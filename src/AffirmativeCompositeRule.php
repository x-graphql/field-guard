<?php

declare(strict_types=1);

namespace XGraphQL\FieldGuard;

use GraphQL\Type\Definition\ResolveInfo;

final readonly class AffirmativeCompositeRule implements RuleInterface
{

    /**
     * @param RuleInterface[] $rules
     */
    public function __construct(private iterable $rules)
    {
    }

    #[\Override]
    public function allows(mixed $value, array $args, mixed $context, ResolveInfo $info): bool
    {
        foreach ($this->rules as $rule) {
            assert($rule instanceof RuleInterface);

            if ($rule->allows($value, $args, $context, $info)) {
                return true;
            }
        }

        return false;
    }

    #[\Override]
    public function shouldRemember(mixed $value, array $args, mixed $context, ResolveInfo $info): bool
    {
        foreach ($this->rules as $rule) {
            if ($rule->shouldRemember($value, $args, $context, $info)) {
                return true;
            }
        }

        return false;
    }
}
