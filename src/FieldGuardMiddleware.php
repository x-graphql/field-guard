<?php

declare(strict_types=1);

namespace XGraphQL\FieldGuard;

use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Type\Definition\ResolveInfo;
use XGraphQL\FieldGuard\Exception\AccessDeniedException;
use XGraphQL\FieldMiddleware\MiddlewareInterface;

final class FieldGuardMiddleware implements MiddlewareInterface
{
    /**
     * @var \WeakMap<OperationDefinitionNode>
     */
    private \WeakMap $remembered;

    public function __construct(private readonly RuleInterface $rule)
    {
        $this->remembered = new \WeakMap();
    }

    public function resolve(mixed $value, array $arguments, mixed $context, ResolveInfo $info, callable $next): mixed
    {
        $operation = $info->operation;
        $operationRemembered = $this->remembered[$operation] ??= new \WeakMap();
        $parentRemembered = $operationRemembered[$info->parentType] ??= [];
        $fieldName = $info->fieldName;

        if (isset($parentRemembered[$fieldName])) {
            $canAccess = $parentRemembered[$fieldName];
        } else {
            $canAccess = $this->rule->allows($value, $arguments, $context, $info);

            if ($this->rule->shouldRemember($value, $arguments, $context, $info)) {
                $parentRemembered[$fieldName] = $canAccess;
            }
        }

        if (false === $canAccess) {
            throw new AccessDeniedException('You not have permitted to access this field');
        }

        return $next($value, $arguments, $context, $info);
    }
}
