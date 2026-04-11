<?php
/**
 * QR Code Service Implementation
 *
 * This service handles QR code generation using the Endroid QR Code
 * library. It generates QR codes as Base64 encoded strings for easy
 * inclusion in JSON responses. It follows the Single Responsibility
 * Principle (SRP) from SOLID principles.
 */

namespace app\services;

use app\services\interfaces\QrServiceInterface;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Yii;
use yii\base\Component;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Logo\Logo;

/**
 * Class QrService
 *
 * Provides QR code generation functionality.
 */
class QrService extends Component implements QrServiceInterface
{
    /**
     * @var int QR code size in pixels
     */
    private int $size;

    /**
     * @var int QR code margin in pixels
     */
    private int $margin;

    /**
     * @var string Error correction level
     */
    private string $errorCorrectionLevel;

    /**
     * Constructor
     *
     * @param int $size QR code size in pixels
     * @param int $margin QR code margin in pixels
     * @param string $errorCorrectionLevel Error correction level
     */
    public function __construct(int $size = 300, int $margin = 10, string $errorCorrectionLevel = 'M')
    {
        $this->size = $size;
        $this->margin = $margin;
        $this->errorCorrectionLevel = $errorCorrectionLevel;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function generate(string $data): string
    {
        return $this->generateForUrl($data);
    }

    /**
     * {@inheritdoc}
     */
    public function generateForUrl(string $url): string
    {
        try {
            // Map string error correction level to enum
            $ecLevel = match (strtoupper($this->errorCorrectionLevel)) {
                'L' => ErrorCorrectionLevel::LOW,
                'M' => ErrorCorrectionLevel::MEDIUM,
                'Q' => ErrorCorrectionLevel::QUARTILE,
                'H' => ErrorCorrectionLevel::HIGH,
                default => ErrorCorrectionLevel::MEDIUM,
            };

            // Create QR code
            $qrCode = new QrCode(
                data: $url,
                encoding: new \Endroid\QrCode\Encoding\Encoding('UTF-8'),
                errorCorrectionLevel: $ecLevel,
                size: $this->size,
                margin: $this->margin,
                foregroundColor: new Color(0, 0, 0),
                backgroundColor: new Color(255, 255, 255)
            );

            // Create PNG writer
            $writer = new PngWriter();

            // Generate QR code
            $result = $writer->write($qrCode);

            // Get raw PNG data
            $pngData = $result->getString();

            // Encode as Base64
            $base64 = base64_encode($pngData);

            // Return as data URI
            return 'data:image/png;base64,' . $base64;

        } catch (\Exception $e) {
            Yii::error('Error generating QR code: ' . $e->getMessage(), __METHOD__);
            throw new \Exception('Failed to generate QR code: ' . $e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setSize(int $size): self
    {
        $this->size = max(50, min(1000, $size)); // Clamp between 50 and 1000
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setMargin(int $margin): self
    {
        $this->margin = max(0, min(50, $margin)); // Clamp between 0 and 50
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setErrorCorrectionLevel(string $level): self
    {
        $validLevels = ['L', 'M', 'Q', 'H'];
        $level = strtoupper($level);

        if (in_array($level, $validLevels)) {
            $this->errorCorrectionLevel = $level;
        }

        return $this;
    }
}
