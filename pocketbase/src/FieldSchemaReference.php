<?php

namespace PocketBaseMCP;

class FieldSchemaReference {
    public static function get() {
        return [
            'description' => 'PocketBase Collection Field Schema Reference',
            'common_properties' => [
                'name' => 'string (required) - Unique field name',
                'type' => 'string (required) - Field type',
                'required' => 'boolean - Field must have a value',
                'hidden' => 'boolean - Hide from API response',
                'presentable' => 'boolean - Show in relation preview labels',
                'system' => 'boolean - Prevents renaming/deletion'
            ],
            'field_types' => [
                'text' => [
                    'description' => 'String values. Zero default: ""',
                    'options' => [
                        'min' => 'number - Minimum characters',
                        'max' => 'number - Maximum characters (default: 5000)',
                        'pattern' => 'string - Regex pattern validation',
                        'autogeneratePattern' => 'string - Auto-generate on create, e.g. "[a-z0-9]{8}"'
                    ],
                    'example' => ['name' => 'title', 'type' => 'text', 'required' => true, 'max' => 255]
                ],
                'bool' => [
                    'description' => 'True/false values. Zero default: false',
                    'options' => [],
                    'example' => ['name' => 'isActive', 'type' => 'bool']
                ],
                'number' => [
                    'description' => 'Numeric/float64 values. Zero default: 0',
                    'options' => [
                        'min' => 'number - Minimum value',
                        'max' => 'number - Maximum value',
                        'onlyInt' => 'boolean - Allow only integers',
                        'noDecimal' => 'boolean - No decimal places'
                    ],
                    'example' => ['name' => 'price', 'type' => 'number', 'required' => true, 'min' => 0]
                ],
                'email' => [
                    'description' => 'Email addresses with validation. Zero default: ""',
                    'options' => [
                        'exceptDomains' => 'array - Blocked domains, e.g. ["gmail.com"]',
                        'onlyDomains' => 'array - Allowed domains only'
                    ],
                    'example' => ['name' => 'email', 'type' => 'email', 'required' => true]
                ],
                'url' => [
                    'description' => 'URL strings with validation. Zero default: ""',
                    'options' => [],
                    'example' => ['name' => 'website', 'type' => 'url']
                ],
                'editor' => [
                    'description' => 'HTML formatted text. Zero default: ""',
                    'options' => [],
                    'example' => ['name' => 'content', 'type' => 'editor']
                ],
                'date' => [
                    'description' => 'Date values (YYYY-MM-DD format). Zero default: ""',
                    'options' => [],
                    'example' => ['name' => 'birthDate', 'type' => 'date']
                ],
                'autodate' => [
                    'description' => 'Auto-sets on create/update. Zero default: ""',
                    'options' => [
                        'onCreate' => 'boolean - Auto-set on record create (default: true)',
                        'onUpdate' => 'boolean - Auto-set on record update (default: true)'
                    ],
                    'example' => ['name' => 'created', 'type' => 'autodate', 'onCreate' => true, 'onUpdate' => false]
                ],
                'select' => [
                    'description' => 'Single/multiple predefined values. Zero default: "" or []',
                    'options' => [
                        'values' => 'array (REQUIRED) - Options, e.g. ["active", "inactive"]',
                        'maxSelect' => 'number - 1 for single, 2+ for multiple (default: 1)'
                    ],
                    'example' => ['name' => 'status', 'type' => 'select', 'values' => ['draft', 'published', 'archived'], 'maxSelect' => 1]
                ],
                'file' => [
                    'description' => 'File uploads. Zero default: []',
                    'options' => [
                        'maxSelect' => 'number - Max files allowed (default: 1)',
                        'maxSize' => 'number - Max file size in bytes (0 = unlimited)',
                        'mimeTypes' => 'array - Allowed MIME types, e.g. ["image/jpeg", "image/png"]',
                        'thumbs' => 'array - Thumbnail sizes, e.g. ["100x100", "300x300"]',
                        'protected' => 'boolean - Requires auth to access'
                    ],
                    'example' => ['name' => 'avatar', 'type' => 'file', 'maxSelect' => 1, 'maxSize' => 5242880, 'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp']]
                ],
                'relation' => [
                    'description' => 'References records from other collections. Zero default: "" or []',
                    'options' => [
                        'collectionId' => 'string (REQUIRED) - Target collection ID',
                        'maxSelect' => 'number - 1 for single, 2+ for multiple (default: 1)',
                        'cascadeDelete' => 'boolean - Delete when related record deleted'
                    ],
                    'example' => ['name' => 'author', 'type' => 'relation', 'collectionId' => 'users_collection_id', 'maxSelect' => 1]
                ],
                'json' => [
                    'description' => 'Any serialized JSON. Zero default: null (can be nullable)',
                    'options' => [],
                    'example' => ['name' => 'metadata', 'type' => 'json']
                ],
                'geopoint' => [
                    'description' => 'Geographic coordinates {lon, lat}. Zero default: {lon: 0, lat: 0}',
                    'options' => [],
                    'example' => ['name' => 'location', 'type' => 'geopoint']
                ]
            ],
            'api_rules' => [
                'description' => 'Access control rules for collection endpoints',
                'values' => [
                    'null' => 'Disallow access (admin only)',
                    '""' => 'Allow all (public access)',
                    '"@request.auth.id != \"\""' => 'Authenticated users only',
                    '"@request.auth.id = owner"' => 'Record owner only'
                ],
                'rules' => ['listRule', 'viewRule', 'createRule', 'updateRule', 'deleteRule']
            ],
            'complete_example' => [
                'name' => 'posts',
                'type' => 'base',
                'listRule' => '',
                'viewRule' => '',
                'createRule' => '@request.auth.id != ""',
                'updateRule' => '@request.auth.id = author',
                'deleteRule' => '@request.auth.id = author',
                'fields' => [
                    ['name' => 'title', 'type' => 'text', 'required' => true, 'max' => 255],
                    ['name' => 'content', 'type' => 'editor'],
                    ['name' => 'status', 'type' => 'select', 'values' => ['draft', 'published'], 'maxSelect' => 1],
                    ['name' => 'author', 'type' => 'relation', 'collectionId' => 'users_id', 'required' => true],
                    ['name' => 'images', 'type' => 'file', 'maxSelect' => 5, 'mimeTypes' => ['image/jpeg', 'image/png']],
                    ['name' => 'created', 'type' => 'autodate', 'onCreate' => true, 'onUpdate' => false]
                ]
            ]
        ];
    }
}
