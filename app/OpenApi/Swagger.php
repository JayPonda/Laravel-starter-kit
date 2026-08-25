<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Laravel Starter Kit API',
    description: 'API documentation for the Laravel Starter Kit (Sanctum token auth).',
    contact: new OA\Contact(name: 'Laravel Starter Kit')
)]

#[OA\Server(
    url: '/api',
    description: 'API Server'
)]

#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'Sanctum personal access token. Supply as `Bearer <token>`.'
)]

#[OA\Schema(
    schema: 'User',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Jane Doe'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'jane@example.com'),
        new OA\Property(property: 'created_at', type: 'string', format: 'datetime', example: '2026-08-25 12:00:00'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'datetime', example: '2026-08-25 12:00:00'),
    ]
)]

#[OA\Schema(
    schema: 'File',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'original_name', type: 'string', example: 'notes.txt'),
        new OA\Property(property: 'path', type: 'string', example: 'file-upload/2026-08-25/abc123'),
        new OA\Property(property: 'mime_type', type: 'string', example: 'text/plain'),
        new OA\Property(property: 'size', type: 'integer', example: 1024),
        new OA\Property(property: 'disk', type: 'string', example: 'minio'),
        new OA\Property(property: 'created_at', type: 'string', format: 'datetime'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'datetime'),
    ]
)]

#[OA\Schema(
    schema: 'Error',
    type: 'object',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
        new OA\Property(
            property: 'errors',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(type: 'array', items: new OA\Items(type: 'string'))
        ),
    ]
)]

class Swagger
{
}
