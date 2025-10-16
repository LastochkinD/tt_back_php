<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%cards}}`.
 */
class m241012_000005_create_cards_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%cards}}', [
            'id' => $this->primaryKey(),
            'title' => $this->string(255)->notNull(),
            'description' => $this->text(),
            'list_id' => $this->integer()->notNull(),
            'created_at' => $this->db->schema->createColumnSchemaBuilder('timestamptz')->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->db->schema->createColumnSchemaBuilder('timestamptz')->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->addForeignKey(
            'fk-cards-list_id',
            '{{%cards}}',
            'list_id',
            '{{%lists}}',
            'id',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-cards-list_id', '{{%cards}}');
        $this->dropTable('{{%cards}}');
    }
}
