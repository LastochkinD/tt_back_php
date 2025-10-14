<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "cards".
 *
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property int $list_id
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Comment[] $comments
 * @property ListEntity $list
 */
class Card extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cards';
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
            [['title', 'list_id'], 'required'],
            [['description'], 'string'],
            [['list_id'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['title'], 'string', 'max' => 255],
            [['list_id'], 'exist', 'skipOnError' => true, 'targetClass' => ListEntity::class, 'targetAttribute' => ['list_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'description' => 'Description',
            'list_id' => 'List ID',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[Comments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getComments()
    {
        return $this->hasMany(Comment::class, ['card_id' => 'id']);
    }

    public function getList()
    {
        return $this->hasOne(ListEntity::class, ['id' => 'list_id']);
    }

    public function getBoard()
    {
        return $this->hasOne(Board::class, ['id' => 'board_id']);
    }
}
