<?php

namespace App\WorkflowActions\General;

use App\WorkflowActions\AbstractWorkflowAction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class HttpCall extends AbstractWorkflowAction
{
    public function inputs(): array
    {
        return [
            'url' => 'The URL to make the HTTP request to',
            'method' => 'HTTP method (GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS)',
            'headers' => 'Optional headers as key-value pairs (JSON object)',
            'body' => 'Optional request body/payload (JSON string or object)',
            'timeout' => 'Request timeout in seconds (default: 30)',
        ];
    }

    public function outputs(): array
    {
        return [
            'status_code' => 'HTTP response status code',
            'response_body_raw' => 'Response body raw',
            'response_body_json' => 'Response body as JSON if the output is json',
            'response_headers' => 'Response headers as JSON string',
            'success' => 'Response status if it was success',
        ];
    }

    public function run(array $input): array
    {
        Validator::make($input, [
            'url' => ['required', 'url'],
            'method' => [
                'required',
                'string',
                Rule::in(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS']),
            ],
            'headers' => ['nullable', 'string'],
            'body' => ['nullable'],
            'timeout' => ['nullable', 'integer', 'min:1', 'max:300'],
        ])->validate();

        $url = $input['url'];
        $method = strtoupper($input['method']);
        $timeout = $input['timeout'] ?? 30;

        // Parse headers if provided
        $headers = [];
        if (! empty($input['headers'])) {
            $decodedHeaders = json_decode($input['headers'], true);
            if (is_array($decodedHeaders)) {
                $headers = $decodedHeaders;
            }
        }

        // Prepare request body
        $body = null;
        if (! empty($input['body'])) {
            if (is_string($input['body'])) {
                $decodedBody = json_decode($input['body'], true);
                $body = $decodedBody !== null ? $decodedBody : $input['body'];
            } else {
                $body = $input['body'];
            }
        }

        try {
            // Build HTTP client with timeout
            $httpClient = Http::timeout($timeout);

            // Add headers if provided
            if (! empty($headers)) {
                $httpClient = $httpClient->withHeaders($headers);
            }

            // Add body for methods that support it
            if (in_array($method, ['POST', 'PUT', 'PATCH']) && $body !== null) {
                $httpClient = $httpClient->withBody(json_encode($body), 'application/json');
            }

            // Make the request
            $response = $httpClient->send($method, $url);

            // Get response data
            $statusCode = $response->status();
            $responseBody = $response->body();
            $responseHeaders = $response->headers();

            // Try to decode response body as JSON
            $responseBodyJson = null;
            $decodedBody = json_decode($responseBody, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $responseBodyJson = $decodedBody;
            }

            return [
                'status_code' => $statusCode,
                'response_body_raw' => $responseBody,
                'response_body_json' => $responseBodyJson,
                'response_headers' => json_encode($responseHeaders),
                'success' => $statusCode >= 200 && $statusCode < 300,
            ];
        } catch (\Exception $e) {
            return [
                'status_code' => 0,
                'response_body_raw' => $e->getMessage(),
                'response_body_json' => null,
                'response_headers' => json_encode([]),
                'success' => false,
            ];
        }
    }
}
