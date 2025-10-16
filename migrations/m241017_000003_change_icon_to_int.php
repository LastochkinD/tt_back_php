<?php

use yii\db\Migration;

/**
 * Handles changing icon_id type from VARCHAR(64) to INTEGER.
 */
class m241017_000003_change_icon_to_int extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Convert icon_id back to INTEGER, setting NULL for non-numeric values
        $this->alterColumn('{{%boards}}', 'icon_id', $this->integer());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->alterColumn('{{%boards}}', 'icon_id', $this->string(64));
    }
}
