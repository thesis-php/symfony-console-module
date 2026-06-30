<?php

declare(strict_types=1);

namespace Thesis\SymfonyConsoleModule;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Thesis\Dic\Tag;

/**
 * @api
 *
 * @implements Tag<Command>
 */
final readonly class LegacyCommandTag implements Tag
{
    /**
     * @internal
     */
    public static function fromAttribute(?AsCommand $attribute): self
    {
        if ($attribute === null) {
            return new self();
        }

        $names = explode('|', $attribute->name);

        $isHidden = $names[0] === '';

        $names = array_filter($names, static fn(string $name) => $name !== '');

        return new self(
            name: array_shift($names) ?? throw new \InvalidArgumentException('Command name cannot be empty.'),
            description: $attribute->description ?? '',
            aliases: array_values($names),
            isHidden: $isHidden,
        );
    }

    /**
     * @param ?non-empty-string $name
     * @param list<non-empty-string> $aliases
     */
    public function __construct(
        public ?string $name = null,
        public string $description = '',
        public array $aliases = [],
        public bool $isHidden = false,
    ) {}
}
