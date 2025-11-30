<?php

namespace app\controllers;

use Yii;
use app\models\User;

class UserController extends \yii\rest\ActiveController
{
    public $modelClass = User::class;

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // JWT Authentication
        $behaviors['authenticator'] = [
            'class' => \app\filters\AuthFilter::class,
            'except' => ['options'],
        ];

        return $behaviors;
    }

    // Override actions for custom logic
    public function actions()
    {
        $actions = parent::actions();
        // Customize actions as needed
        unset($actions['index'], $actions['view'], $actions['create'], $actions['update'], $actions['delete'], $actions['options']); // We'll implement these custom
        return $actions;
    }

    public function actionIndex()
    {
        $query = User::find();
        $request = Yii::$app->request;

        // Filter by all fields except password
        $filters = ['id', 'email', 'name', 'created_at', 'updated_at'];

        foreach ($filters as $field) {
            $value = $request->get($field);
            if ($value !== null) {
                $query->andWhere([$field => $value]);
            }
        }

        $users = $query->limit(20)->all();

        $result = [];
        foreach ($users as $user) {
            $result[] = $this->formatUser($user);
        }

        return $result;
    }

    public function findModel($id)
    {
        $model = User::findOne($id);
        if ($model === null) {
            throw new \yii\web\NotFoundHttpException('User not found');
        }
        return $model;
    }

    public function actionView($id)
    {
        $model = $this->findModel($id);
        return $this->formatUser($model);
    }

    public function actionOptions()
    {
        Yii::$app->response->statusCode = 200;
        return [];
    }

    /**
     * Format user data for response (exclude password)
     */
    private function formatUser($user)
    {
        return [
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }
}
