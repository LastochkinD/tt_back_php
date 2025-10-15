<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "lists".
 *
 * @property int $id
 * @property string $title
 * @property int $board_id
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Board $board
 * @property Card[] $cards
 */
class ListEntity extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'lists';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => \yii\behaviors\TimestampBehavior::class,
                'value' => new \yii\db\Expression('NOW()'),
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title', 'board_id'], 'required'],
            [['board_id'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['title'], 'string', 'max' => 255],
            [['board_id'], 'exist', 'skipOnError' => true, 'targetClass' => Board::class, 'targetAttribute' => ['board_id' => 'id']],
        ];
    }

    public function fields()
    {
        return array_merge(parent::fields(), [
            'BoardId' => 'board_id',
            'createdAt' => 'created_at',
        ]);
    }

    public function extraFields()
    {
        return array_merge(parent::extraFields(), [
            'Board',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'board_id' => 'Board ID',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[Board]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBoard()
    {
        return $this->hasOne(Board::class, ['id' => 'board_id']);
    }

    /**
     * Gets query for [[Cards]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCards()
    {
        return $this->hasMany(Card::class, ['list_id' => 'id']);
    }
}
