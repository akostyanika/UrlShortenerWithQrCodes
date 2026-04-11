<?php
/**
 * Yii2 Web Application Entry Point
 *
 * This is the main entry point for the URL Shortener backend API.
 * It initializes the Yii2 application and handles incoming requests.
 */

// Define constants
defined('YII_DEBUG') or define('YII_DEBUG', getenv('APP_DEBUG') === 'true');
defined('YII_ENV') or define('YII_ENV', getenv('APP_ENV') ?: 'prod');

// Register Composer autoloader
require __DIR__ . '/../vendor/autoload.php';

// Include Yii class
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

// Load application configuration
$config = require __DIR__ . '/../config/web.php';

// Create and run application
(new yii\web\Application($config))->run();
