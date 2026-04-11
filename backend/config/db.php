<?php
/**
 * Database Configuration
 *
 * This configuration file sets up the database connection for the
 * URL Shortener service using Yii2's database component.
 */

return [
    'class' => 'yii\db\Connection',
    'dsn' => sprintf(
        'mysql:host=%s;dbname=%s;port=3306;charset=utf8mb4',
        getenv('DB_HOST') ?: 'localhost',
        getenv('DB_NAME') ?: 'urlshortener'
    ),
    'username' => getenv('DB_USER') ?: 'app_user',
    'password' => getenv('DB_PASSWORD') ?: 'app_secret_pass',
    'tablePrefix' => '',
    'charset' => 'utf8mb4',

    // Connection pool configuration
    'enableSchemaCache' => true,
    'schemaCacheDuration' => 3600,
    'schemaCache' => 'cache',

    // Error handling
    'enableLogging' => true,
    'enableProfiling' => true,
];
