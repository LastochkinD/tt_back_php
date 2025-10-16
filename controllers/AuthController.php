<?php

namespace app\controllers;

use Yii;
use app\models\User;
use app\models\LoginForm;

class AuthController extends \yii\rest\Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // CORS
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::class,
            'cors' => [
                'Origin' => ['*'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'DELETE', 'HEAD', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => false,
                'Access-Control-Max-Age' => 86400,
                'Access-Control-Allow-Headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
            ],
        ];

        $behaviors['contentNegotiator'] = [
            'class' => \yii\filters\ContentNegotiator::class,
            'formats' => [
                'application/json' => \yii\web\Response::FORMAT_JSON,
            ],
        ];
        return $behaviors;
    }

    public function actionLogin()
    {
        try {
            $model = new LoginForm();
            $data = Yii::$app->request->getBodyParams();

            // Отладочная информация: входные данные
            Yii::info('Login request data: ' . json_encode($data), 'debug');

            // Map email to username for LoginForm compatibility
            if (isset($data['email'])) {
                $data['username'] = $data['email'];
                Yii::info('Mapped email to username: ' . $data['username'], 'debug');
            }

            Yii::info('Data after mapping: ' . json_encode($data), 'debug');

            // Принудительно загружаем данные
            $loadResult = $model->load($data, '');
            Yii::info('Model load result: ' . ($loadResult ? 'true' : 'false'), 'debug');
            Yii::info('Model attributes after load: ' . json_encode($model->attributes), 'debug');

            // Принудительно вызываем валидацию и логируем после каждого шага
            Yii::info('Calling validate()...', 'debug');
            $validateResult = $model->validate();
            Yii::info('Validate result: ' . ($validateResult ? 'true' : 'false'), 'debug');

            // Всегда получаем ошибки, даже если валидация прошла
            $errorsAfterValidate = $model->getErrors();
            Yii::info('Errors after validate(): ' . json_encode($errorsAfterValidate), 'debug');

            // Проверяем ошибки по отдельным атрибутам
            Yii::info('Username errors: ' . json_encode($model->getErrors('username')), 'debug');
            Yii::info('Password errors: ' . json_encode($model->getErrors('password')), 'debug');
            Yii::info('RememberMe errors: ' . json_encode($model->getErrors('rememberMe')), 'debug');

            if ($validateResult) {
                $user = $model->user;
                $token = Yii::$app->jwt->generateToken($user->id);

                // Вывод отладочной информации в консоль
                Yii::info('User data from database: ' . json_encode($user->attributes), 'debug');

                return [
                    'user' => [
                        'id' => $user->id,
                        'email' => $user->email,
                        'name' => $user->name,
                    ],
                    'token' => (string) $token,
                    'debug' => $user->attributes, // Отладочная информация: данные пользователя из базы
                ];
            } else {
                // Отладка в случае неудачи
                $errors = $model->getErrors();
                Yii::info('Login validation failed. Model errors: ' . json_encode($errors), 'debug');
                return ['message' => 'Invalid credentials', 'debug_errors' => $errors];
            }
        } catch (\Exception $e) {
            Yii::error('Login exception: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString(), 'debug');
            return ['message' => 'Database connection error: ' . $e->getMessage()];
        }
    }

    public function actionRegister()
    {
        try {
            $user = new User(['scenario' => User::SCENARIO_REGISTER]);
            $data = Yii::$app->request->getBodyParams();

            if ($user->load($data, '') && $user->save()) {
                $token = Yii::$app->jwt->generateToken($user->id);

                Yii::$app->response->statusCode = 201;

                return [
                    'user' => [
                        'id' => $user->id,
                        'email' => $user->email,
                        'name' => $user->name,
                    ],
                    'token' => (string) $token,
                ];
            }
            return ['message' => 'User already exists'];
        } catch (\Exception $e) {
            return ['message' => 'Database connection error: ' . $e->getMessage()];
        }
    }
}
