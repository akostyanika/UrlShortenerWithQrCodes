<?php
/**
 * QR Code Service Interface
 *
 * This interface defines the contract for QR code generation services.
 * It follows the Single Responsibility Principle (SRP) from SOLID
 * principles, focusing solely on QR code generation logic.
 */

namespace app\services\interfaces;

/**
 * Interface QrServiceInterface
 *
 * Defines the contract for generating QR codes.
 */
interface QrServiceInterface
{
    /**
     * Generate QR code as Base64 encoded string
     *
     * @param string $data The data to encode in the QR code
     * @return string The QR code as Base64 encoded data URI
     */
    public function generate(string $data): string;

    /**
     * Generate QR code as Base64 encoded string (alias for generate)
     *
     * @param string $url The URL to encode in the QR code
     * @return string The QR code as Base64 encoded string
     */
    public function generateForUrl(string $url): string;

    /**
     * Set QR code size
     *
     * @param int $size Size in pixels
     * @return self
     */
    public function setSize(int $size): self;

    /**
     * Set QR code margin
     *
     * @param int $margin Margin in pixels
     * @return self
     */
    public function setMargin(int $margin): self;

    /**
     * Set error correction level
     *
     * @param string $level Error correction level (L, M, Q, H)
     * @return self
     */
    public function setErrorCorrectionLevel(string $level): self;
}
