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
