<?php
/**
 * Migration: Create Links Table
 *
 * This migration creates the links table for storing shortened URLs.
 * It includes the original URL, short code, click count, and timestamps.
 */

use yii\db\Migration;

/**
 * Class m230301_000001_create_links_table
 */
class m230301_000001_create_links_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp(): bool
    {
        // Create links table
        $this->createTable('{{%links}}', [
            'id' => $this->primaryKey(),
            'original_url' => $this->text()->notNull(),
            'short_code' => $this->string(10)->notNull()->unique(),
            'clicks' => $this->integer()->defaultValue(0),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->null(),
        ]);

        // Create index on short_code for faster lookups
        $this->createIndex(
            'idx-links-short_code',
            '{{%links}}',
            'short_code'
        );

        // Create index on original_url for faster lookups
        $this->createIndex(
            'idx-links-original_url',
            '{{%links}}',
            'original_url'
        );

        // Add comment to table
        $this->addCommentOnTable('{{%links}}', 'Table for storing shortened URLs');

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown(): bool
    {
        // Drop the table
        $this->dropTable('{{%links}}');

        return true;
    }
}
