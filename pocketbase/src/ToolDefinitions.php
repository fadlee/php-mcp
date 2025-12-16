<?php

namespace PocketBaseMCP;

class ToolDefinitions {
    public static function get() {
        return [
            // Health
            [
                'name' => 'health',
                'description' => 'Check PocketBase server health status',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => new \stdClass()
                ]
            ],
            // Collections
            [
                'name' => 'list_collections',
                'description' => 'List all collections',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => new \stdClass()
                ]
            ],
            [
                'name' => 'view_collection',
                'description' => 'View a collection by name or ID',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'collection' => [
                            'type' => 'string',
                            'description' => 'Collection name or ID'
                        ]
                    ],
                    'required' => ['collection']
                ]
            ],
            [
                'name' => 'get_field_schema_reference',
                'description' => 'Get PocketBase collection field schema reference. Call this before create_collection to see correct field syntax for all field types.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => new \stdClass()
                ]
            ],
            [
                'name' => 'create_collection',
                'description' => 'Create a new collection. Call get_field_schema_reference first to see correct field syntax.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => [
                            'type' => 'string',
                            'description' => 'Collection name'
                        ],
                        'type' => [
                            'type' => 'string',
                            'description' => 'Collection type: base, auth, or view',
                            'enum' => ['base', 'auth', 'view']
                        ],
                        'fields' => [
                            'type' => 'array',
                            'description' => 'Array of field definitions',
                            'items' => [
                                'type' => 'object',
                                'description' => 'Field definition object'
                            ]
                        ],
                        'listRule' => [
                            'type' => 'string',
                            'description' => 'List API rule (null=disallow, ""=allow all)'
                        ],
                        'viewRule' => [
                            'type' => 'string',
                            'description' => 'View API rule'
                        ],
                        'createRule' => [
                            'type' => 'string',
                            'description' => 'Create API rule'
                        ],
                        'updateRule' => [
                            'type' => 'string',
                            'description' => 'Update API rule'
                        ],
                        'deleteRule' => [
                            'type' => 'string',
                            'description' => 'Delete API rule'
                        ],
                        'indexes' => [
                            'type' => 'array',
                            'description' => 'SQL index definitions',
                            'items' => [
                                'type' => 'string',
                                'description' => 'SQL CREATE INDEX statement'
                            ]
                        ]
                    ],
                    'required' => ['name', 'fields']
                ]
            ],
            [
                'name' => 'update_collection',
                'description' => 'Update an existing collection. For schema changes (add/remove fields) you must send a valid payload. If updating fields, provide the full fields array (existing fields + your changes), not just the new field.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'collection' => [
                            'type' => 'string',
                            'description' => 'Collection name or ID'
                        ],
                        'data' => [
                            'type' => 'object',
                            'description' => 'Collection data to update. For schema changes, include fields as a full array (existing + new/removed). If omitted, you may pass update properties directly at the top-level (besides collection).'
                        ],
                        'fields' => [
                            'type' => 'array',
                            'description' => 'Optional shorthand to update the collection fields (schema). Must be the full fields array (existing + changes).'
                        ],
                        'indexes' => [
                            'type' => 'array',
                            'description' => 'Optional indexes definitions. Note that view collections may not support indexes.'
                        ],
                        'listRule' => [
                            'type' => ['string', 'null'],
                            'description' => 'List API rule (null=disallow, ""=allow all)'
                        ],
                        'viewRule' => [
                            'type' => ['string', 'null'],
                            'description' => 'View API rule'
                        ],
                        'createRule' => [
                            'type' => ['string', 'null'],
                            'description' => 'Create API rule'
                        ],
                        'updateRule' => [
                            'type' => ['string', 'null'],
                            'description' => 'Update API rule'
                        ],
                        'deleteRule' => [
                            'type' => ['string', 'null'],
                            'description' => 'Delete API rule'
                        ]
                    ],
                    'required' => ['collection']
                ]
            ],
            [
                'name' => 'delete_collection',
                'description' => 'Delete a collection',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'collection' => [
                            'type' => 'string',
                            'description' => 'Collection name or ID'
                        ]
                    ],
                    'required' => ['collection']
                ]
            ],
            // Rules
            [
                'name' => 'get_rules_reference',
                'description' => 'Get API rules syntax reference. Call this BEFORE update_collection_rules to understand filter syntax, operators, modifiers, and macros.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => new \stdClass()
                ]
            ],
            [
                'name' => 'update_collection_rules',
                'description' => 'Update collection API rules (access control). Call get_rules_reference first for syntax. Use null for admin-only, "" for public, or filter expression.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'collection' => [
                            'type' => 'string',
                            'description' => 'Collection name or ID'
                        ],
                        'listRule' => [
                            'type' => ['string', 'null'],
                            'description' => 'Rule for listing records. null=admin only, ""=public, or filter expression'
                        ],
                        'viewRule' => [
                            'type' => ['string', 'null'],
                            'description' => 'Rule for viewing single record'
                        ],
                        'createRule' => [
                            'type' => ['string', 'null'],
                            'description' => 'Rule for creating records'
                        ],
                        'updateRule' => [
                            'type' => ['string', 'null'],
                            'description' => 'Rule for updating records'
                        ],
                        'deleteRule' => [
                            'type' => ['string', 'null'],
                            'description' => 'Rule for deleting records'
                        ]
                    ],
                    'required' => ['collection']
                ]
            ],
            // Records
            [
                'name' => 'list_records',
                'description' => 'List records from a collection with optional filtering, sorting, and pagination',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'collection' => [
                            'type' => 'string',
                            'description' => 'Collection name or ID'
                        ],
                        'page' => [
                            'type' => 'integer',
                            'description' => 'Page number (default: 1)'
                        ],
                        'perPage' => [
                            'type' => 'integer',
                            'description' => 'Records per page (default: 30, max: 500)'
                        ],
                        'sort' => [
                            'type' => 'string',
                            'description' => 'Sort field(s), prefix with - for DESC (e.g., -created,title)'
                        ],
                        'filter' => [
                            'type' => 'string',
                            'description' => 'Filter expression (e.g., title~"test" && created>"2022-01-01")'
                        ],
                        'expand' => [
                            'type' => 'string',
                            'description' => 'Relations to expand (e.g., relField1,relField2.subRelField)'
                        ],
                        'fields' => [
                            'type' => 'string',
                            'description' => 'Comma-separated fields to return'
                        ]
                    ],
                    'required' => ['collection']
                ]
            ],
            [
                'name' => 'view_record',
                'description' => 'View a single record by ID',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'collection' => [
                            'type' => 'string',
                            'description' => 'Collection name or ID'
                        ],
                        'id' => [
                            'type' => 'string',
                            'description' => 'Record ID'
                        ],
                        'expand' => [
                            'type' => 'string',
                            'description' => 'Relations to expand'
                        ],
                        'fields' => [
                            'type' => 'string',
                            'description' => 'Comma-separated fields to return'
                        ]
                    ],
                    'required' => ['collection', 'id']
                ]
            ],
            [
                'name' => 'create_record',
                'description' => 'Create a new record in a collection',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'collection' => [
                            'type' => 'string',
                            'description' => 'Collection name or ID'
                        ],
                        'data' => [
                            'type' => 'object',
                            'description' => 'Record data (field values)'
                        ],
                        'expand' => [
                            'type' => 'string',
                            'description' => 'Relations to expand in response'
                        ]
                    ],
                    'required' => ['collection', 'data']
                ]
            ],
            [
                'name' => 'update_record',
                'description' => 'Update an existing record',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'collection' => [
                            'type' => 'string',
                            'description' => 'Collection name or ID'
                        ],
                        'id' => [
                            'type' => 'string',
                            'description' => 'Record ID'
                        ],
                        'data' => [
                            'type' => 'object',
                            'description' => 'Record data to update'
                        ],
                        'expand' => [
                            'type' => 'string',
                            'description' => 'Relations to expand in response'
                        ]
                    ],
                    'required' => ['collection', 'id', 'data']
                ]
            ],
            [
                'name' => 'delete_record',
                'description' => 'Delete a record',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'collection' => [
                            'type' => 'string',
                            'description' => 'Collection name or ID'
                        ],
                        'id' => [
                            'type' => 'string',
                            'description' => 'Record ID'
                        ]
                    ],
                    'required' => ['collection', 'id']
                ]
            ]
        ];
    }
}
