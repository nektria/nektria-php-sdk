<?php

declare(strict_types=1);

namespace Nektria\Listener;

use Nektria\Exception\InsufficientCredentialsException;
use Nektria\Exception\InvalidAuthorizationException;
use Nektria\Infrastructure\SecurityServiceInterface;
use Nektria\Service\ContextService;
use Nektria\Service\RoleManager;
use Nektria\Util\StringUtil;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

abstract class RequestListener extends BaseRequestListener
{
    public function __construct(
        protected readonly SecurityServiceInterface $securityService,
        ContainerInterface $container,
    ) {
        parent::__construct(
            container: $container
        );
    }

    protected function assignLogLevel(Request $request): ?string
    {
        $route = $request->attributes->get('_route') ?? '';

        if (
            str_starts_with($route, 'nektria_')
            || str_starts_with($route, 'app_admin_tools_')
            || str_starts_with($route, 'app_common_')
            || str_starts_with($route, 'app_public_')
        ) {
            return self::LOG_LEVEL_NONE;
        }

        return self::LOG_LEVEL_INFO;
    }

    protected function checkAccess(Request $request): void
    {
        $route = $request->attributes->get('_route') ?? '';
        $apiKey = $this->readApiKey($request);

        if (str_starts_with($route, 'app_private_')) {
            $authorization = $request->headers->get('Authorization');
            $tenantId = $request->attributes->get('tenantId') ?? '';

            if (
                $authorization === null
                || !str_starts_with($authorization, 'Bearer ')
                || !StringUtil::isUuid4($tenantId)
            ) {
                throw new InvalidAuthorizationException();
            }

            $token = substr($authorization, 7);
            $this->securityService->authenticateApi($token);
            if ($this->securityService->retrieveCurrentUser()->role !== RoleManager::ROLE_ADMIN) {
                $this->securityService->clearAuthentication();

                throw new InvalidAuthorizationException();
            }

            $this->contextService()->setContext(ContextService::PRIVATE);
            $this->securityService->authenticateSystem($tenantId);

            return;
        }

        if (
            str_starts_with($route, 'app_common_')
            || str_starts_with($route, 'app_public_')
            || str_starts_with($route, 'nektria_public_')
            || str_starts_with($route, 'nektria_common_')
        ) {
            if ($apiKey !== '') {
                $this->securityService->authenticateUser($apiKey);

                if (!$this->validateUser($this->securityService->retrieveCurrentUser())) {
                    $this->securityService->clearAuthentication();

                    throw new InvalidAuthorizationException();
                }
            }

            return;
        }

        if (str_starts_with($route, 'app_admin') || str_starts_with($route, 'nektria_admin')) {
            $this->contextService()->setContext(ContextService::ADMIN);
            $this->securityService->authenticateUser($apiKey);

            try {
                $this->securityService->validateRole([RoleManager::ROLE_ADMIN]);
            } catch (InsufficientCredentialsException) {
                $this->securityService->clearAuthentication();
            }
        } elseif (
            str_starts_with($route, 'app_apilegacy_')
            || str_starts_with($route, 'app_api_')
            || str_starts_with($route, 'nektria_api_')
        ) {
            $this->contextService()->setContext(ContextService::PUBLIC);
            $this->securityService->authenticateUser($apiKey);
        } elseif (str_starts_with($route, 'app_api2_') || str_starts_with($route, 'nektria_api2_')) {
            $this->contextService()->setContext(ContextService::PUBLIC_V2);
            $this->securityService->authenticateUser($apiKey);
        } elseif (str_starts_with($route, 'app_web_') || str_starts_with($route, 'nektria_web_')) {
            $this->contextService()->setContext(ContextService::INTERNAL);
            $this->securityService->authenticateUser($apiKey);
            if ($this->securityService->currentUser() !== null) {
                $this->contextService()->addExtra('userId', $this->securityService->currentUser()->id);
            }
        }

        if (!$this->validateUser($this->securityService->retrieveCurrentUser())) {
            $this->securityService->clearAuthentication();

            throw new InvalidAuthorizationException();
        }
    }

    /**
     * @return string[]
     */
    protected function exposedHeaders(): array
    {
        return [
            'Link', 'X-Authorization'
        ];
    }

    /**
     * @return string[]
     */
    protected function getAllowedCorsHeaders(): array
    {
        return [
            'Accept', 'Accept-Encoding', 'Accept-Language', 'Access-Control-Request-Headers',
            'Access-Control-Request-Method', 'Connection', 'Content-Length', 'Content-Type', 'Host', 'Origin',
            'Referer', 'User-Agent', 'X-Authorization', 'X-Api-Id', 'X-Nektria-App', 'X-Trace', 'X-sync',
            'Cross-Origin-Embedder-Policy', 'Cross-Origin-Opener-Policy', 'X-Tenant', 'X-Api-Version', 'X-Origin',
        ];
    }

    protected function readApiKey(Request $request): string
    {
        if ($request->headers->has('X-Authorization')) {
            $header = 'X-Authorization';
        } elseif ($request->headers->has('X-Api-Id')) {
            $header = 'X-Api-Id';
        } else {
            return '';
        }

        return $request->headers->get($header) ?? '';
    }
}
