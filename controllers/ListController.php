<?php

namespace app\controllers;

use Yii;
use app\models\ListEntity;
use app\models\BoardAccess;

class ListController extends \yii\rest\ActiveController
{
    public $modelClass = ListEntity::class;

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // JWT Authentication
        $behaviors['authenticator'] = [
            'class' => \app\filters\AuthFilter::class,
            'except' => ['options'],
        ];

        // Remove CORS from individual controllers to avoid conflicts with .htaccess

        return $behaviors;
    }

    // Override actions for custom logic
    public function actions()
    {
        $actions = parent::actions();
        // Customize actions as needed
        unset($actions['index'], $actions['view'], $actions['create'], $actions['update'], $actions['delete']); // We'll implement these custom
        return $actions;
    }

    public function actionIndex()
    {
        $boardId = Yii::$app->request->get('board') ?: Yii::$app->request->get('board_id');

        if ($boardId) {
            // Check if user has access to the board
            $userId = Yii::$app->user->id;
            if (!Yii::$app->boardAccessManager->canView($boardId, $userId)) {
                throw new \yii\web\ForbiddenHttpException('Access denied.');
            }

            $lists = ListEntity::find()
                ->where(['board_id' => $boardId])
                ->orderBy(['id' => SORT_ASC])
                ->all();
        } else {
            // Get all lists that user has access to through boards
            $userId = Yii::$app->user->id;
            $lists = ListEntity::find()
                ->joinWith(['board'])
                ->andWhere([
                    'OR',
                    ['boards.user_id' => $userId],
                    ['exists', BoardAccess::find()->where('{{%board_accesses}}.board_id = {{%lists}}.board_id AND {{%board_accesses}}.user_id = :userId', [':userId' => $userId])]
                ])
                ->orderBy(['lists.id' => SORT_ASC])
                ->all();
        }

        $result = [];
        foreach ($lists as $list) {
            $board = $list->board;
            $result[] = [
                'id' => $list->id,
                'title' => $list->title,
                'createdAt' => $list->created_at,
                'BoardId' => $list->board_id,
                'Board' => [
                    'id' => $board->id,
                    'title' => $board->title,
                    'description' => $board->description,
                ]
            ];
        }

        return $result;
    }

    public function findModel($id)
    {
        $model = ListEntity::findOne($id);
        if ($model === null) {
            throw new \yii\web\NotFoundHttpException('List not found');
        }
        return $model;
    }

    public function checkAccess($action, $model = null, $params = [])
    {
        $listId = $model ? $model->id : Yii::$app->request->get('id');
        $list = $model ?: ListEntity::findOne($listId);

        if (in_array($action, ['view'])) {
            if (!$list || !Yii::$app->boardAccessManager->canView($list->board_id, Yii::$app->user->id)) {
                throw new \yii\web\ForbiddenHttpException('Access denied.');
            }
        }

        if (in_array($action, ['update', 'delete'])) {
            if (!$list || !Yii::$app->boardAccessManager->canEdit($list->board_id, Yii::$app->user->id)) {
                throw new \yii\web\ForbiddenHttpException('Access denied.');
            }
        }
    }

    public function actionView($id)
    {
        $model = $this->findModel($id);
        $this->checkAccess('view', $model);

        $board = $model->board;
        return [
            'id' => $model->id,
            'title' => $model->title,
            'createdAt' => $model->created_at,
            'BoardId' => $model->board_id,
            'Board' => [
                'id' => $board->id,
                'title' => $board->title,
                'description' => $board->description,
            ]
        ];
    }

    public function actionCreate()
    {
        $userId = Yii::$app->user->id;
        $data = json_decode(Yii::$app->request->getRawBody(), true);
        if (!$data) {
            $data = Yii::$app->request->getBodyParams();
        }
        if (empty($data)) {
            $data = Yii::$app->request->post();
        }
        $boardId = $data['board'] ?? $data['board_id'] ?? null;

        if (!$boardId) {
            throw new \yii\web\BadRequestHttpException('board or board_id is required. Data: ' . json_encode($data));
        }

        // Check if board exists
        if (!\app\models\Board::findOne($boardId)) {
            throw new \yii\web\NotFoundHttpException('Board not found');
        }

        // Check if user can create lists on this board
        if (!Yii::$app->boardAccessManager->canEdit($boardId, $userId)) {
            throw new \yii\web\ForbiddenHttpException('Access denied.');
        }

        $list = new ListEntity();
        $list->load($data, '');
        $list->board_id = $boardId;

        if ($list->save()) {
            $board = $list->board;
            $result = [
                'id' => $list->id,
                'title' => $list->title,
                'createdAt' => $list->created_at,
                'BoardId' => $list->board_id
            ];
            Yii::$app->response->statusCode = 201;
            return $result;
        } else {
            throw new \yii\web\BadRequestHttpException('Validation error');
        }
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $this->checkAccess('update', $model);

        $data = json_decode(Yii::$app->request->getRawBody(), true);
        if (!$data) {
            $data = Yii::$app->request->getBodyParams();
        }
        if (empty($data)) {
            $data = Yii::$app->request->post();
        }
        $newBoardId = $data['board'] ?? $data['board_id'] ?? null;

        // If changing board, check access to new board
        if ($newBoardId && $newBoardId != $model->board_id) {
            if (!Yii::$app->boardAccessManager->canEdit($newBoardId, Yii::$app->user->id)) {
                throw new \yii\web\ForbiddenHttpException('Access denied to target board.');
            }
            $model->board_id = $newBoardId;
        }

        $model->load($data, '');
        if ($model->save()) {
            $board = $model->board;
            return [
                'id' => $model->id,
                'title' => $model->title,
                'createdAt' => $model->created_at,
                'BoardId' => $model->board_id
            ];
        } else {
            throw new \yii\web\BadRequestHttpException('Validation error: ' . implode(', ', $model->getErrorSummary(true)));
        }
    }

    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $this->checkAccess('delete', $model);

        if ($model->delete()) {
            Yii::$app->response->setStatusCode(200);
            return ['message' => 'List deleted'];
        } else {
            throw new \yii\web\ServerErrorHttpException('Failed to delete the list.');
        }
    }

    public function actionOptions()
    {
        Yii::$app->response->statusCode = 200;
        return [];
    }
}
