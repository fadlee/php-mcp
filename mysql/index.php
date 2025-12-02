<?php
/**
 * MySQL MCP Server
 * Implements Model Context Protocol for MySQL database operations
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Database configuration
const DB_HOST = '127.0.0.1';
const DB_PORT = '3306';
const DB_USER = 'root';
const DB_PASS = '';
const DB_NAME = 'ivorie_legacy_erp';

class MySQLMCPServer {
    private $conn;
    
    public function __construct($host = DB_HOST, $port = DB_PORT, $user = DB_USER, $pass = DB_PASS, $database = DB_NAME) {
        try {
            $this->conn = new PDO(
                "mysql:host=" . $host . ";port=" . $port . ";dbname=" . $database,
                $user,
                $pass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            $this->sendError(-32603, "Database connection failed: " . $e->getMessage());
            exit;
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
                    'tools' => new stdClass(),
                    'resources' => new stdClass()
                ],
                'serverInfo' => [
                    'name' => 'mysql-mcp-server',
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
                    [
                        'name' => 'query',
                        'description' => 'Execute a SELECT query on the MySQL database',
                        'inputSchema' => [
                            'type' => 'object',
                            'properties' => [
                                'sql' => [
                                    'type' => 'string',
                                    'description' => 'SQL SELECT query to execute'
                                ]
                            ],
                            'required' => ['sql']
                        ]
                    ],
                    [
                        'name' => 'execute',
                        'description' => 'Execute an INSERT, UPDATE, or DELETE query',
                        'inputSchema' => [
                            'type' => 'object',
                            'properties' => [
                                'sql' => [
                                    'type' => 'string',
                                    'description' => 'SQL query to execute'
                                ]
                            ],
                            'required' => ['sql']
                        ]
                    ],
                    [
                        'name' => 'list_tables',
                        'description' => 'List all tables in the database',
                        'inputSchema' => [
                            'type' => 'object',
                            'properties' => new stdClass()
                        ]
                    ],
                    [
                        'name' => 'describe_table',
                        'description' => 'Get the structure of a table',
                        'inputSchema' => [
                            'type' => 'object',
                            'properties' => [
                                'table' => [
                                    'type' => 'string',
                                    'description' => 'Name of the table'
                                ]
                            ],
                            'required' => ['table']
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
                case 'query':
                    $result = $this->executeQuery($args['sql']);
                    break;
                
                case 'execute':
                    $result = $this->executeCommand($args['sql']);
                    break;
                
                case 'list_tables':
                    $result = $this->listTables();
                    break;
                
                case 'describe_table':
                    $result = $this->describeTable($args['table']);
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
                            'text' => json_encode($result, JSON_PRETTY_PRINT)
                        ]
                    ]
                ]
            ];
        } catch (Exception $e) {
            return $this->error($id, -32603, $e->getMessage());
        }
    }
    
    private function executeQuery($sql) {
        // Security: Only allow SELECT queries
        if (!preg_match('/^\s*SELECT/i', $sql)) {
            throw new Exception("Only SELECT queries are allowed in query tool");
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function executeCommand($sql) {
        // Security: Only allow INSERT, UPDATE, DELETE
        if (!preg_match('/^\s*(INSERT|UPDATE|DELETE)/i', $sql)) {
            throw new Exception("Only INSERT, UPDATE, DELETE queries allowed");
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return [
            'affected_rows' => $stmt->rowCount(),
            'message' => 'Query executed successfully'
        ];
    }
    
    private function listTables() {
        $stmt = $this->conn->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return ['tables' => $tables];
    }
    
    private function describeTable($table) {
        // Sanitize table name
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $stmt = $this->conn->query("DESCRIBE `$table`");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function listResources($id) {
        try {
            $tables = $this->listTables()['tables'];
            $resources = array_map(function($table) {
                return [
                    'uri' => "mysql://table/$table",
                    'name' => $table,
                    'description' => "Table: $table",
                    'mimeType' => 'application/json'
                ];
            }, $tables);
            
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
            if (preg_match('#^mysql://table/(.+)$#', $uri, $matches)) {
                $table = $matches[1];
                $data = $this->describeTable($table);
                
                return [
                    'jsonrpc' => '2.0',
                    'id' => $id,
                    'result' => [
                        'contents' => [
                            [
                                'uri' => $uri,
                                'mimeType' => 'application/json',
                                'text' => json_encode($data, JSON_PRETTY_PRINT)
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
    
    private function sendError($code, $message) {
        echo json_encode([
            'jsonrpc' => '2.0',
            'id' => null,
            'error' => ['code' => $code, 'message' => $message]
        ]);
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
    
    $dbHost = $_GET['host'] ?? DB_HOST;
    $dbPort = $_GET['port'] ?? DB_PORT;
    $dbUser = $_GET['user'] ?? DB_USER;
    $dbPass = $_GET['pass'] ?? DB_PASS;
    $dbName = $_GET['dbname'] ?? DB_NAME;
    
    $server = new MySQLMCPServer($dbHost, $dbPort, $dbUser, $dbPass, $dbName);
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
    <title>MySQL MCP Server</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; background: #f5f5f5; color: #333; line-height: 1.6; }
        h1 { color: #2563eb; border-bottom: 2px solid #2563eb; padding-bottom: 10px; }
        h2 { color: #1e40af; margin-top: 30px; }
        code { background: #e5e7eb; padding: 2px 6px; border-radius: 4px; font-size: 0.9em; }
        pre { background: #1e293b; color: #e2e8f0; padding: 15px; border-radius: 8px; overflow-x: auto; }
        pre code { background: transparent; color: inherit; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { border: 1px solid #d1d5db; padding: 10px; text-align: left; }
        th { background: #e5e7eb; }
        .required { color: #dc2626; font-weight: bold; }
        .card { background: white; padding: 20px; border-radius: 8px; margin: 15px 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <h1>MySQL MCP Server</h1>
    <p>Model Context Protocol server for MySQL database operations. Use with <code>mcp-remote</code> package.</p>

    <div class="card">
        <h2>URL Parameters</h2>
        <table>
            <tr><th>Parameter</th><th>Description</th><th>Required</th></tr>
            <tr><td><code>host</code></td><td>MySQL server host</td><td class="required">Yes</td></tr>
            <tr><td><code>port</code></td><td>MySQL server port (default: 3306)</td><td>No</td></tr>
            <tr><td><code>dbname</code></td><td>Database name</td><td class="required">Yes</td></tr>
            <tr><td><code>user</code></td><td>Database username</td><td class="required">Yes</td></tr>
            <tr><td><code>pass</code></td><td>Database password</td><td class="required">Yes</td></tr>
        </table>
    </div>

    <div class="card">
        <h2>MCP Client Configuration</h2>
        <p>Add this to your MCP client configuration (e.g., Claude Desktop):</p>
        <pre><code>{
  "mcpServers": {
    "mysql-server": {
      "command": "npx",
      "args": [
        "mcp-remote",
        "<?= htmlspecialchars($baseUrl) ?>?host=localhost&port=3306&dbname=mydb&user=root&pass=secret",
        "--allow-http"
      ]
    }
  }
}</code></pre>
    </div>

    <div class="card">
        <h2>Available Tools</h2>
        <table>
            <tr><th>Tool</th><th>Description</th></tr>
            <tr><td><code>query</code></td><td>Execute SELECT queries</td></tr>
            <tr><td><code>execute</code></td><td>Execute INSERT, UPDATE, DELETE queries</td></tr>
            <tr><td><code>list_tables</code></td><td>List all tables in the database</td></tr>
            <tr><td><code>describe_table</code></td><td>Get the structure of a table</td></tr>
        </table>
    </div>

    <div class="card">
        <h2>Resources</h2>
        <p>The server also exposes database tables as MCP resources with URI format: <code>mysql://table/{table_name}</code></p>
    </div>
</body>
</html>
    <?php
}

