<?php

declare(strict_types=1);

namespace Nektria\Console\Debug;

use Nektria\Console\Console;
use Nektria\Infrastructure\ArrayDocumentReadModel;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FixMigrarionsConsole extends Console
{
    public function __construct(
        private readonly ContainerInterface $container,
    ) {
        parent::__construct('debug:migrarions:fix');
    }

    protected function play(): void
    {
        /** @var ArrayDocumentReadModel $readModel */
        $readModel = $this->container->get(ArrayDocumentReadModel::class);

        $readModel->fixMigrations();
    }
}
