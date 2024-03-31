<?php

declare(strict_types=1);

namespace XGraphQL\FieldGuard;

use GraphQL\Type\Definition\ResolveInfo;

/**
 * Help to manage access control
 */
interface RuleInterface
{
    /**
     * Whether to accept user access system.
     */
    public function allows(mixed $value, array $args, mixed $context, ResolveInfo $info): bool;

    /**
     * Whether to remember [[allows]] result when accessing field again.
     */
    public function shouldRemember(mixed $value, array $args, mixed $context, ResolveInfo $info): bool;
}
