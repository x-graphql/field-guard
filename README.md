Field Guard
===========

[Middleware](https://github.com/x-graphql/field-middleware) for adding security layer to GraphQL schema

![unit tests](https://github.com/x-graphql/field-guard/actions/workflows/unit_tests.yml/badge.svg)
[![codecov](https://codecov.io/gh/x-graphql/field-guard/graph/badge.svg?token=a76EAc7BUy)](https://codecov.io/gh/x-graphql/field-guard)

Getting Started
---------------

Install this package via [Composer](https://getcomposer.org)

```shell
composer require x-graphql/field-guard
```

Usages
------

Create permissions array mapping object type name, and it fields with rule, rule can be
boolean or instance of `XGraphQL\FieldGuard\RuleInterface`:

```php
use GraphQL\Type\Definition\ResolveInfo;
use XGraphQL\FieldGuard\RuleInterface;

$isAdminRule = new class implements RuleInterface {
    public function allows(mixed $value, array $args, mixed $context, ResolveInfo $info) : bool{
        return $context->isAdmin();
    }
    
    public function shouldRemember(mixed $value,array $args,mixed $context,ResolveInfo $info) : bool{
        return true;
    }
};

$permissions = [
    'Query' => [
        'getUser' => true, /// all user can get user.
        'getBook' => false, /// deny all user to get book.
    ],
    'Mutation' => [
        'createUser' => $isAdminRule, /// only admin user can create user.
    ]   
];
```

Then create middleware with `$permissions` above and apply to schema:

```php
use XGraphQL\FieldMiddleware\FieldMiddleware;
use XGraphQL\FieldGuard\FieldGuardMiddleware;

$schema = ...
$guardMiddleware = new FieldGuardMiddleware($permissions);

FieldMiddleware::apply($schema, [$guardMiddleware]);
```

Credits
-------

Created by [Minh Vuong](https://github.com/vuongxuongminh)
