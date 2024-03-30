<?php

declare(strict_types=1);

namespace XGraphQL\FieldGuard;

use GraphQL\Type\Definition\ResolveInfo;

final readonly class ObjectRule implements RuleInterface
{
    /**
     * @param string $typename
     * @param array<string, RuleInterface|bool> $fields
     * @param RuleInterface|bool $defaultFieldRule
     */
    public function __construct(private string $typename, private array $fields = [], private RuleInterface|bool $defaultFieldRule = true)
    {
    }

    #[\Override]
    public function allows(mixed $value, array $args, mixed $context, ResolveInfo $info): bool
    {
        if ($info->parentType->name() !== $this->typename) {
            return false;
        }

        foreach ($this->fields as $name => $rule) {
            if ($info->fieldName !== $name) {
                continue;
            }

            return $this->check($rule, $value, $args, $context, $info);
        }

        return $this->check($this->defaultFieldRule, $value, $args, $context, $info);
    }

    private function check(RuleInterface|bool $rule, mixed $value, array $args, mixed $context, ResolveInfo $info): bool
    {
        if ($rule instanceof RuleInterface) {
            return $rule->allows($value, $args, $context, $info);
        }

        return $rule;
    }

    #[\Override]
    public function shouldRemember(mixed $value, array $args, mixed $context, ResolveInfo $info): bool
    {
        foreach ($this->fields as $name => $rule) {
            if ($name !== $info->fieldName) {
                continue;
            }

            if ($rule instanceof RuleInterface) {
                return $rule->shouldRemember($value, $args, $context, $info);
            }

            return true;
        }

        if ($this->defaultFieldRule instanceof RuleInterface) {
            return $this->defaultFieldRule->shouldRemember($value, $args, $context, $info);
        }

        return true;
    }
}
