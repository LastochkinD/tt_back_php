<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%board_accesses}}`.
 */
class m241012_000003_create_board_access_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->execute("CREATE TYPE public.enum_board_accesses_role AS ENUM (
    'viewer',
    'editor',
    'admin'
);");

        $this->createTable('{{%board_accesses}}', [
            'id' => $this->primaryKey(),
            'board_id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->notNull(),
            'role' => "public.enum_board_accesses_role DEFAULT 'viewer'::public.enum_board_accesses_role NOT NULL",
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->addForeignKey(
            'fk-board_accesses-board_id',
            '{{%board_accesses}}',
            'board_id',
            '{{%boards}}',
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-board_accesses-user_id',
            '{{%board_accesses}}',
            'user_id',
            '{{%users}}',
            'id',
            'CASCADE'
        );

        $this->createIndex('unique_board_user_access', '{{%board_accesses}}', ['board_id', 'user_id'], true);
        $this->createIndex('board_access_user_index', '{{%board_accesses}}', 'user_id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('board_access_user_index', '{{%board_accesses}}');
        $this->dropIndex('unique_board_user_access', '{{%board_accesses}}');
        $this->dropForeignKey('fk-board_accesses-user_id', '{{%board_accesses}}');
        $this->dropForeignKey('fk-board_accesses-board_id', '{{%board_accesses}}');
        $this->dropTable('{{%board_accesses}}');
        $this->execute("DROP TYPE public.enum_board_accesses_role;");
    }
}
