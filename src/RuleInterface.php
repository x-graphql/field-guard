<?php

declare(strict_types=1);

namespace XGraphQL\FieldGuard;

use GraphQL\Type\Definition\ResolveInfo;

interface RuleInterface
{
    public function allows(mixed $value, array $args, mixed $context, ResolveInfo $info): bool;

    public function shouldRemember(mixed $value, array $args, mixed $context, ResolveInfo $info): bool;
}
