<?php
/**
 * Web Application Configuration
 *
 * This is the main configuration file for the URL Shortener
 * backend API. It includes all necessary components, modules,
 * and request handling configurations.
 */

$db = require __DIR__ . '/db.php';
$params = require __DIR__ . '/params.php';

$config = [
    'id' => 'urlshortener-backend',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'language' => 'en-US',
    'timezone' => 'UTC',

    // ----------------------------------------------------------
    // Controller Configuration
    // ----------------------------------------------------------
    'controllerNamespace' => 'app\controllers',

    // ----------------------------------------------------------
    // Components
    // ----------------------------------------------------------
    'components' => [
        // Request configuration for REST API
        'request' => [
            'cookieValidationKey' => $params['cookieValidationKey'],
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ],
            'enableCsrfValidation' => false, // Disabled for API
        ],

        // Response configuration
        'response' => [
            'class' => 'yii\web\Response',
            'on beforeSend' => function ($event) {
                $response = $event->sender;
                if ($response->data !== null && !$response->isSuccessful) {
                    // Ensure error responses are in JSON format
                    if (!isset($response->data['status'])) {
                        $response->data = [
                            'status' => 'error',
                            'message' => $response->data['message'] ?? 'An error occurred',
                            'code' => $response->statusCode,
                        ];
                    }
                }
            },
        ],

        // Database connection
        'db' => $db,

        // URL manager for routing
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'enableStrictParsing' => false,
            'rules' => [
                // API routes
                'POST api/shorten' => 'api/shorten',
                'GET api/info/<code:\w+>' => 'api/info',
                'OPTIONS api/shorten' => 'api/options',
                'OPTIONS api/info/<code:\w+>' => 'api/options',

                // Redirect route
                'GET <code:\w{6}>' => 'redirect/index',
            ],
        ],

        // Cache component (using file cache)
        'cache' => [
            'class' => 'yii\caching\FileCache',
            'cachePath' => '@app/runtime/cache',
        ],

        // Logging configuration
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                    'logFile' => '@app/runtime/logs/app.log',
                    'maxFileSize' => 1024 * 1024 * 2, // 2MB
                    'maxLogFiles' => 5,
                ],
            ],
        ],
    ],

    // ----------------------------------------------------------
    // Service Definitions (Dependency Injection)
    // ----------------------------------------------------------
    'container' => [
        'definitions' => [
            // URL Validation Service
            'app\services\interfaces\UrlValidationServiceInterface' => [
                'class' => 'app\services\UrlValidationService',
                'timeout' => 3, // 3 second timeout for URL accessibility check
            ],

            // Shortener Service
            'app\services\interfaces\ShortenerServiceInterface' => [
                'class' => 'app\services\ShortenerService',
                'codeLength' => 6,
            ],

            // QR Code Service
            'app\services\interfaces\QrServiceInterface' => [
                'class' => 'app\services\QrService',
                'size' => 300,
                'margin' => 10,
            ],
        ],
    ],

    // ----------------------------------------------------------
    // Application Parameters
    // ----------------------------------------------------------
    'params' => $params,
];

// Load console config if running in console mode
if (YII_ENV_DEV) {
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
    ];
}

return $config;
