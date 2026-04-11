<?php
/**
 * Shortener Service Interface
 *
 * This interface defines the contract for URL shortening services.
 * It follows the Single Responsibility Principle (SRP) from SOLID
 * principles, focusing solely on URL shortening logic.
 */

namespace app\services\interfaces;

/**
 * Interface ShortenerServiceInterface
 *
 * Defines the contract for generating and managing short URLs.
 */
interface ShortenerServiceInterface
{
    /**
     * Create a shortened URL
     *
     * @param string $originalUrl The original URL to shorten
     * @return array Result with short code and short URL
     */
    public function createShortUrl(string $originalUrl): array;

    /**
     * Find original URL by short code
     *
     * @param string $shortCode The short code to look up
     * @return string|null The original URL or null if not found
     */
    public function findOriginalUrl(string $shortCode): ?string;

    /**
     * Check if short code exists
     *
     * @param string $shortCode The short code to check
     * @return bool True if code exists, false otherwise
     */
    public function codeExists(string $shortCode): bool;

    /**
     * Generate a unique short code
     *
     * @return string A unique short code
     */
    public function generateCode(): string;
}
