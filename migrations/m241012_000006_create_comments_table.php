<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%comments}}`.
 */
class m241012_000006_create_comments_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%comments}}', [
            'id' => $this->primaryKey(),
            'text' => $this->text()->notNull(),
            'card_id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->notNull(),
            'created_at' => $this->db->schema->createColumnSchemaBuilder('timestamptz')->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->db->schema->createColumnSchemaBuilder('timestamptz')->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->addForeignKey(
            'fk-comments-card_id',
            '{{%comments}}',
            'card_id',
            '{{%cards}}',
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-comments-user_id',
            '{{%comments}}',
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
        $this->dropForeignKey('fk-comments-user_id', '{{%comments}}');
        $this->dropForeignKey('fk-comments-card_id', '{{%comments}}');
        $this->dropTable('{{%comments}}');
    }
}
