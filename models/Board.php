<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "boards".
 *
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property string|null $color
 * @property int|null $icon_id
 * @property int|null $user_id
 * @property string $created_at
 * @property string $updated_at
 */
class Board extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'boards';
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
            [['title'], 'required'],
            [['description'], 'string'],
            [['color'], 'string', 'max' => 64],
            [['icon_id'], 'integer'],
            [['user_id'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['title'], 'string', 'max' => 255],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
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
            'color' => 'Color',
            'icon_id' => 'Icon ID',
            'user_id' => 'Owner ID',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    // Relationships
    public function getOwner() { return $this->hasOne(User::class, ['id' => 'user_id']); }
    public function getLists() { return $this->hasMany(ListEntity::class, ['board_id' => 'id']); }
    public function getCards() { return $this->hasMany(Card::class, ['board_id' => 'id']); }
    public function getBoardAccesses() { return $this->hasMany(BoardAccess::class, ['board_id' => 'id']); }
    public function getUsers() { return $this->hasMany(User::class, ['id' => 'user_id'])->via('boardAccesses'); }
    public function getMembers() { return $this->hasMany(User::class, ['id' => 'user_id'])->via('boardAccesses'); }
}
