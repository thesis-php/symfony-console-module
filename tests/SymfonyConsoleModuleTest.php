<?php

declare(strict_types=1);

namespace Thesis;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LazyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;
use Thesis\Dic\ClosureModule;
use Thesis\SymfonyConsoleModule\CommandTag;
use Thesis\SymfonyConsoleModule\LegacyCommandTag;

#[Test]
#[Covers(SymfonyConsoleModule::class)]
final class SymfonyConsoleModuleTest
{
    public function buildsApplicationWithDefaultName(): void
    {
        $app = Dic::build(new SymfonyConsoleModule());

        Assert::same($app->getName(), 'UNKNOWN');
    }

    public function buildsApplicationWithCustomName(): void
    {
        $app = Dic::build(new SymfonyConsoleModule(name: 'MyApp'));

        Assert::same($app->getName(), 'MyApp');
    }

    public function buildsApplicationWithCustomVersion(): void
    {
        $app = Dic::build(new SymfonyConsoleModule(name: 'MyApp', version: '2.0'));

        Assert::same($app->getVersion(), '2.0');
    }

    public function commandTaggedWithCommandTagIsAddedAsLazyCommand(): void
    {
        $app = Dic::build(new ClosureModule(static function (Dic $dic) {
            $app = $dic->import(new SymfonyConsoleModule());

            $dic->function(static fn(): int => Command::SUCCESS)
                ->tag(new CommandTag('greet', 'Greets someone'));

            return $app;
        }));

        $command = $app->find('greet');
        Assert::instanceOf($command, LazyCommand::class);
        Assert::same($command->getName(), 'greet');
        Assert::same($command->getDescription(), 'Greets someone');
    }

    public function commandTaggedWithLegacyCommandTagIsAdded(): void
    {
        $commandClass = new class extends Command {
            public function __construct()
            {
                parent::__construct('legacy:greet-default');
            }

            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                return self::SUCCESS;
            }
        }::class;

        $app = Dic::build(new ClosureModule(static function (Dic $dic) use ($commandClass) {
            $app = $dic->import(new SymfonyConsoleModule());

            $dic->object($commandClass)
                ->tag(new LegacyCommandTag('legacy:greet', 'Legacy greet'));

            return $app;
        }));

        $command = $app->find('legacy:greet');
        Assert::same($command->getName(), 'legacy:greet');
        Assert::same($command->getDescription(), 'Legacy greet');
    }

    public function legacyCommandWithoutNameUsesCommandClassDefaultName(): void
    {
        $commandClass = new class extends Command {
            public function __construct()
            {
                parent::__construct('legacy:greet-default');
            }

            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                return self::SUCCESS;
            }
        }::class;

        $app = Dic::build(new ClosureModule(static function (Dic $dic) use ($commandClass) {
            $app = $dic->import(new SymfonyConsoleModule());

            $dic->object($commandClass)
                ->tag(new LegacyCommandTag());

            return $app;
        }));

        Assert::same($app->find('legacy:greet-default')->getName(), 'legacy:greet-default');
    }

    public function commandTagAliasesAreRegistered(): void
    {
        $app = Dic::build(new ClosureModule(static function (Dic $dic) {
            $app = $dic->import(new SymfonyConsoleModule());

            $dic->function(static fn(): int => Command::SUCCESS)
                ->tag(new CommandTag('greet', aliases: ['g', 'hi']));

            return $app;
        }));

        Assert::same($app->find('g')->getName(), 'greet');
        Assert::same($app->find('hi')->getName(), 'greet');
    }

    public function hiddenCommandTagIsHidden(): void
    {
        $app = Dic::build(new ClosureModule(static function (Dic $dic) {
            $app = $dic->import(new SymfonyConsoleModule());

            $dic->function(static fn(): int => Command::SUCCESS)
                ->tag(new CommandTag('hidden:cmd', isHidden: true));

            return $app;
        }));

        Assert::true($app->find('hidden:cmd')->isHidden());
    }
}
