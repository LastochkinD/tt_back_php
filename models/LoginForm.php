<?php

namespace app\models;

use Yii;
use yii\base\Model;

/**
 * LoginForm is the model behind the login form.
 *
 * @property-read User|null $user
 *
 */
class LoginForm extends Model
{
    public $username;
    public $password;
    public $rememberMe = true;

    private $_user = false;


    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            // username and password are both required
            [['username', 'password'], 'required'],
            // rememberMe must be a boolean value
            ['rememberMe', 'boolean'],
            // password is validated by validatePassword()
            ['password', 'validatePassword'],
        ];
    }

    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     *
     * @param string $attribute the attribute currently being validated
     * @param array $params the additional name-value pairs given in the rule
     */
    public function validatePassword($attribute, $params)
    {
        try {
            $user = $this->getUser();
            \Yii::info('LoginForm validatePassword: username = ' . $this->username . ', user found = ' . ($user ? 'true' : 'false'), 'debug');

            if (!$user) {
                \Yii::info('LoginForm validatePassword: User not found for username: ' . $this->username, 'debug');
                $this->addError($attribute, 'Incorrect username or password.');
            } elseif (!$user->validatePassword($this->password)) {
                \Yii::info('LoginForm validatePassword: Invalid password for user: ' . $user->email, 'debug');
                $this->addError($attribute, 'Incorrect username or password.');
            } else {
                \Yii::info('LoginForm validatePassword: Password validation successful for user: ' . $user->email, 'debug');
            }
        } catch (\Exception $e) {
            \Yii::info('LoginForm validatePassword: Exception during validation: ' . $e->getMessage(), 'debug');
            $this->addError($attribute, 'Ошибка подключения к базе данных');
        }
    }

    /**
     * Logs in a user using the provided username and password.
     * @return bool whether the user is logged in successfully
     */
    public function login()
    {
        try {
            if ($this->validate()) {
                return Yii::$app->user->login($this->getUser(), $this->rememberMe ? 3600*24*30 : 0);
            }
        } catch (\Exception $e) {
            // Database connection error
            throw $e;
        }
        return false;
    }

    /**
     * Finds user by [[username]]
     *
     * @return User|null
     */
    public function getUser()
    {
        if ($this->_user === false) {
            try {
                $this->_user = User::findByUsername($this->username);
            } catch (\Exception $e) {
                // Database connection error
                $this->_user = null;
            }
        }

        return $this->_user;
    }
}
