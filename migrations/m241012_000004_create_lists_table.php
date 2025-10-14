<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%lists}}`.
 */
class m241012_000004_create_lists_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%lists}}', [
            'id' => $this->primaryKey(),
            'title' => $this->string(255)->notNull(),
            'board_id' => $this->integer()->notNull(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->addForeignKey(
            'fk-lists-board_id',
            '{{%lists}}',
            'board_id',
            '{{%boards}}',
            'id',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-lists-board_id', '{{%lists}}');
        $this->dropTable('{{%lists}}');
    }
}
