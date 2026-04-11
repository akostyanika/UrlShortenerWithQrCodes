<?php
/**
 * URL Validation Service Interface
 *
 * This interface defines the contract for URL validation services.
 * It follows the Single Responsibility Principle (SRP) from SOLID
 * principles, focusing solely on URL validation logic.
 */

namespace app\services\interfaces;

/**
 * Interface UrlValidationServiceInterface
 *
 * Defines the contract for validating URLs including syntax validation
 * and optionally checking URL accessibility.
 */
interface UrlValidationServiceInterface
{
    /**
     * Validate URL syntax
     *
     * @param string $url The URL to validate
     * @return bool True if URL syntax is valid, false otherwise
     */
    public function isValidSyntax(string $url): bool;

    /**
     * Check if URL is accessible
     *
     * @param string $url The URL to check
     * @return bool True if URL is accessible, false otherwise
     */
    public function isAccessible(string $url): bool;

    /**
     * Complete validation of URL
     *
     * @param string $url The URL to validate
     * @param bool $checkAccessibility Whether to check if URL is accessible
     * @return array Validation result with status and message
     */
    public function validate(string $url, bool $checkAccessibility = true): array;

    /**
     * Get allowed protocols
     *
     * @return array List of allowed protocols
     */
    public function getAllowedProtocols(): array;
}
