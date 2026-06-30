<?php

declare(strict_types=1);

namespace Thesis\SymfonyConsoleModule;

use Symfony\Component\Console\Attribute\AsCommand;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Expect;
use Testo\Test;

#[Test]
#[Covers(CommandTag::class)]
final class CommandTagTest
{
    public function fromAttributeSimpleName(): void
    {
        $tag = CommandTag::fromAttribute(new AsCommand('foo'));

        Assert::same($tag->name, 'foo');
        Assert::same($tag->description, '');
        Assert::same($tag->aliases, []);
        Assert::false($tag->isHidden);
    }

    public function fromAttributeWithDescription(): void
    {
        $tag = CommandTag::fromAttribute(new AsCommand('foo', 'Does something'));

        Assert::same($tag->name, 'foo');
        Assert::same($tag->description, 'Does something');
    }

    public function fromAttributeWithAliases(): void
    {
        $tag = CommandTag::fromAttribute(new AsCommand('foo|bar|baz'));

        Assert::same($tag->name, 'foo');
        Assert::same($tag->aliases, ['bar', 'baz']);
        Assert::false($tag->isHidden);
    }

    public function fromAttributeHidden(): void
    {
        $tag = CommandTag::fromAttribute(new AsCommand('|foo'));

        Assert::same($tag->name, 'foo');
        Assert::same($tag->aliases, []);
        Assert::true($tag->isHidden);
    }

    public function fromAttributeHiddenWithAliases(): void
    {
        $tag = CommandTag::fromAttribute(new AsCommand('|foo|bar'));

        Assert::same($tag->name, 'foo');
        Assert::same($tag->aliases, ['bar']);
        Assert::true($tag->isHidden);
    }

    public function fromAttributeEmptyNameThrowsInvalidArgumentException(): void
    {
        Expect::exception(\InvalidArgumentException::class)->withMessageContaining('Command name cannot be empty.');

        CommandTag::fromAttribute(new AsCommand('|'));
    }
}
