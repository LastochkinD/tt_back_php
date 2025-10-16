<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%boards}}`.
 */
class m241012_000002_create_boards_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%boards}}', [
            'id' => $this->primaryKey(),
            'title' => $this->string(255)->notNull(),
            'description' => $this->text(),
            'color' => $this->string(7), // hex color code
            'icon_id' => $this->integer(),
            'user_id' => $this->integer(),
            'created_at' => $this->db->schema->createColumnSchemaBuilder('timestamptz')->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->db->schema->createColumnSchemaBuilder('timestamptz')->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->addForeignKey(
            'fk-boards-user_id',
            '{{%boards}}',
            'user_id',
            '{{%users}}',
            'id',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-boards-user_id', '{{%boards}}');
        $this->dropTable('{{%boards}}');
    }
}
