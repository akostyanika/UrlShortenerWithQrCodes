<?php
/**
 * Link Model
 *
 * This model represents a shortened URL in the database.
 * It provides ActiveRecord functionality for the links table.
 */

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * Class Link
 *
 * Represents a shortened URL entry in the database.
 *
 * @property int $id
 * @property string $original_url
 * @property string $short_code
 * @property int $clicks
 * @property string $created_at
 * @property string|null $updated_at
 */
class Link extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return 'links';
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            // Original URL is required
            [['original_url', 'short_code'], 'required'],

            // Original URL should be a valid URL
            ['original_url', 'url', 'message' => 'Please enter a valid URL'],

            // Short code should be exactly 6 characters
            ['short_code', 'string', 'length' => 6],

            // Short code should be alphanumeric
            ['short_code', 'match', 'pattern' => '/^[a-zA-Z0-9]{6}$/', 'message' => 'Short code must be alphanumeric'],

            // Short code should be unique
            ['short_code', 'unique', 'message' => 'This short code already exists'],

            // Clicks should be an integer
            ['clicks', 'integer', 'min' => 0],

            // Created at should be safe for mass assignment
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            // Timestamp behavior for created_at and updated_at
            'timestamp' => [
                'class' => 'yii\behaviors\TimestampBehavior',
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'original_url' => 'Original URL',
            'short_code' => 'Short Code',
            'clicks' => 'Clicks',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Get the full short URL
     *
     * @return string The full short URL
     */
    public function getShortUrl(): string
    {
        $baseUrl = Yii::$app->params['shortUrl']['baseUrl'] ?? 'http://localhost:8080';
        return rtrim($baseUrl, '/') . '/' . $this->short_code;
    }

    /**
     * Find link by short code
     *
     * @param string $code The short code
     * @return static|null The link or null
     */
    public static function findByCode(string $code): ?self
    {
        return static::find()->where(['short_code' => $code])->one();
    }

    /**
     * Find link by original URL
     *
     * @param string $url The original URL
     * @return static|null The link or null
     */
    public static function findByOriginalUrl(string $url): ?self
    {
        return static::find()->where(['original_url' => $url])->one();
    }
}
