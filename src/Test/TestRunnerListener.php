<?php

declare(strict_types=1);

namespace Nektria\Test;

use Doctrine\ORM\EntityManager;
use Nektria\Exception\NektriaException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Process\Process;
use Throwable;

readonly class TestRunnerListener
{
    public function __construct(
        protected ContainerInterface $container
    ) {
    }

    public function onBoot(): void
    {
    }

    public function restartDatabase(
        bool $reuseDatabase = true,
        bool $useMigrations = true
    ): void {
        try {
            if (!$reuseDatabase) {
                (new Process(['bin/console', 'd:d:c', '-e', 'test']))->run();
                /** @var EntityManager $em */
                $em = $this->container->get('doctrine.orm.entity_manager');
                $em->getConnection()->executeStatement("
                    DO $$
                    DECLARE
                        r RECORD;
                    BEGIN
                        FOR r IN (SELECT tablename FROM pg_tables WHERE schemaname = 'public')
                        LOOP
                            EXECUTE 'DROP TABLE IF EXISTS public.' || quote_ident(r.tablename) || ' CASCADE';
                        END LOOP;
                    END $$;
                ");
            }

            if ($useMigrations) {
                (new Process(['bin/console', 'd:m:m', '-e', 'test', '-n']))->run();
            } else {
                (new Process(['bin/console', 'd:s:u', '-e', 'test', '--dump-sql', '--force']))->run();
            }
        } catch (Throwable $e) {
            throw NektriaException::new($e);
        }
    }
}
