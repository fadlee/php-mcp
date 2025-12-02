<?php
/**
 * PocketBase MCP Server
 * Implements Model Context Protocol for PocketBase operations
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

class PocketBaseMCPServer {
    private $baseUrl;
    private $token;
    
    public function __construct($baseUrl, $token = null, $email = null, $password = null) {
        $this->baseUrl = rtrim($baseUrl, '/');
        
        if ($token) {
            $this->token = $token;
        } elseif ($email && $password) {
            $this->token = $this->authenticate($email, $password);
        }
    }
    
    private function authenticate($email, $password) {
        $response = $this->request('POST', '/api/collections/_superusers/auth-with-password', [
            'identity' => $email,
            'password' => $password
        ], [], false);
        
        return $response['token'] ?? null;
    }
    
    private function request($method, $endpoint, $data = null, $query = [], $useAuth = true) {
        $url = $this->baseUrl . $endpoint;
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        $headers = ['Content-Type: application/json'];
        if ($useAuth && $this->token) {
            $headers[] = 'Authorization: ' . $this->token;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if ($data !== null && in_array($method, ['POST', 'PATCH', 'PUT'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("HTTP request failed: $error");
        }
        
        $decoded = json_decode($response, true);
        
        if ($httpCode >= 400) {
            $message = $decoded['message'] ?? "HTTP error $httpCode";
            throw new Exception($message);
        }
        
        return $decoded;
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
                    'tools' => new stdClass(),
                    'resources' => new stdClass()
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
                'tools' => [
                    // Health
                    [
                        'name' => 'health',
                        'description' => 'Check PocketBase server health status',
                        'inputSchema' => [
                            'type' => 'object',
                            'properties' => new stdClass()
                        ]
                    ],
                    // Collections
                    [
                        'name' => 'list_collections',
                        'description' => 'List all collections',
                        'inputSchema' => [
                            'type' => 'object',
                            'properties' => new stdClass()
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
                            'properties' => new stdClass()
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
                                    'description' => 'Array of field definitions'
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
                                    'description' => 'SQL index definitions'
                                ]
                            ],
                            'required' => ['name', 'fields']
                        ]
                    ],
                    [
                        'name' => 'update_collection',
                        'description' => 'Update an existing collection',
                        'inputSchema' => [
                            'type' => 'object',
                            'properties' => [
                                'collection' => [
                                    'type' => 'string',
                                    'description' => 'Collection name or ID'
                                ],
                                'data' => [
                                    'type' => 'object',
                                    'description' => 'Collection data to update'
                                ]
                            ],
                            'required' => ['collection', 'data']
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
                ]
            ]
        ];
    }
    
    private function callTool($params, $id) {
        $toolName = $params['name'] ?? '';
        $args = $params['arguments'] ?? [];
        
        try {
            switch ($toolName) {
                // Health
                case 'health':
                    $result = $this->health();
                    break;
                
                // Schema Reference
                case 'get_field_schema_reference':
                    $result = $this->getFieldSchemaReference();
                    break;
                
                // Collections
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
                    $result = $this->updateCollection($args['collection'], $args['data']);
                    break;
                case 'delete_collection':
                    $result = $this->deleteCollection($args['collection']);
                    break;
                
                // Records
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
        } catch (Exception $e) {
            return $this->error($id, -32603, $e->getMessage());
        }
    }
    
    // Health
    private function health() {
        return $this->request('GET', '/api/health');
    }
    
    // Schema Reference
    private function getFieldSchemaReference() {
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
    
    // Collections
    private function listCollections() {
        return $this->request('GET', '/api/collections');
    }
    
    private function viewCollection($collection) {
        return $this->request('GET', '/api/collections/' . urlencode($collection));
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
        
        return $this->request('POST', '/api/collections', $payload);
    }
    
    private function updateCollection($collection, $data) {
        return $this->request('PATCH', '/api/collections/' . urlencode($collection), $data);
    }
    
    private function deleteCollection($collection) {
        $this->request('DELETE', '/api/collections/' . urlencode($collection));
        return ['message' => 'Collection deleted successfully'];
    }
    
    // Records
    private function listRecords($collection, $options) {
        $query = [];
        foreach (['page', 'perPage', 'sort', 'filter', 'expand', 'fields'] as $key) {
            if (!empty($options[$key])) {
                $query[$key] = $options[$key];
            }
        }
        return $this->request('GET', '/api/collections/' . urlencode($collection) . '/records', null, $query);
    }
    
    private function viewRecord($collection, $recordId, $options = []) {
        $query = [];
        foreach (['expand', 'fields'] as $key) {
            if (!empty($options[$key])) {
                $query[$key] = $options[$key];
            }
        }
        return $this->request('GET', '/api/collections/' . urlencode($collection) . '/records/' . urlencode($recordId), null, $query);
    }
    
    private function createRecord($collection, $data, $options = []) {
        $query = [];
        if (!empty($options['expand'])) {
            $query['expand'] = $options['expand'];
        }
        return $this->request('POST', '/api/collections/' . urlencode($collection) . '/records', $data, $query);
    }
    
    private function updateRecord($collection, $recordId, $data, $options = []) {
        $query = [];
        if (!empty($options['expand'])) {
            $query['expand'] = $options['expand'];
        }
        return $this->request('PATCH', '/api/collections/' . urlencode($collection) . '/records/' . urlencode($recordId), $data, $query);
    }
    
    private function deleteRecord($collection, $recordId) {
        $this->request('DELETE', '/api/collections/' . urlencode($collection) . '/records/' . urlencode($recordId));
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
        } catch (Exception $e) {
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
        } catch (Exception $e) {
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

// Main execution
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $request = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode([
            'jsonrpc' => '2.0',
            'id' => null,
            'error' => ['code' => -32700, 'message' => 'Parse error']
        ]);
        exit;
    }
    
    $pbUrl = $_GET['url'] ?? '';
    $pbToken = $_GET['token'] ?? '';
    $pbEmail = $_GET['email'] ?? '';
    $pbPassword = $_GET['password'] ?? '';
    
    if (empty($pbUrl)) {
        echo json_encode([
            'jsonrpc' => '2.0',
            'id' => $request['id'] ?? null,
            'error' => ['code' => -32602, 'message' => 'Missing required parameter: url']
        ]);
        exit;
    }
    
    $server = new PocketBaseMCPServer($pbUrl, $pbToken, $pbEmail, $pbPassword);
    $response = $server->handleRequest($request);
    echo json_encode($response);
} else {
    header('Content-Type: text/html; charset=utf-8');
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . strtok($_SERVER['REQUEST_URI'], '?');
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PocketBase MCP Server</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 900px; margin: 0 auto; padding: 20px; background: #f5f5f5; color: #333; line-height: 1.6; }
        h1 { color: #6366f1; border-bottom: 2px solid #6366f1; padding-bottom: 10px; }
        h2 { color: #4f46e5; margin-top: 30px; }
        h3 { color: #6366f1; margin-top: 20px; font-size: 1em; }
        code { background: #e5e7eb; padding: 2px 6px; border-radius: 4px; font-size: 0.9em; }
        pre { background: #1e293b; color: #e2e8f0; padding: 15px; border-radius: 8px; overflow-x: auto; }
        pre code { background: transparent; color: inherit; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { border: 1px solid #d1d5db; padding: 10px; text-align: left; }
        th { background: #e5e7eb; }
        .required { color: #dc2626; font-weight: bold; }
        .card { background: white; padding: 20px; border-radius: 8px; margin: 15px 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .tool-group { margin-bottom: 10px; }
    </style>
</head>
<body>
    <h1>PocketBase MCP Server</h1>
    <p>Model Context Protocol server for PocketBase operations. Use with <code>mcp-remote</code> package.</p>

    <div class="card">
        <h2>URL Parameters</h2>
        <table>
            <tr><th>Parameter</th><th>Description</th><th>Required</th></tr>
            <tr><td><code>url</code></td><td>PocketBase server URL (e.g., http://127.0.0.1:8090)</td><td class="required">Yes</td></tr>
            <tr><td><code>token</code></td><td>Admin/Superuser token for authentication</td><td>Option 1</td></tr>
            <tr><td><code>email</code></td><td>Superuser email for authentication</td><td>Option 2</td></tr>
            <tr><td><code>password</code></td><td>Superuser password for authentication</td><td>Option 2</td></tr>
        </table>
        <p><small>Use either <code>token</code> OR <code>email</code> + <code>password</code> for authentication. Required for collection management and protected records.</small></p>
    </div>

    <div class="card">
        <h2>MCP Client Configuration</h2>
        <p>Add this to your MCP client configuration (e.g., Claude Desktop):</p>
        <h3>Option 1: With Token</h3>
        <pre><code>{
  "mcpServers": {
    "pocketbase": {
      "command": "npx",
      "args": [
        "mcp-remote",
        "<?= htmlspecialchars($baseUrl) ?>?url=http://127.0.0.1:8090&token=YOUR_ADMIN_TOKEN",
        "--allow-http"
      ]
    }
  }
}</code></pre>
        <h3>Option 2: With Email & Password</h3>
        <pre><code>{
  "mcpServers": {
    "pocketbase": {
      "command": "npx",
      "args": [
        "mcp-remote",
        "<?= htmlspecialchars($baseUrl) ?>?url=http://127.0.0.1:8090&email=admin@example.com&password=YOUR_PASSWORD",
        "--allow-http"
      ]
    }
  }
}</code></pre>
    </div>

    <div class="card">
        <h2>Available Tools</h2>
        
        <h3>Health</h3>
        <table>
            <tr><th>Tool</th><th>Description</th></tr>
            <tr><td><code>health</code></td><td>Check PocketBase server health status</td></tr>
        </table>

        <h3>Collections Management</h3>
        <table>
            <tr><th>Tool</th><th>Description</th></tr>
            <tr><td><code>list_collections</code></td><td>List all collections</td></tr>
            <tr><td><code>view_collection</code></td><td>View a collection by name or ID</td></tr>
            <tr><td><code>create_collection</code></td><td>Create a new collection</td></tr>
            <tr><td><code>update_collection</code></td><td>Update an existing collection</td></tr>
            <tr><td><code>delete_collection</code></td><td>Delete a collection</td></tr>
        </table>

        <h3>Records Management</h3>
        <table>
            <tr><th>Tool</th><th>Description</th></tr>
            <tr><td><code>list_records</code></td><td>List records with filter, sort, pagination</td></tr>
            <tr><td><code>view_record</code></td><td>View a single record by ID</td></tr>
            <tr><td><code>create_record</code></td><td>Create a new record</td></tr>
            <tr><td><code>update_record</code></td><td>Update an existing record</td></tr>
            <tr><td><code>delete_record</code></td><td>Delete a record</td></tr>
        </table>
    </div>

    <div class="card">
        <h2>Filter Syntax</h2>
        <p>The <code>filter</code> parameter supports PocketBase filter syntax:</p>
        <pre><code>// Operators: = != > >= < <= ~ !~ ?= ?!= ?> ?>= ?< ?<= ?~ ?!~
// Logical: && || ()

// Examples:
title = "test"
created >= "2022-01-01 00:00:00"
title ~ "abc" && status = true
(role = "admin" || role = "moderator") && active = true</code></pre>
    </div>

    <div class="card">
        <h2>Resources</h2>
        <p>The server exposes collections as MCP resources with URI format: <code>pocketbase://collection/{collection_name}</code></p>
    </div>

    <div class="card">
        <h2>Getting Admin Token</h2>
        <p>To get an admin token, authenticate via PocketBase API:</p>
        <pre><code>curl -X POST http://127.0.0.1:8090/api/collections/_superusers/auth-with-password \
  -H "Content-Type: application/json" \
  -d '{"identity":"admin@example.com","password":"your-password"}'</code></pre>
        <p>Copy the <code>token</code> from the response.</p>
        <p>For more details, see <a href="https://pocketbase.io/docs/authentication/#api-keys" target="_blank">PocketBase Authentication Documentation</a>.</p>
    </div>

    <div class="card">
        <h2>Example Prompts for Testing</h2>
        
        <h3>Health Check</h3>
        <pre><code>Check if the PocketBase server is healthy</code></pre>

        <h3>Collections</h3>
        <pre><code>List all collections in the PocketBase database

Get details about the "posts" collection

Create a new collection called "tasks" with the following fields:
- title (text, required)
- description (text, optional)
- status (text, optional)
- createdBy (relation to users collection)

Update the "posts" collection to rename it to "articles"

Delete the "tasks" collection</code></pre>

        <h3>Records - List & View</h3>
        <pre><code>List all posts from the "posts" collection with pagination (10 per page)

List posts sorted by creation date (newest first), show only title and author fields

Find posts where title contains "hello" and created date is after 2022-01-01

List posts with related author information expanded

Get a specific post record by ID "abc123"

Get a post with all its related fields expanded</code></pre>

        <h3>Records - Create & Update</h3>
        <pre><code>Create a new post in the "posts" collection:
- title: "My First Post"
- content: "This is my first post"
- status: "published"

Create a task in "tasks" collection with title "Buy groceries"

Update the post "abc123" to change title to "Updated Title" and status to "draft"

Update multiple fields of a record</code></pre>

        <h3>Records - Delete</h3>
        <pre><code>Delete the post with ID "abc123"

Remove a specific task record</code></pre>

        <h3>Advanced Filtering</h3>
        <pre><code>List all published posts (status = "published")

Find posts created by a specific user with status "active"

List records where rating is greater than 4 OR (status = "featured" AND created > "2024-01-01")

Search for records with title containing "javascript" (case-insensitive)

Get paginated results: page 2, 25 records per page, sorted by title</code></pre>
    </div>
</body>
</html>
    <?php
}
