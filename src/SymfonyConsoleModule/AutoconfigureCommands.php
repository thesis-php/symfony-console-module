<?php

declare(strict_types=1);

namespace Thesis\SymfonyConsoleModule;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Thesis\Dic;
use Thesis\Dic\Configuration\FunctionAutoconfig;
use Thesis\Dic\Configuration\ObjectAutoconfig;

/**
 * @api
 */
final readonly class AutoconfigureCommands
{
    public function __invoke(Dic $dic): void
    {
        $dic->onObject($this->onObject(...));
        $dic->onFunction($this->onFunction(...));
    }

    /**
     * @param ObjectAutoconfig<object> $object
     */
    private function onObject(ObjectAutoconfig $object): void
    {
        $attribute = $object->attributes->find(AsCommand::class);

        if ($object->is(Command::class)) {
            $object->tag(LegacyCommandTag::fromAttribute($attribute));
        } elseif ($attribute !== null) {
            if (!$object->isInvokable()) {
                throw new \LogicException(\sprintf(
                    '#[AsCommand] on %s requires an __invoke() method.',
                    $object->reflection->name,
                ));
            }

            $object->tag(CommandTag::fromAttribute($attribute));
        }

        foreach ($object->methods as $method) {
            if ($method->attributes->has(AsCommand::class)) {
                $method->register();
            }
        }
    }

    private function onFunction(FunctionAutoconfig $function): void
    {
        if (null !== $attribute = $function->attributes->find(AsCommand::class)) {
            $function->tag(CommandTag::fromAttribute($attribute));
        }
    }
}
