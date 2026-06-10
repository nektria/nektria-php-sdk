<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Document\ThrowableDocument;
use Nektria\Dto\LocalClock;
use Nektria\Exception\NektriaRuntimeException;
use Nektria\Infrastructure\SharedDiscordCache;
use Nektria\Util\JsonUtil;
use Throwable;

use function count;
use function in_array;

/**
 * @phpstan-type AlertMessage array{
 *      content?: string|null,
 *      embeds?: array<array{
 *          title?: string|null,
 *          type?: string|null,
 *          description?: string|null,
 *          url?: string|null,
 *          timestamp?: string|null,
 *          color?: int|null,
 *          footer?: array{
 *              text?: string,
 *              icon_url?: string
 *          }|null,
 *          image?: array{
 *              url?: string,
 *              height?: int,
 *              width?: int
 *          }|null,
 *          thumbnail?: array{
 *              url?: string,
 *              height?: int,
 *              width?: int
 *          }|null,
 *          author?: array{
 *              name?: string,
 *          }|null,
 *          fields?: array<array{
 *              name?: string,
 *              value?: string,
 *              inline?: bool
 *          }>|null,
 *      }>|null,
 *      components?: array<array{
 *          type: int,
 *          components: array<array{
 *              type: int,
 *              style: int,
 *              label: string,
 *              custom_id: string
 *          }>
 *      }>|null,
 *      flags?: int|null,
 *  }
 */
readonly class AlertService extends AbstractService
{
    public const string CHANNEL_ALARMS = 'alarms';

    public const string CHANNEL_BUGS = 'bugs';

    public const string CHANNEL_CONFIGURATIONS = 'configurations';

    public const string CHANNEL_OPERATIONS = 'operations';

    public const string CHANNEL_PICKING_SHIFTS = 'pickingshifts';

    public const string CHANNEL_UPDATES = 'updates';

    public const string EMPTY_LINE = "‎\n‎";

    public const int FLAG_SUPPRESS_NOTIFICATIONS = 1 << 12;

    /** @var string[] */
    private array $tokens;

    public function __construct(
        private SharedDiscordCache $sharedDiscordCache,
        private string $alertsToken,
    ) {
        parent::__construct();
        $this->tokens = explode(',', $this->alertsToken);
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function channels(): array
    {
        return [
            'dev' => [
                'operations' => '1221387322755383356',
                'updates' => '1221387427000750120',
                'bugs' => '1221173694878060635',
                'pickingshifts' => '1221387354246221874',
                'configurations' => '1223608760287760545',
                'debug' => '1235335765550956654',
                'alarms' => '1355500573260251226',
            ],
            'qa' => [
                'operations' => '1221387486320787518',
                'updates' => '1221387553375260714',
                'bugs' => '1221173866131623966',
                'pickingshifts' => '1221387520726663208',
                'configurations' => '1223608835256750293',
                'debug' => '1235335567089209404',
                'alarms' => '1355500424651735100',
            ],
            'staging' => [
                'operations' => '1221387600485417000',
                'updates' => '1221387669410680893',
                'bugs' => '1221173888751239198',
                'pickingshifts' => '1221387636548309183',
                'configurations' => '1223609089490292796',
                'debug' => '1235335500974264350',
                'alarms' => '1355500367097368576',
            ],
            'prod' => [
                'operations' => '1221221066450669678',
                'updates' => '1221224077482528908',
                'bugs' => '1221173937354833940',
                'pickingshifts' => '1221221171161468959',
                'configurations' => '1223571661421412352',
                'debug' => '1235335372506923068',
                'alarms' => '1355500222046015539',
            ],
        ];
    }

    /**
     * @return string[]
     */
    public function channelsList(): array
    {
        return array_keys($this->channels()[$this->contextService()->env()]);
    }

    public function cleanMessagesFromCache(string $channel): void
    {
        $this->sharedDiscordCache->remove($channel);
    }

    /**
     * @param AlertMessage $message
     */
    public function debugMessage(array $message, ?int $flags = null): void
    {
        if (!$this->contextService()->debugMode()) {
            return;
        }

        $this->sendMessage('debug', $message, $flags);
    }

    /**
     * @return AlertMessage[]
     */
    public function readMessagesFromCache(string $channel): array
    {
        return $this->sharedDiscordCache->read($channel);
    }

    /**
     * @param AlertMessage $message
     */
    public function sendMessage(string $channel, array $message, ?int $flags = null): void
    {
        $hour = (int) LocalClock::now('Europe/Madrid')->hour();
        if ($hour < 8 || $hour > 23) {
            $flags |= self::FLAG_SUPPRESS_NOTIFICATIONS;
        }

        $eol = self::EMPTY_LINE;
        $tenantName = $this->securityService()->currentUser()?->tenant->name ?? 'none';
        $message['content'] ??= '';
        $message['content'] =
            $eol .
            "**{$this->contextService()->project()}**{$eol}" .
            "**{$tenantName}**{$eol}" .
            $eol .
            $message['content'];

        if (count($message['embeds'] ?? []) === 0) {
            $message['content'] .= $eol;
        }

        try {
            $this->makeRequest($channel, $message);
        } catch (Throwable $e) {
            try {
                $content = "‎\n" .
                    '**Discord Api Error**' .
                    "```json\n" .
                    JsonUtil::encode(JsonUtil::decode($e->getMessage()), true) .
                    "\n```" .
                    "Trace: {$this->contextService()->traceId()}\n" .
                    "‎\n‎";

                if (str_contains($content, 'You are being rate limited.')) {
                    return;
                }

                $this->makeRequest($channel, [
                    'content' => $content,
                    'flags' => $flags,
                ]);
            } catch (Throwable) {
            }
        }
    }

    /**
     * @param mixed[] $input
     */
    public function sendThrowable(
        string $tenantName,
        string $method,
        string $path,
        array $input,
        ThrowableDocument $document,
        int $times = 1,
        ?int $flags = null
    ): void {
        if ($this->contextService()->env() === 'test') {
            return;
        }

        if (
            ($document->throwable instanceof NektriaRuntimeException)
            && $document->throwable->silent() && !$this->contextService()->debugMode()
        ) {
            return;
        }

        $hour = (int) LocalClock::now('Europe/Madrid')->hour();
        if ($hour < 8 || $hour > 23) {
            $flags |= self::FLAG_SUPPRESS_NOTIFICATIONS;
        }

        $inputString = JsonUtil::encode($input, true);
        $documentString = JsonUtil::encode($document->toDevArray(), true);
        $eol = self::EMPTY_LINE;
        $manyTimes = $times === 1 ? '' : " (x{$times})";
        $content =
            $eol .
            "**{$this->contextService()->project()}**{$eol}" .
            "**{$tenantName}**{$eol}" .
            $eol .
            "**{$method}** _{$path}_ {$manyTimes}\n" .
            "```json\n" .
            $inputString .
            "\n```" .
            "```json\n" .
            $documentString .
            "\n```" .
            "Trace: {$this->contextService()->traceId()}\n" .
            self::EMPTY_LINE;

        $content = str_replace(['\/', '/app/'], ['/', ''], $content);
        $content = html_entity_decode(preg_replace('/\\\u([\da-fA-F]{4})/', '&#x\1;', $content) ?? '');

        try {
            $this->makeRequest(self::CHANNEL_BUGS, [
                'content' => $content,
            ]);
        } catch (Throwable) {
            $content = self::EMPTY_LINE .
                $eol .
                "**{$this->contextService()->project()}**{$eol}" .
                "**{$tenantName}**{$eol}" .
                $eol .
                "**{$method}** _{$path}_ {$manyTimes}\n" .
                "Trace: {$this->contextService()->traceId()}\n" .
                self::EMPTY_LINE;

            $this->makeRequest(self::CHANNEL_BUGS, [
                'content' => $content,
                'flags' => $flags,
            ]);
        }
    }

    /**
     * @param array{
     *     name: string,
     *     value: string,
     * }[] $embeds
     */
    public function simpleDebugMessage(
        string $message,
        array $embeds = [],
        ?int $flags = null
    ): void {
        if (count($embeds) > 0) {
            $embeds = [
                [
                    'fields' => $embeds,
                ],
            ];
        }

        $this->debugMessage([
            'content' => $message,
            'embeds' => $embeds,
        ], $flags);
    }

    /**
     * @param array{
     *     name: string,
     *     value: string,
     * }[] $embeds
     */
    public function simpleMessage(
        string $channel,
        string $message,
        array $embeds = [],
        ?int $flags = null
    ): void {
        if (count($embeds) > 0) {
            $embeds = [
                [
                    'fields' => $embeds,
                ],
            ];
        }

        $this->sendMessage($channel, [
            'content' => $message,
            'embeds' => $embeds,
        ], $flags);
    }

    /**
     * @param AlertMessage $message
     */
    private function makeRequest(string $channel, array $message): void
    {
        if ($this->contextService()->env() === 'test') {
            return;
        }

        $token = $this->tokens[array_rand($this->tokens)];
        $channelId = $this->parseChannel($channel);
        $this->sharedDiscordCache->addMessage($channel, $message);
        $this->requestClient()->post(
            "https://discord.com/api/channels/{$channelId}/messages",
            $message,
            [
                'Authorization' => "Bot {$token}",
                'Content-Type' => 'application/json',
            ],
            enableDebugFallback: false,
        );
        $this->sharedDiscordCache->removeLastMessage($channel);
    }

    private function parseChannel(string $channelId): string
    {
        $defaultChannels = array_keys($this->channels()[$this->contextService()->env()]);

        if (!in_array($channelId, $defaultChannels, true)) {
            return $channelId;
        }

        $configuration = $this->channels();

        return $configuration[$this->contextService()->env()][$channelId];
    }
}
