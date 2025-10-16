<?php

use yii\db\Migration;

/**
 * Handles adding color and icon_id to table `{{%boards}}`.
 */
class m241016_000001_add_color_and_icon_to_boards_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%boards}}', 'color', $this->string(7)->after('description'));
        $this->addColumn('{{%boards}}', 'icon_id', $this->integer()->after('color'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%boards}}', 'icon_id');
        $this->dropColumn('{{%boards}}', 'color');
    }
}
