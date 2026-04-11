<?php
/**
 * URL Validation Service Implementation
 *
 * This service handles URL validation including syntax checking
 * and optionally checking URL accessibility. It follows the
 * Single Responsibility Principle (SRP) from SOLID principles.
 */

namespace app\services;

use app\services\interfaces\UrlValidationServiceInterface;
use Yii;
use yii\base\Component;

/**
 * Class UrlValidationService
 *
 * Provides URL validation functionality with support for syntax
 * validation and optional accessibility checking.
 */
class UrlValidationService extends Component implements UrlValidationServiceInterface
{
    /**
     * @var int Timeout for URL accessibility check in seconds
     */
    private int $timeout;

    /**
     * @var array List of allowed protocols
     */
    private array $allowedProtocols = ['http', 'https'];

    /**
     * Constructor
     *
     * @param int $timeout Timeout for URL accessibility check
     */
    public function __construct(int $timeout = 3)
    {
        $this->timeout = $timeout;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function isValidSyntax(string $url): bool
    {
        // Check if URL is not empty
        if (empty($url) || strlen($url) > 2048) {
            return false;
        }

        // Use PHP's built-in URL validation
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        // Parse URL to check protocol
        $parsed = parse_url($url);

        // Ensure protocol exists and is allowed
        if (!isset($parsed['scheme']) || !in_array(strtolower($parsed['scheme']), $this->allowedProtocols)) {
            return false;
        }

        // Ensure host exists
        if (empty($parsed['host'])) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isAccessible(string $url): bool
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'HEAD',
                'timeout' => $this->timeout,
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        // Suppress warnings and check if we get any response
        $headers = @get_headers($url, 1, $context);

        if ($headers === false) {
            return false;
        }

        // Check if we got a successful response (2xx or 3xx)
        if (isset($headers[0])) {
            $statusCode = (int) substr($headers[0], 9, 3);
            return ($statusCode >= 200 && $statusCode < 400);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(string $url, bool $checkAccessibility = true): array
    {
        // Step 1: Syntax validation
        if (!$this->isValidSyntax($url)) {
            return [
                'valid' => false,
                'message' => 'Invalid URL syntax or unsupported protocol. Please enter a valid HTTP or HTTPS URL.',
            ];
        }

        // Normalize URL (add scheme if missing)
        if (!preg_match('/^https?:\/\//i', $url)) {
            $url = 'https://' . $url;
        }

        // Step 2: Accessibility check (optional)
        if ($checkAccessibility && !$this->isAccessible($url)) {
            return [
                'valid' => false,
                'message' => 'The URL appears to be inaccessible. Please check if the website is online.',
            ];
        }

        return [
            'valid' => true,
            'message' => 'URL is valid.',
            'normalizedUrl' => $url,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getAllowedProtocols(): array
    {
        return $this->allowedProtocols;
    }
}
