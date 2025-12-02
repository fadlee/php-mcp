<?php

namespace PocketBaseMCP;

class HttpClient {
    private $baseUrl;
    private $token;

    public function __construct($baseUrl, $token = null) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->token = $token;
    }

    public function setToken($token) {
        $this->token = $token;
    }

    public function getToken() {
        return $this->token;
    }

    public function request($method, $endpoint, $data = null, $query = [], $useAuth = true) {
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
            throw new \Exception("HTTP request failed: $error");
        }

        $decoded = json_decode($response, true);

        if ($httpCode >= 400) {
            $message = $decoded['message'] ?? "HTTP error $httpCode";
            throw new \Exception($message);
        }

        return $decoded;
    }

    public function authenticate($email, $password) {
        $response = $this->request('POST', '/api/collections/_superusers/auth-with-password', [
            'identity' => $email,
            'password' => $password
        ], [], false);

        $this->token = $response['token'] ?? null;
        return $this->token;
    }
}
