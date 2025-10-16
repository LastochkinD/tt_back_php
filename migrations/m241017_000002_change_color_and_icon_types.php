<?php

use yii\db\Migration;

/**
 * Handles changing color and icon_id types from VARCHAR(7) and INTEGER to VARCHAR(64) each.
 */
class m241017_000002_change_color_and_icon_types extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('{{%boards}}', 'color', $this->string(64));
        $this->alterColumn('{{%boards}}', 'icon_id', $this->string(64));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->alterColumn('{{%boards}}', 'color', $this->string(7));
        $this->alterColumn('{{%boards}}', 'icon_id', $this->integer());
    }
}
