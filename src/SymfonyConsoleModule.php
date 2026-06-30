<?php

declare(strict_types=1);

namespace Thesis;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LazyCommand;
use Thesis\Dic\DoNotAutowire;
use Thesis\Dic\Module;
use Thesis\Dic\Ref;
use Thesis\Dic\TaggedRefs;
use Thesis\SymfonyConsoleModule\CommandTag;
use Thesis\SymfonyConsoleModule\LegacyCommandTag;
use const Thesis\Dic\doNotAutowire;

/**
 * @api
 *
 * @implements Module<Ref<Application>>
 */
final readonly class SymfonyConsoleModule implements Module
{
    public function __construct(
        private string|DoNotAutowire $name = doNotAutowire,
        private string|DoNotAutowire $version = doNotAutowire,
    ) {}

    public function configure(Dic $dic): mixed
    {
        $app = $dic
            ->object(Application::class)
            ->args([
                'name' => $this->name,
                'version' => $this->version,
            ]);

        $dic->onTagResolution(function (TaggedRefs $taggedRefs) use ($dic, $app): void {
            foreach ($taggedRefs->find(CommandTag::class) as $taggedRef) {
                $app->call('addCommand', [
                    $this->buildCommand($dic, $taggedRef->ref, $taggedRef->tag),
                ]);
            }

            foreach ($taggedRefs->find(LegacyCommandTag::class) as $taggedRef) {
                $app->call('addCommand', [
                    $this->buildLegacyCommand($dic, $taggedRef->ref, $taggedRef->tag),
                ]);
            }
        });

        return $app;
    }

    /**
     * @param Ref<callable> $code
     * @return Ref<LazyCommand>
     */
    private function buildCommand(Dic $dic, Ref $code, CommandTag $tag): Ref
    {
        $command = $dic
            ->object(Command::class)
            ->call('setCode', [$code]);

        return $dic
            ->object(LazyCommand::class)
            ->args([
                'name' => $tag->name,
                'description' => $tag->description,
                'aliases' => $tag->aliases,
                'isHidden' => $tag->isHidden,
                'commandFactory' => $dic->provider($command),
            ]);
    }

    /**
     * @param Ref<Command> $command
     * @return Ref<Command>
     */
    private function buildLegacyCommand(Dic $dic, Ref $command, LegacyCommandTag $tag): Ref
    {
        if ($tag->name === null) {
            return $command;
        }

        return $dic
            ->object(LazyCommand::class)
            ->args([
                'name' => $tag->name,
                'description' => $tag->description,
                'aliases' => $tag->aliases,
                'isHidden' => $tag->isHidden,
                'commandFactory' => $dic->provider($command),
            ]);
    }
}
