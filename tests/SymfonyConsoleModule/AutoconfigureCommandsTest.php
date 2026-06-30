<?php

declare(strict_types=1);

namespace Thesis\SymfonyConsoleModule;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LazyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Expect;
use Testo\Test;
use Thesis\Dic;
use Thesis\Dic\ClosureModule;
use Thesis\SymfonyConsoleModule;

#[Test]
#[Covers(AutoconfigureCommands::class)]
final class AutoconfigureCommandsTest
{
    public function objectExtendingCommandIsTaggedWithLegacyCommandTag(): void
    {
        $commandClass = new class extends Command {
            public function __construct()
            {
                parent::__construct('auto:legacy');
            }

            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                return self::SUCCESS;
            }
        }::class;

        $app = Dic::build(new ClosureModule(static function (Dic $dic) use ($commandClass) {
            $dic->apply(new AutoconfigureCommands());
            $app = $dic->import(new SymfonyConsoleModule());

            $dic->object($commandClass);

            return $app;
        }));

        Assert::same($app->find('auto:legacy')->getName(), 'auto:legacy');
    }

    public function invokableObjectWithAsCommandIsTaggedWithCommandTag(): void
    {
        $commandClass = new #[AsCommand('auto:invokable', 'Invokable command')] class {
            public function __invoke(): int
            {
                return Command::SUCCESS;
            }
        }::class;

        $app = Dic::build(new ClosureModule(static function (Dic $dic) use ($commandClass) {
            $dic->apply(new AutoconfigureCommands());
            $app = $dic->import(new SymfonyConsoleModule());

            $dic->object($commandClass);

            return $app;
        }));

        $command = $app->find('auto:invokable');
        Assert::instanceOf($command, LazyCommand::class);
        Assert::same($command->getDescription(), 'Invokable command');
    }

    public function nonInvokableObjectWithAsCommandThrowsLogicException(): void
    {
        $commandClass = new #[AsCommand('auto:non-invokable')] class {
            public string $value = '';
        }::class;

        Expect::exception(\LogicException::class)->withMessageContaining('#[AsCommand] on');

        Dic::build(new ClosureModule(static function (Dic $dic) use ($commandClass) {
            $dic->apply(new AutoconfigureCommands());
            $app = $dic->import(new SymfonyConsoleModule());

            $dic->object($commandClass);

            return $app;
        }));
    }

    public function methodWithAsCommandIsAutoconfigured(): void
    {
        $commandClass = new class {
            #[AsCommand('auto:from-method', 'From method')]
            public function build(): int
            {
                return Command::SUCCESS;
            }
        }::class;

        $app = Dic::build(new ClosureModule(static function (Dic $dic) use ($commandClass) {
            $dic->apply(new AutoconfigureCommands());
            $app = $dic->import(new SymfonyConsoleModule());

            $dic->object($commandClass);

            return $app;
        }));

        $command = $app->find('auto:from-method');
        Assert::instanceOf($command, LazyCommand::class);
        Assert::same($command->getDescription(), 'From method');
    }
}
