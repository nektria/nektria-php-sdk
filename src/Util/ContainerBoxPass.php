<?php

declare(strict_types=1);

namespace Nektria\Util;

use Nektria\Service\AbstractService;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ContainerBoxPass implements CompilerPassInterface
{
    /**
     * @return string[]
     */
    public function getAllTraits(string $class): array
    {
        $traits = [];

        do {
            $uses = class_uses($class);
            if ($uses !== false) {
                $traits = [...$traits, ...$uses];
            }
            $class = get_parent_class($class);
        } while ($class !== false);

        return array_unique($traits);
    }

    public function process(ContainerBuilder $container): void
    {
        foreach ($container->getDefinitions() as $definition) {
            $class = $definition->getClass();

            if ($class === null || !class_exists($class, false)) {
                continue;
            }

            if (is_a($class, AbstractService::class, true)) {
                $definition->addMethodCall('setContainer', [new Reference('service_container')]);
            }
        }
    }
}
