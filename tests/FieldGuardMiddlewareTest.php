<?php

declare(strict_types=1);

namespace XGraphQL\FieldGuard\Test;

use GraphQL\Error\DebugFlag;
use GraphQL\Error\Error;
use GraphQL\GraphQL;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use XGraphQL\FieldGuard\FieldGuardMiddleware;
use XGraphQL\FieldGuard\RuleInterface;
use XGraphQL\FieldMiddleware\FieldMiddleware;

class FieldGuardMiddlewareTest extends TestCase
{
    public function testDefaultDeny(): void
    {
        $schema = $this->createSchema();
        $middleware = new FieldGuardMiddleware([], true);
        FieldMiddleware::apply($schema, [$middleware]);

        $this->expectException(Error::class);
        $this->expectExceptionMessage('You not have permitted to access this field');

        GraphQL::executeQuery($schema, '{ dummyString }')->toArray(DebugFlag::RETHROW_INTERNAL_EXCEPTIONS);
    }

    #[DataProvider('rulesProvider')]
    public function testRule(RuleInterface|bool $rule, string $query, array $expectingResult): void
    {
        $schema = $this->createSchema();
        $middleware = new FieldGuardMiddleware(
            [
                'DummyObject' => ['dummyObjectString' => $rule],
                'Query' => ['dummyString' => $rule]
            ]
        );
        FieldMiddleware::apply($schema, [$middleware]);

        $result = GraphQL::executeQuery($schema, $query);

        $this->assertEquals($expectingResult, $result->toArray());
    }

    public static function rulesProvider(): array
    {
        return [
            'allows boolean rule' => [
                true,
                '{ dummyString }',
                [
                    'data' => [
                        'dummyString' => 'Dummy',
                    ]
                ]
            ],
            'deny boolean rule' => [
                false,
                '{ dummyObject { dummyObjectString } }',
                [
                    'data' => [
                        'dummyObject' => [
                            'dummyObjectString' => null
                        ],
                    ],
                    'errors' => [
                        [
                            'message' => 'You not have permitted to access this field',
                            'locations' => [
                                [
                                    'line' => 1,
                                    'column' => 17
                                ]
                            ],
                            'path' => ['dummyObject', 'dummyObjectString'],
                        ]
                    ]
                ]
            ],
            'deny rule' => [
                self::createRule(false),
                '{ dummyString }',
                [
                    'data' => [
                        'dummyString' => null,
                    ],
                    'errors' => [
                        [
                            'message' => 'You not have permitted to access this field',
                            'locations' => [
                                [
                                    'line' => 1,
                                    'column' => 3,
                                ]
                            ],
                            'path' => ['dummyString']
                        ]
                    ]
                ]
            ],
            'allows rule' => [
                self::createRule(true),
                '{ dummyObject { dummyObjectString } }',
                [
                    'data' => [
                        'dummyObject' => [
                            'dummyObjectString' => 'Dummy Object String'
                        ],
                    ],
                ]
            ],
        ];
    }

    public function testRememberRule(): void
    {
        $schema = $this->createSchema();
        $rule = $this->createMock(RuleInterface::class);
        $rule->expects($this->once())->method('allows')->willReturn(true);
        $rule->expects($this->once())->method('shouldRemember')->willReturn(true);

        FieldMiddleware::apply($schema, [new FieldGuardMiddleware(['DummyObject' => ['dummyObjectString' => $rule]])]);

        $result = GraphQL::executeQuery(
            $schema,
            <<<'GQL'
query {
    dummyObject {
        dummyObjectString
        dummyObject {
            dummyObjectString
        }
    }
}
GQL
        );

        $this->assertEquals(
            [
                'data' => [
                    'dummyObject' => [
                        'dummyObjectString' => 'Dummy Object String',
                        'dummyObject' => [
                            'dummyObjectString' => 'Dummy Object String Inner'
                        ]
                    ]
                ]
            ],
            $result->toArray()
        );
    }

    private function createSchema(): Schema
    {
        $dummyObject = new ObjectType([
            'name' => 'DummyObject',
            'fields' => [
                'dummyObjectString' => Type::string(),
            ],
        ]);

        $dummyObject->config['fields']['dummyObject'] = $dummyObject;

        return new Schema([
            'query' => new ObjectType([
                'name' => 'Query',
                'fields' => [
                    'dummyString' => [
                        'type' => Type::string(),
                        'resolve' => fn() => 'Dummy',
                    ],
                    'dummyObject' => [
                        'type' => $dummyObject,
                        'resolve' => fn() => [
                            'dummyObjectString' => 'Dummy Object String',
                            'dummyObject' => [
                                'dummyObjectString' => 'Dummy Object String Inner',
                            ]
                        ]
                    ]
                ],
            ]),
        ]);
    }

    private static function createRule(bool $canAccess): RuleInterface
    {
        return new class($canAccess) implements RuleInterface {
            public function __construct(private readonly bool $canAccess)
            {
            }

            public function allows(mixed $value, array $args, mixed $context, ResolveInfo $info): bool
            {
                return $this->canAccess;
            }

            public function shouldRemember(mixed $value, array $args, mixed $context, ResolveInfo $info): bool
            {
                return true;
            }
        };
    }
}
