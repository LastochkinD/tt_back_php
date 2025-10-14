<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "board_accesses".
 *
 * @property int $id
 * @property int $board_id
 * @property int $user_id
 * @property string $role
 * @property string $created_at
 * @property string $updated_at
 */
class BoardAccess extends \yii\db\ActiveRecord
{
    const ROLE_VIEWER = 'viewer';
    const ROLE_EDITOR = 'editor';
    const ROLE_ADMIN = 'admin';

    public static function tableName()
    {
        return 'board_accesses';
    }

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

    public function rules()
    {
        return [
            [['board_id', 'user_id', 'role'], 'required'],
            [['board_id', 'user_id'], 'integer'],
            [['role'], 'string', 'max' => 50],
            [['role'], 'in', 'range' => ['viewer', 'editor', 'admin']],
            [['created_at', 'updated_at'], 'safe'],
            [['board_id', 'user_id'], 'unique', 'targetAttribute' => ['board_id', 'user_id']],
            [['board_id'], 'exist', 'skipOnError' => true, 'targetClass' => Board::class, 'targetAttribute' => ['board_id' => 'id']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'board_id' => 'Board ID',
            'user_id' => 'User ID',
            'role' => 'Role',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public function getBoard()
    {
        return $this->hasOne(Board::class, ['id' => 'board_id']);
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}
