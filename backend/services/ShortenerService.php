<?php
/**
 * Shortener Service Implementation
 *
 * This service handles URL shortening logic including generating
 * unique short codes, handling collisions, and storing/retrieving
 * URLs from the database. It follows SOLID principles including
 * Dependency Inversion and Single Responsibility.
 */

namespace app\services;

use app\models\Link;
use app\services\interfaces\ShortenerServiceInterface;
use Yii;
use yii\base\Component;
use yii\db\Exception as DbException;

/**
 * Class ShortenerService
 *
 * Provides URL shortening functionality with collision handling.
 */
class ShortenerService extends Component implements ShortenerServiceInterface
{
    /**
     * @var int Length of the short code
     */
    private int $codeLength;

    /**
     * @var string Characters allowed in short code
     */
    private string $allowedChars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    /**
     * Constructor
     *
     * @param int $codeLength Length of the short code to generate
     */
    public function __construct(int $codeLength = 6)
    {
        $this->codeLength = $codeLength;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function createShortUrl(string $originalUrl): array
    {
        try {
            // Check if URL already has a short code
            $existingLink = Link::find()->where(['original_url' => $originalUrl])->one();

            if ($existingLink !== null) {
                return [
                    'success' => true,
                    'short_code' => $existingLink->short_code,
                    'short_url' => $this->buildShortUrl($existingLink->short_code),
                    'original_url' => $originalUrl,
                    'created_at' => $existingLink->created_at,
                    'existing' => true,
                ];
            }

            // Generate a unique short code
            $shortCode = $this->generateUniqueCode();

            // Create new link record
            $link = new Link();
            $link->original_url = $originalUrl;
            $link->short_code = $shortCode;
            $link->created_at = date('Y-m-d H:i:s');

            if (!$link->save()) {
                Yii::error('Failed to save link: ' . json_encode($link->getErrors()), __METHOD__);
                return [
                    'success' => false,
                    'message' => 'Failed to create short URL. Please try again.',
                ];
            }

            return [
                'success' => true,
                'short_code' => $shortCode,
                'short_url' => $this->buildShortUrl($shortCode),
                'original_url' => $originalUrl,
                'created_at' => $link->created_at,
                'existing' => false,
            ];

        } catch (DbException $e) {
            Yii::error('Database error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'message' => 'Database error occurred. Please try again.',
            ];
        } catch (\Exception $e) {
            Yii::error('Error creating short URL: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'message' => 'An error occurred while creating the short URL.',
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findOriginalUrl(string $shortCode): ?string
    {
        $link = Link::find()->where(['short_code' => $shortCode])->one();

        if ($link === null) {
            return null;
        }

        // Update click count
        $link->updateCounters(['clicks' => 1]);

        return $link->original_url;
    }

    /**
     * {@inheritdoc}
     */
    public function codeExists(string $shortCode): bool
    {
        return Link::find()->where(['short_code' => $shortCode])->exists();
    }

    /**
     * {@inheritdoc}
     */
    public function generateCode(): string
    {
        return $this->generateUniqueCode();
    }

    /**
     * Generate a unique short code with collision handling
     *
     * This method uses a do-while loop to ensure uniqueness
     * by checking the database for existing codes.
     *
     * @return string A unique short code
     */
    private function generateUniqueCode(): string
    {
        $maxAttempts = 10;
        $attempts = 0;

        do {
            $code = $this->generateRandomCode();
            $exists = $this->codeExists($code);
            $attempts++;

            if ($attempts >= $maxAttempts) {
                // If we've tried too many times, use a timestamp-based approach
                $code = $this->generateTimestampCode();
                break;
            }
        } while ($exists);

        return $code;
    }

    /**
     * Generate a random alphanumeric code
     *
     * @return string Random code
     */
    private function generateRandomCode(): string
    {
        $chars = $this->allowedChars;
        $code = '';
        $charsLength = strlen($chars);

        for ($i = 0; $i < $this->codeLength; $i++) {
            $code .= $chars[random_int(0, $charsLength - 1)];
        }

        return $code;
    }

    /**
     * Generate a code using timestamp (fallback)
     *
     * @return string Timestamp-based code
     */
    private function generateTimestampCode(): string
    {
        $timestamp = time();
        $chars = $this->allowedChars;
        $code = '';

        // Convert timestamp to base62-like representation
        while ($timestamp > 0) {
            $code .= $chars[$timestamp % strlen($chars)];
            $timestamp = intdiv($timestamp, strlen($chars));
        }

        // Pad with random chars if needed
        while (strlen($code) < $this->codeLength) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return substr(str_shuffle($code), 0, $this->codeLength);
    }

    /**
     * Build the full short URL
     *
     * @param string $shortCode The short code
     * @return string The full short URL
     */
    private function buildShortUrl(string $shortCode): string
    {
        $baseUrl = Yii::$app->params['shortUrl']['baseUrl'] ?? 'http://localhost:8080';
        return rtrim($baseUrl, '/') . '/' . $shortCode;
    }
}
