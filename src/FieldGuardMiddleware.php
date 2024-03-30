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
     * @param array<string, array<string, RuleInterface|bool>> $permissions
     */
    public function __construct(private readonly array $permissions, private readonly bool $defaultDeny = false)
    {
        $this->remembered = new \WeakMap();
    }

    /**
     * @throws Error
     */
    public function resolve(mixed $value, array $arguments, mixed $context, ResolveInfo $info, callable $next): mixed
    {
        $object = $info->parentType->name();
        $field = $info->fieldName;
        $rule = $this->permissions[$object][$field] ?? null;

        if (null === $rule) {
            $canAccess = !$this->defaultDeny;
        } else {
            $operationRemembered = $this->remembered[$info->operation] ??= new \WeakMap();
            $objectRemembered = $operationRemembered[$info->parentType] ??= new \ArrayObject();

            if (isset($objectRemembered[$field])) {
                $canAccess = $objectRemembered[$field];
            } else {
                $canAccess = $this->allows($rule, $value, $arguments, $context, $info);

                if ($this->shouldRemember($rule, $value, $arguments, $context, $info)) {
                    $objectRemembered[$field] = $canAccess;
                }
            }
        }

        if (false === $canAccess) {
            throw new Error('You not have permitted to access this field');
        }

        return $next($value, $arguments, $context, $info);
    }

    private function allows(RuleInterface|bool $rule, mixed $value, array $arguments, mixed $context, ResolveInfo $info): bool
    {
        if ($rule instanceof RuleInterface) {
            return $rule->allows($value, $arguments, $context, $info);
        }

        return $rule;
    }

    private function shouldRemember(RuleInterface|bool $rule, mixed $value, array $arguments, mixed $context, ResolveInfo $info): bool
    {
        if ($rule instanceof RuleInterface) {
            return $rule->shouldRemember($value, $arguments, $context, $info);
        }

        return true; /// always remember static boolean rule
    }
}
