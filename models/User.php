<?php

namespace app\models;

use Yii;
use yii\base\NotSupportedException;

/**
 * User model
 *
 * @property int $id
 * @property string $email
 * @property string $password
 * @property string|null $name
 * @property string $created_at
 * @property string $updated_at
 */
class User extends \yii\db\ActiveRecord implements \yii\web\IdentityInterface
{
    public $username;

    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->username = $this->email; // Map email to username for compatibility
    }
    const SCENARIO_REGISTER = 'register';
    const SCENARIO_UPDATE = 'update';

    public static function tableName()
    {
        return 'users';
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_REGISTER] = ['email', 'password', 'name'];
        $scenarios[self::SCENARIO_UPDATE] = ['email', 'name', 'password'];
        return $scenarios;
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
            [['email'], 'required'],
            [['password'], 'required', 'on' => self::SCENARIO_REGISTER],
            [['name'], 'safe'],
            [['email'], 'email'],
            [['email'], 'unique'],
            [['password'], 'string', 'min' => 6],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'email' => 'Email',
            'password' => 'Password',
            'name' => 'Name',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        // Try to decode JWT token to get user ID
        $userId = Yii::$app->jwt->getUserIdFromToken($token);
        if (!$userId) {
            return null;
        }
        return static::findOne($userId);
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthKey()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Finds user by username (for compatibility with LoginForm)
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        return static::find()->where(['email' => $username])->one();
    }

    /**
     * Finds user by email
     *
     * @param string $email
     * @return static|null
     */
    public static function findByEmail($email)
    {
        return static::findOne(['email' => $email]);
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return \Yii::$app->security->validatePassword($password, $this->password);
    }

    /**
     * Generates password hash
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = \Yii::$app->security->generatePasswordHash($password);
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                $this->setPassword($this->password);
            } elseif ($this->isAttributeChanged('password')) {
                $this->setPassword($this->password);
            }
            return true;
        }
        return false;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBoards()
    {
        return $this->hasMany(Board::class, ['user_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBoardAccesses()
    {
        return $this->hasMany(BoardAccess::class, ['user_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getComments()
    {
        return $this->hasMany(Comment::class, ['user_id' => 'id']);
    }
}
