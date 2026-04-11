<?php
/**
 * Redirect Controller
 *
 * This controller handles the redirection from short URLs
 * to their original URLs. It's a critical component that
 * performs the core functionality of the URL shortener.
 */

namespace app\controllers;

use app\services\interfaces\ShortenerServiceInterface;
use Yii;
use yii\base\InvalidConfigException;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * Class RedirectController
 *
 * Handles short URL redirection to original URLs.
 */
class RedirectController extends Controller
{
    /**
     * @var ShortenerServiceInterface
     */
    private ShortenerServiceInterface $shortenerService;

    /**
     * Constructor with dependency injection
     *
     * @param string $id Controller ID
     * @param \yii\base\Module $module Parent module
     * @param ShortenerServiceInterface $shortenerService Shortener service
     * @param array $config Configuration array
     * @throws InvalidConfigException If service is not properly configured
     */
    public function __construct(
        string $id,
        $module,
        ShortenerServiceInterface $shortenerService,
        array $config = []
    ) {
        parent::__construct($id, $module, $config);
        $this->shortenerService = $shortenerService;
    }

    /**
     * Redirect to original URL
     *
     * GET /{short_code}
     *
     * This action looks up the short code in the database and
     * redirects the user to the original URL with a 301 Moved
     * Permanently status code for SEO purposes.
     *
     * @param string $code The short code
     * @return void Redirects to original URL or shows 404
     * @throws NotFoundHttpException If short code is not found
     */
    public function actionIndex(string $code): void
    {
        // Validate short code format
        if (!preg_match('/^[a-zA-Z0-9]{6}$/', $code)) {
            throw new NotFoundHttpException('Invalid short code format');
        }

        // Find original URL
        $originalUrl = $this->shortenerService->findOriginalUrl($code);

        if ($originalUrl === null) {
            throw new NotFoundHttpException('Short URL not found. The link may have expired or never existed.');
        }

        // Ensure URL has a protocol
        if (!preg_match('/^https?:\/\//i', $originalUrl)) {
            $originalUrl = 'http://' . $originalUrl;
        }

        // Log the redirect for analytics (optional)
        Yii::info("Redirecting short code '{$code}' to '{$originalUrl}'", __METHOD__);

        // Perform permanent redirect (301) for better SEO
        Yii::$app->response->redirect($originalUrl, 301)->send();
        Yii::$app->end();
    }
}
