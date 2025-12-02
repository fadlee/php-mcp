<?php

namespace PocketBaseMCP;

class RulesReference {
    public static function get() {
        return [
            'description' => 'PocketBase API Rules and Filters Reference',
            
            'rule_types' => [
                'listRule' => 'Controls who can list/search records',
                'viewRule' => 'Controls who can view a single record',
                'createRule' => 'Controls who can create records',
                'updateRule' => 'Controls who can update records',
                'deleteRule' => 'Controls who can delete records',
                'options.manageRule' => '(Auth collections only) Allow managing other users data'
            ],
            
            'rule_values' => [
                'null' => 'Locked - Only superusers can perform this action (default)',
                '""' => 'Public - Anyone can perform this action (superusers, users, guests)',
                'filter_expression' => 'Only requests satisfying the filter can perform this action'
            ],
            
            'response_behavior' => [
                'listRule_unsatisfied' => '200 with empty items',
                'viewRule_unsatisfied' => '404 Not Found',
                'createRule_unsatisfied' => '400 Bad Request',
                'updateRule_unsatisfied' => '404 Not Found',
                'deleteRule_unsatisfied' => '404 Not Found',
                'locked_rule' => '403 Forbidden (if not superuser)'
            ],
            
            'filter_syntax' => [
                'format' => 'OPERAND OPERATOR OPERAND',
                'operators' => [
                    '=' => 'Equal',
                    '!=' => 'NOT equal',
                    '>' => 'Greater than',
                    '>=' => 'Greater than or equal',
                    '<' => 'Less than',
                    '<=' => 'Less than or equal',
                    '~' => 'Like/Contains (auto wraps in % for wildcard)',
                    '!~' => 'NOT Like/Contains',
                    '?=' => 'Any/At least one of Equal (for arrays)',
                    '?!=' => 'Any/At least one of NOT equal',
                    '?>' => 'Any/At least one of Greater than',
                    '?>=' => 'Any/At least one of Greater than or equal',
                    '?<' => 'Any/At least one of Less than',
                    '?<=' => 'Any/At least one of Less than or equal',
                    '?~' => 'Any/At least one of Like/Contains',
                    '?!~' => 'Any/At least one of NOT Like/Contains'
                ],
                'logical' => [
                    '&&' => 'AND',
                    '||' => 'OR',
                    '(...)' => 'Grouping'
                ],
                'comments' => '// Single line comments supported'
            ],
            
            'available_fields' => [
                'collection_fields' => 'All your collection schema fields, including nested relations (e.g., someRelField.status)',
                
                '@request' => [
                    '@request.context' => 'Context: default, oauth2, otp, password, realtime, protectedFile',
                    '@request.method' => 'HTTP method: GET, POST, PATCH, DELETE',
                    '@request.headers.*' => 'Request headers (lowercase, - becomes _). Ex: @request.headers.x_token',
                    '@request.query.*' => 'Query parameters as strings. Ex: @request.query.page',
                    '@request.auth.*' => 'Current authenticated user. Ex: @request.auth.id, @request.auth.email',
                    '@request.body.*' => 'Submitted body parameters. Ex: @request.body.title'
                ],
                
                '@collection' => [
                    'usage' => 'Target other collections not directly related',
                    'syntax' => '@collection.collectionName.field',
                    'alias' => '@collection.collectionName:alias.field (for multiple joins)',
                    'example' => '@collection.news.categoryId ?= categoryId && @collection.news.author ?= @request.auth.id'
                ]
            ],
            
            'modifiers' => [
                ':isset' => [
                    'description' => 'Check if client submitted a field (only for @request.*)',
                    'example' => '@request.body.role:isset = false // disallow submitting role'
                ],
                ':changed' => [
                    'description' => 'Check if client submitted AND changed a field (only for @request.body.*)',
                    'example' => '@request.body.role:changed = false // disallow changing role'
                ],
                ':length' => [
                    'description' => 'Check array field length (file, select, relation)',
                    'example' => 'someRelationField:length = 2'
                ],
                ':each' => [
                    'description' => 'Apply condition on each array item (select, file, relation)',
                    'example' => 'someSelectField:each ~ "pb_%" // all must have pb_ prefix'
                ],
                ':lower' => [
                    'description' => 'Case-insensitive comparison (lowercase)',
                    'example' => 'title:lower = "test" // matches Test, TEST, tEsT'
                ]
            ],
            
            'macros' => [
                '@now' => 'Current datetime (UTC)',
                '@second' => 'Current second (0-59)',
                '@minute' => 'Current minute (0-59)',
                '@hour' => 'Current hour (0-23)',
                '@weekday' => 'Current weekday (0-6)',
                '@day' => 'Current day number',
                '@month' => 'Current month number',
                '@year' => 'Current year number',
                '@yesterday' => 'Yesterday datetime',
                '@tomorrow' => 'Tomorrow datetime',
                '@todayStart' => 'Beginning of today',
                '@todayEnd' => 'End of today',
                '@monthStart' => 'Beginning of current month',
                '@monthEnd' => 'End of current month',
                '@yearStart' => 'Beginning of current year',
                '@yearEnd' => 'End of current year'
            ],
            
            'functions' => [
                'geoDistance(lonA, latA, lonB, latB)' => [
                    'description' => 'Calculate Haversine distance between 2 points in kilometres',
                    'example' => 'geoDistance(address.lon, address.lat, 23.32, 42.69) < 25'
                ]
            ],
            
            'common_examples' => [
                'authenticated_only' => '@request.auth.id != ""',
                'owner_only' => '@request.auth.id = owner',
                'active_records' => 'status = "active"',
                'auth_and_active' => '@request.auth.id != "" && status = "active"',
                'auth_or_pending' => '@request.auth.id != "" && (status = "active" || status = "pending")',
                'in_allowed_list' => '@request.auth.id != "" && allowed_users.id ?= @request.auth.id',
                'title_prefix' => 'title ~ "Lorem%"',
                'no_role_change' => '@request.body.role:changed = false',
                'future_date' => '@request.body.publishDate >= @now',
                'nearby_location' => 'geoDistance(location.lon, location.lat, @request.query.lon, @request.query.lat) < 10'
            ],
            
            'tips' => [
                'Rules act as filters - unsatisfied rules filter out records, not just deny access',
                'Superusers bypass all rules',
                'Use null (not "null" string) for admin-only access',
                'Empty string "" allows public access',
                'Test rules in PocketBase Admin UI with autocomplete',
                'Combine with indexes for better query performance'
            ]
        ];
    }
}
