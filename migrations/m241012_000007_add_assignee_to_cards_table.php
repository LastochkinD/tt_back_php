<?php

use yii\db\Migration;

/**
 * Handles adding assignee_id column to table `{{%cards}}`.
 */
class m241012_000007_add_assignee_to_cards_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%cards}}', 'assignee_id', $this->integer()->null());

        $this->addForeignKey(
            'fk-cards-assignee_id',
            '{{%cards}}',
            'assignee_id',
            '{{%users}}',
            'id',
            'SET NULL'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-cards-assignee_id', '{{%cards}}');
        $this->dropColumn('{{%cards}}', 'assignee_id');
    }
}
