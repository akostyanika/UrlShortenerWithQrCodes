<?php
/**
 * API Controller
 *
 * This controller handles the REST API endpoints for URL shortening.
 * It follows the Single Responsibility Principle by delegating
 * business logic to services.
 */

namespace app\controllers;

use app\services\interfaces\QrServiceInterface;
use app\services\interfaces\ShortenerServiceInterface;
use app\services\interfaces\UrlValidationServiceInterface;
use Yii;
use yii\base\InvalidConfigException;
use yii\web\Controller;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * Class ApiController
 *
 * Handles API endpoints for URL shortening operations.
 */
class ApiController extends Controller
{
    /**
     * @var UrlValidationServiceInterface
     */
    private UrlValidationServiceInterface $validationService;

    /**
     * @var ShortenerServiceInterface
     */
    private ShortenerServiceInterface $shortenerService;

    /**
     * @var QrServiceInterface
     */
    private QrServiceInterface $qrService;

    /**
     * Constructor with dependency injection
     *
     * @param string $id Controller ID
     * @param \yii\base\Module $module Parent module
     * @param UrlValidationServiceInterface $validationService URL validation service
     * @param ShortenerServiceInterface $shortenerService Shortener service
     * @param QrServiceInterface $qrService QR code service
     * @param array $config Configuration array
     * @throws InvalidConfigException If services are not properly configured
     */
    public function __construct(
        string $id,
        $module,
        UrlValidationServiceInterface $validationService,
        ShortenerServiceInterface $shortenerService,
        QrServiceInterface $qrService,
        array $config = []
    ) {
        parent::__construct($id, $module, $config);

        $this->validationService = $validationService;
        $this->shortenerService = $shortenerService;
        $this->qrService = $qrService;
    }

    /**
     * Initialize controller
     *
     * This method sets up CORS headers for cross-origin requests.
     */
    public function init(): void
    {
        parent::init();

        // Set response format to JSON
        Yii::$app->response->format = Response::FORMAT_JSON;

        // Set CORS headers
        Yii::$app->response->headers->set('Access-Control-Allow-Origin', '*');
        Yii::$app->response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        Yii::$app->response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
    }

    /**
     * Handle CORS preflight requests
     *
     * @return array Empty response for OPTIONS requests
     */
    public function actionOptions(): array
    {
        Yii::$app->response->statusCode = 204;
        return [];
    }

    /**
     * Shorten URL endpoint
     *
     * POST /api/shorten
     *
     * @return array Response with short URL and QR code
     * @throws BadRequestHttpException If request is invalid
     */
    public function actionShorten(): array
    {
        // Get JSON input
        $request = Yii::$app->request;
        $bodyParams = $request->getBodyParams();

        // Get URL from request
        $url = $bodyParams['url'] ?? ($request->get('url') ?? null);

        // Validate URL presence
        if (empty($url)) {
            return $this->errorResponse('URL is required', 400);
        }

        // Validate URL format
        $validationResult = $this->validationService->validate($url, true);

        if (!$validationResult['valid']) {
            return $this->errorResponse($validationResult['message'], 400);
        }

        // Use normalized URL
        $normalizedUrl = $validationResult['normalizedUrl'] ?? $url;

        // Create short URL
        $shortenerResult = $this->shortenerService->createShortUrl($normalizedUrl);

        if (!$shortenerResult['success']) {
            return $this->errorResponse($shortenerResult['message'], 500);
        }

        // Generate QR code
        try {
            $qrCode = $this->qrService->generateForUrl($shortenerResult['short_url']);
        } catch (\Exception $e) {
            Yii::error('QR generation failed: ' . $e->getMessage(), __METHOD__);
            $qrCode = null;
        }

        // Return success response
        return $this->successResponse([
            'short_code' => $shortenerResult['short_code'],
            'short_url' => $shortenerResult['short_url'],
            'original_url' => $shortenerResult['original_url'],
            'qr_code' => $qrCode,
            'created_at' => $shortenerResult['created_at'],
        ]);
    }

    /**
     * Get URL info endpoint
     *
     * GET /api/info?code=xxxxxx
     *
     * @return array Response with URL information
     * @throws BadRequestHttpException If code is missing
     */
    public function actionInfo(): array
    {
        // Get short code from request
        $code = Yii::$app->request->get('code');

        if (empty($code)) {
            return $this->errorResponse('Short code is required', 400);
        }

        // Validate short code format
        if (!preg_match('/^[a-zA-Z0-9]{6}$/', $code)) {
            return $this->errorResponse('Invalid short code format', 400);
        }

        // Find original URL
        $originalUrl = $this->shortenerService->findOriginalUrl($code);

        if ($originalUrl === null) {
            return $this->errorResponse('Short URL not found', 404);
        }

        // Get short URL
        $baseUrl = Yii::$app->params['shortUrl']['baseUrl'] ?? 'http://localhost:8080';
        $shortUrl = rtrim($baseUrl, '/') . '/' . $code;

        return $this->successResponse([
            'short_code' => $code,
            'short_url' => $shortUrl,
            'original_url' => $originalUrl,
        ]);
    }

    /**
     * Create success response
     *
     * @param array $data Response data
     * @param int $code HTTP status code
     * @return array Formatted success response
     */
    private function successResponse(array $data, int $code = 200): array
    {
        Yii::$app->response->statusCode = $code;
        return [
            'status' => 'success',
            'code' => $code,
            'data' => $data,
        ];
    }

    /**
     * Create error response
     *
     * @param string $message Error message
     * @param int $code HTTP status code
     * @return array Formatted error response
     */
    private function errorResponse(string $message, int $code = 400): array
    {
        Yii::$app->response->statusCode = $code;
        return [
            'status' => 'error',
            'code' => $code,
            'message' => $message,
        ];
    }
}
