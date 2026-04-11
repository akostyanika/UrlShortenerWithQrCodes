<?php
/**
 * Application Parameters
 *
 * This file contains application-wide parameters including
 * security keys, API settings, and service configurations.
 */

return [
    // Security
    'cookieValidationKey' => getenv('APP_KEY') ?: 'urlshortener-secret-key-change-in-production',

    // Application
    'appName' => 'URL Shortener Service',
    'appVersion' => '1.0.0',

    // API Configuration
    'api' => [
        'shortCodeLength' => 6,
        'allowedProtocols' => ['http', 'https'],
        'maxUrlLength' => 2048,
        'validationTimeout' => 3, // seconds
    ],

    // QR Code Configuration
    'qrCode' => [
        'size' => 300,
        'margin' => 10,
        'errorCorrectionLevel' => 'M', // L, M, Q, H
        'format' => 'png',
    ],

    // Short URL Configuration
    'shortUrl' => [
        'baseUrl' => getenv('APP_URL') ?: 'http://localhost:8080',
        'expirationDays' => 0, // 0 = never expire
    ],
];
