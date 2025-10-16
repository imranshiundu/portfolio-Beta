<?php
/**
 * Application Configuration
 */

return [
    'name' => 'Imran Shiundu Portfolio',
    'env' => 'production', // development, production
    'debug' => false,
    'url' => 'https://imranshiundu.eu',
    'api_url' => 'https://api.imranshiundu.eu',
    'timezone' => 'Africa/Nairobi',
    'locale' => 'en',
    
    // Security
    'key' => 'base64:your-secret-key-here',
    'cipher' => 'AES-256-CBC',
    
    // Features
    'features' => [
        'blog' => true,
        'projects' => true,
        'contact' => true,
        'dark_mode' => true,
        'analytics' => true,
    ],
    
    // Upload settings
    'uploads' => [
        'max_size' => 5242880, // 5MB in bytes
        'allowed_types' => [
            'image/jpeg',
            'image/jpg', 
            'image/png',
            'image/webp',
            'image/gif'
        ],
        'path' => '../storage/'
    ],
    
    // API settings
    'api' => [
        'version' => 'v1',
        'rate_limit' => 100, // requests per minute
        'cors' => [
            'allowed_origins' => [
                'https://imranshiundu.eu',
                'https://www.imranshiundu.eu',
                'http://localhost:3000'
            ],
            'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
            'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With']
        ]
    ]
];
?>