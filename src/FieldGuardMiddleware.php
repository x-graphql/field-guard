<?php

declare(strict_types=1);

namespace XGraphQL\FieldGuard;

use GraphQL\Error\Error;
use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Type\Definition\ResolveInfo;
use XGraphQL\FieldMiddleware\MiddlewareInterface;

final class FieldGuardMiddleware implements MiddlewareInterface
{
    /**
     * @var \WeakMap<OperationDefinitionNode>
     */
    private \WeakMap $remembered;

    /**
     * @param array<string, array<string, RuleInterface>> $permissions
     */
    public function __construct(private readonly array $permissions)
    {
        $this->remembered = new \WeakMap();
    }

    /**
     * @throws Error
     */
    public function resolve(mixed $value, array $arguments, mixed $context, ResolveInfo $info, callable $next): mixed
    {
        $parentTypename = $info->parentType->name();
        $fieldName = $info->fieldName;
        $rule = $this->permissions[$parentTypename][$fieldName] ?? null;

        if (null !== $rule) {
            $operationRemembered = $this->remembered[$info->operation] ??= new \WeakMap();
            $parentRemembered = $operationRemembered[$info->parentType] ??= [];

            if (isset($parentRemembered[$fieldName])) {
                $canAccess = $parentRemembered[$fieldName];
            } else {
                $canAccess = $rule->allows($value, $arguments, $context, $info);

                if ($rule->shouldRemember($value, $arguments, $context, $info)) {
                    $parentRemembered[$fieldName] = $canAccess;
                }
            }

            if (false === $canAccess) {
                throw new Error('You not have permitted to access this field');
            }
        }

        return $next($value, $arguments, $context, $info);
    }
}
