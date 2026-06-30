# Thesis Symfony Console Module

A [Thesis DIC](https://github.com/thesis-php/dic) module that integrates
[Symfony Console](https://symfony.com/doc/current/components/console.html) into your application.

Supports all registration features:
- [invokable commands](https://symfony.com/doc/current/console.html#creating-a-command)
- [method-based commands](https://symfony.com/doc/current/console.html#method-based-commands)
- [`Symfony\Component\Console\Command\Command` subclasses](https://symfony.com/doc/current/console.html#legacy-syntax-to-define-commands)
- [lazy commands](https://symfony.com/doc/current/console/lazy_commands.html)
- `#[Symfony\Component\Console\Attribute\AsCommand]` attribute

## Installation

```shell
composer require thesis/symfony-console-module
```

## Quick start

Here's a console app built with Thesis Dic and Symfony Console Module:

```php
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Attribute\Argument;use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;use Thesis\Dic;
use Thesis\SymfonyConsoleModule;
use Thesis\SymfonyConsoleModule\AutoconfigureCommands;

#[AsCommand('greet', description: 'Greets someone', aliases: ['g'])]
final class GreetCommand
{
    public function __invoke(#[Argument] string $name, SymfonyStyle $io): int
    {
        $io->title("Hello, {$name}!");
    
        return Command::SUCCESS;
    }
}

final class App implements Dic\Module
{
    public function configure(Dic $dic): mixed
    {
        $dic->apply(new AutoconfigureCommands());
        $app = $dic->import(new SymfonyConsoleModule(name: 'MyApp', version: '1.0.0'));

        $dic->object(GreetCommand::class);

        return $app;
    }
}

$status = Dic::run(
    module: new App(),
    main: fn (Application $app) => $app->run(),
);

exit($status);
```

## Documentation

Importing `SymfonyConsoleModule` registers an `Application` and enables two tags that control how commands are added
to it.

### Manual tagging

`CommandTag` marks any callable — an invokable class or a function — to be wrapped in a lazy command:

```php
use Thesis\SymfonyConsoleModule\CommandTag;

$dic->object(GreetCommand::class)
    ->tag(new CommandTag(
        name: 'greet',
        description: 'Greets someone',
        aliases: ['g'],
    ));

$dic->function(static fn(): int => Command::SUCCESS)
    ->tag(new CommandTag('util:noop'));
```

`LegacyCommandTag` marks a `Command` subclass. When `name` is provided, the command is wrapped in a `LazyCommand`
and loaded on demand. When `name` is omitted, the name is read from the command itself — which requires
instantiating it eagerly on application boot:

```php
use Thesis\SymfonyConsoleModule\LegacyCommandTag;

// Setting the name makes the command lazy
$dic->object(LegacyGreetCommand::class)
    ->tag(new LegacyCommandTag(
        name: 'greet:legacy',
        description: 'Legacy greet',
    ));

// Keeping the name from the Command class itself requires to instantiate it
$dic->object(LegacyGreetCommand::class)
    ->tag(new LegacyCommandTag());
```

### Autoconfiguration with `#[AsCommand]`

`AutoconfigureCommands` reads the native `#[AsCommand]` attribute and applies the appropriate tag automatically,
so you don't have to tag each class by hand.

Apply it once per module:

```php
$dic->apply(new AutoconfigureCommands());
```

**Invokable class** — any class with `#[AsCommand]` and an `__invoke()` method gets a `CommandTag`:

```php
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

#[AsCommand('greet', 'Greets someone')]
final class GreetCommand
{
    public function __invoke(): int
    {
        return Command::SUCCESS;
    }
}

$dic->object(GreetCommand::class);
```

**Method-based commands** — methods annotated with `#[AsCommand]` are picked up individually, which is convenient
when one class groups related commands:

```php
final class GreetCommands
{
    #[AsCommand('greet:alice', 'Greet Alice')]
    public function greetAlice(): int { /* ... */ }

    #[AsCommand('greet:bob', 'Greet Bob')]
    public function greetBob(): int { /* ... */ }
}

$dic->object(GreetCommands::class);
```

**`Command` subclass** — classes that extend `Command` get a `LegacyCommandTag`. The name comes from `#[AsCommand]`
if present, otherwise from the name already set on the class:

```php
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class LegacyGreetCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return self::SUCCESS;
    }
}

$dic->object(LegacyGreetCommand::class);
```
