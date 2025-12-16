<?php

namespace PocketBaseMCP;

class PocketBaseMCPServer {
    private $http;

    public function __construct($baseUrl, $token = null, $email = null, $password = null) {
        $this->http = new HttpClient($baseUrl, $token);

        if (!$token && $email && $password) {
            $this->http->authenticate($email, $password);
        }
    }

    public function handleRequest($request) {
        $method = $request['method'] ?? '';
        $params = $request['params'] ?? [];
        $id = $request['id'] ?? null;

        switch ($method) {
            case 'initialize':
                return $this->initialize($id);
            case 'tools/list':
                return $this->listTools($id);
            case 'tools/call':
                return $this->callTool($params, $id);
            case 'resources/list':
                return $this->listResources($id);
            case 'resources/read':
                return $this->readResource($params, $id);
            default:
                return $this->error($id, -32601, "Method not found: $method");
        }
    }

    private function initialize($id) {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => [
                'protocolVersion' => '2024-11-05',
                'capabilities' => [
                    'tools' => new \stdClass(),
                    'resources' => new \stdClass()
                ],
                'serverInfo' => [
                    'name' => 'pocketbase-mcp-server',
                    'version' => '1.0.0'
                ]
            ]
        ];
    }

    private function listTools($id) {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => [
                'tools' => ToolDefinitions::get()
            ]
        ];
    }

    private function callTool($params, $id) {
        $toolName = $params['name'] ?? '';
        $args = $params['arguments'] ?? [];

        try {
            switch ($toolName) {
                case 'health':
                    $result = $this->health();
                    break;
                case 'get_field_schema_reference':
                    $result = FieldSchemaReference::get();
                    break;
                case 'list_collections':
                    $result = $this->listCollections();
                    break;
                case 'view_collection':
                    $result = $this->viewCollection($args['collection']);
                    break;
                case 'create_collection':
                    $result = $this->createCollection($args);
                    break;
                case 'update_collection':
                    $collection = $args['collection'] ?? '';
                    if (empty($collection)) {
                        return $this->error($id, -32602, 'Missing required parameter: collection');
                    }

                    $data = $args['data'] ?? null;
                    if ($data === null) {
                        $data = $args;
                        unset($data['collection']);
                    } else {
                        foreach (['fields', 'indexes', 'listRule', 'viewRule', 'createRule', 'updateRule', 'deleteRule'] as $k) {
                            if (array_key_exists($k, $args) && !array_key_exists($k, $data)) {
                                $data[$k] = $args[$k];
                            }
                        }
                    }

                    if (!is_array($data)) {
                        return $this->error($id, -32602, 'Invalid parameter: data must be an object');
                    }

                    if (empty($data)) {
                        return $this->error(
                            $id,
                            -32602,
                            'Missing update payload. Provide update properties under data or at top-level (besides collection). For schema changes, send fields as the full fields array (existing fields + your changes).'
                        );
                    }

                    if (array_key_exists('fields', $data) && !is_array($data['fields'])) {
                        return $this->error(
                            $id,
                            -32602,
                            'Invalid payload: fields must be an array. For schema changes, send the full fields array (existing fields + your changes), not just a single new field.'
                        );
                    }

                    $result = $this->updateCollection($collection, $data);
                    break;
                case 'delete_collection':
                    $result = $this->deleteCollection($args['collection']);
                    break;
                case 'get_rules_reference':
                    $result = RulesReference::get();
                    break;
                case 'update_collection_rules':
                    $result = $this->updateCollectionRules($args);
                    break;
                case 'list_records':
                    $result = $this->listRecords($args['collection'], $args);
                    break;
                case 'view_record':
                    $result = $this->viewRecord($args['collection'], $args['id'], $args);
                    break;
                case 'create_record':
                    $result = $this->createRecord($args['collection'], $args['data'], $args);
                    break;
                case 'update_record':
                    $result = $this->updateRecord($args['collection'], $args['id'], $args['data'], $args);
                    break;
                case 'delete_record':
                    $result = $this->deleteRecord($args['collection'], $args['id']);
                    break;
                default:
                    return $this->error($id, -32602, "Unknown tool: $toolName");
            }

            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'result' => [
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                        ]
                    ]
                ]
            ];
        } catch (\Exception $e) {
            return $this->error($id, -32603, $e->getMessage());
        }
    }

    // Health
    private function health() {
        return $this->http->request('GET', '/api/health');
    }

    // Collections
    private function listCollections() {
        return $this->http->request('GET', '/api/collections');
    }

    private function viewCollection($collection) {
        return $this->http->request('GET', '/api/collections/' . urlencode($collection));
    }

    private function createCollection($data) {
        $payload = [
            'name' => $data['name'],
            'type' => $data['type'] ?? 'base',
            'fields' => $data['fields'] ?? []
        ];

        foreach (['listRule', 'viewRule', 'createRule', 'updateRule', 'deleteRule'] as $rule) {
            if (isset($data[$rule])) {
                $payload[$rule] = $data[$rule];
            }
        }

        if (!empty($data['indexes'])) {
            $payload['indexes'] = $data['indexes'];
        }

        return $this->http->request('POST', '/api/collections', $payload);
    }

    private function updateCollection($collection, $data) {
        return $this->http->request('PATCH', '/api/collections/' . urlencode($collection), $data);
    }

    private function deleteCollection($collection) {
        $this->http->request('DELETE', '/api/collections/' . urlencode($collection));
        return ['message' => 'Collection deleted successfully'];
    }

    private function updateCollectionRules($args) {
        $collection = $args['collection'];
        $payload = [];

        foreach (['listRule', 'viewRule', 'createRule', 'updateRule', 'deleteRule'] as $rule) {
            if (array_key_exists($rule, $args)) {
                $payload[$rule] = $args[$rule];
            }
        }

        if (empty($payload)) {
            return ['message' => 'No rules specified to update'];
        }

        $result = $this->http->request('PATCH', '/api/collections/' . urlencode($collection), $payload);

        return [
            'message' => 'Collection rules updated successfully',
            'collection' => $collection,
            'updatedRules' => array_keys($payload),
            'currentRules' => [
                'listRule' => $result['listRule'] ?? null,
                'viewRule' => $result['viewRule'] ?? null,
                'createRule' => $result['createRule'] ?? null,
                'updateRule' => $result['updateRule'] ?? null,
                'deleteRule' => $result['deleteRule'] ?? null
            ]
        ];
    }

    // Records
    private function listRecords($collection, $options) {
        $query = [];
        foreach (['page', 'perPage', 'sort', 'filter', 'expand', 'fields'] as $key) {
            if (!empty($options[$key])) {
                $query[$key] = $options[$key];
            }
        }
        return $this->http->request('GET', '/api/collections/' . urlencode($collection) . '/records', null, $query);
    }

    private function viewRecord($collection, $recordId, $options = []) {
        $query = [];
        foreach (['expand', 'fields'] as $key) {
            if (!empty($options[$key])) {
                $query[$key] = $options[$key];
            }
        }
        return $this->http->request('GET', '/api/collections/' . urlencode($collection) . '/records/' . urlencode($recordId), null, $query);
    }

    private function createRecord($collection, $data, $options = []) {
        $query = [];
        if (!empty($options['expand'])) {
            $query['expand'] = $options['expand'];
        }
        return $this->http->request('POST', '/api/collections/' . urlencode($collection) . '/records', $data, $query);
    }

    private function updateRecord($collection, $recordId, $data, $options = []) {
        $query = [];
        if (!empty($options['expand'])) {
            $query['expand'] = $options['expand'];
        }
        return $this->http->request('PATCH', '/api/collections/' . urlencode($collection) . '/records/' . urlencode($recordId), $data, $query);
    }

    private function deleteRecord($collection, $recordId) {
        $this->http->request('DELETE', '/api/collections/' . urlencode($collection) . '/records/' . urlencode($recordId));
        return ['message' => 'Record deleted successfully'];
    }

    // Resources
    private function listResources($id) {
        try {
            $collections = $this->listCollections();
            $items = $collections['items'] ?? [];

            $resources = array_map(function($col) {
                return [
                    'uri' => 'pocketbase://collection/' . $col['name'],
                    'name' => $col['name'],
                    'description' => 'Collection: ' . $col['name'] . ' (type: ' . $col['type'] . ')',
                    'mimeType' => 'application/json'
                ];
            }, $items);

            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'result' => ['resources' => $resources]
            ];
        } catch (\Exception $e) {
            return $this->error($id, -32603, $e->getMessage());
        }
    }

    private function readResource($params, $id) {
        try {
            $uri = $params['uri'] ?? '';
            if (preg_match('#^pocketbase://collection/(.+)$#', $uri, $matches)) {
                $collection = $matches[1];
                $data = $this->viewCollection($collection);

                return [
                    'jsonrpc' => '2.0',
                    'id' => $id,
                    'result' => [
                        'contents' => [
                            [
                                'uri' => $uri,
                                'mimeType' => 'application/json',
                                'text' => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                            ]
                        ]
                    ]
                ];
            }

            return $this->error($id, -32602, "Invalid resource URI");
        } catch (\Exception $e) {
            return $this->error($id, -32603, $e->getMessage());
        }
    }

    private function error($id, $code, $message) {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => $code,
                'message' => $message
            ]
        ];
    }
}
