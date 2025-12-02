<?php

require_once __DIR__ . '/src/HttpClient.php';
require_once __DIR__ . '/src/ToolDefinitions.php';
require_once __DIR__ . '/src/FieldSchemaReference.php';
require_once __DIR__ . '/src/PocketBaseMCPServer.php';

use PocketBaseMCP\PocketBaseMCPServer;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

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
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') 
        . '://' . $_SERVER['HTTP_HOST'] . strtok($_SERVER['REQUEST_URI'], '?');
    require __DIR__ . '/templates/docs.html.php';
}
