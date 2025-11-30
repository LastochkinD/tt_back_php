<?php

namespace app\controllers;

use Yii;
use app\models\Card;

class CardController extends \yii\rest\ActiveController
{
    public $modelClass = Card::class;

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
        $userId = Yii::$app->user->id;
        $listId = Yii::$app->request->get('list');

        if ($listId) {
            // Check if user has access to the board through the list
            if (!\app\models\ListEntity::find()
                ->joinWith(['board'])
                ->where(['lists.id' => $listId])
                ->andWhere([
                    'OR',
                    ['boards.user_id' => $userId],
                    ['exists', \app\models\BoardAccess::find()->where('{{%board_accesses}}.board_id = {{%lists}}.board_id AND {{%board_accesses}}.user_id = :userId', [':userId' => $userId])]
                ])
                ->one()) {
                throw new \yii\web\ForbiddenHttpException('Access denied.');
            }

            $cards = Card::find()
                ->with('list')
                ->where(['list_id' => $listId])
                ->orderBy(['id' => SORT_ASC])
                ->all();
        } else {
            // Get all cards that user has access to through boards
            $cards = Card::find()
                ->joinWith(['list.board'])
                ->andWhere([
                    'OR',
                    ['boards.user_id' => $userId],
                    ['exists', \app\models\BoardAccess::find()->where('{{%board_accesses}}.board_id = {{%lists}}.board_id AND {{%board_accesses}}.user_id = :userId', [':userId' => $userId])]
                ])
                ->with('list')
                ->orderBy(['cards.id' => SORT_ASC])
                ->all();
        }

        $result = [];
        foreach ($cards as $card) {
            $result[] = [
                'id' => $card->id,
                'title' => $card->title,
                'description' => $card->description,
                'assigneeId' => $card->assignee_id,
                'createdAt' => $card->created_at,
                'ListId' => $card->list_id,
                'List' => [
                    'id' => $card->list->id,
                    'title' => $card->list->title,
                    'BoardId' => $card->list->board_id,
                ]
            ];
        }

        return $result;
    }

    public function findModel($id)
    {
        $model = Card::findOne($id);
        if ($model === null) {
            throw new \yii\web\NotFoundHttpException('Card not found');
        }
        return $model;
    }

    public function checkAccess($action, $model = null, $params = [])
    {
        $cardId = $model ? $model->id : Yii::$app->request->get('id');
        $card = $model ?: Card::findOne($cardId);

        if (in_array($action, ['view'])) {
            if (!$card || !Yii::$app->boardAccessManager->canView($card->list->board_id, Yii::$app->user->id)) {
                throw new \yii\web\ForbiddenHttpException('Access denied.');
            }
        }

        if (in_array($action, ['update', 'delete'])) {
            if (!$card || !Yii::$app->boardAccessManager->canEdit($card->list->board_id, Yii::$app->user->id)) {
                throw new \yii\web\ForbiddenHttpException('Access denied.');
            }
        }
    }

    public function actionView($id)
    {
        $model = $this->findModel($id);
        $this->checkAccess('view', $model);

        $list = $model->list;
        return [
            'id' => $model->id,
            'title' => $model->title,
            'description' => $model->description,
            'assigneeId' => $model->assignee_id,
            'createdAt' => $model->created_at,
            'ListId' => $model->list_id,
            'List' => [
                'id' => $list->id,
                'title' => $list->title,
                'BoardId' => $list->board_id,
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

        // Map assigneeId to assignee_id for model loading
        if (isset($data['assigneeId'])) {
            $data['assignee_id'] = $data['assigneeId'];
        }

        $listId = $data['list'] ?? null;

        if (!$listId) {
            throw new \yii\web\BadRequestHttpException('list is required.');
        }

        // Check if list exists and get it
        $list = \app\models\ListEntity::findOne($listId);
        if (!$list) {
            throw new \yii\web\NotFoundHttpException('List not found');
        }

        // Check if user can create cards on this list's board
        if (!Yii::$app->boardAccessManager->canEdit($list->board_id, $userId)) {
            throw new \yii\web\ForbiddenHttpException('Access denied.');
        }

        $card = new Card();
        $card->load($data, '');
        $card->list_id = $listId;

        // Validate assignee_id if provided
        if ($card->assignee_id && !$card->validateAssignee()) {
            throw new \yii\web\BadRequestHttpException('Assignee does not have access to this board');
        }

        if ($card->save()) {
            $result = [
                'id' => $card->id,
                'title' => $card->title,
                'description' => $card->description,
                'assigneeId' => $card->assignee_id,
                'createdAt' => $card->created_at,
                'ListId' => $card->list_id
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

        // Map assigneeId to assignee_id for model loading
        if (isset($data['assigneeId'])) {
            $data['assignee_id'] = $data['assigneeId'];
        }

        $newListId = $data['list'] ?? null;

        // If changing list, check access to new list's board
        if ($newListId && $newListId != $model->list_id) {
            $newList = \app\models\ListEntity::findOne($newListId);
            if (!$newList || !Yii::$app->boardAccessManager->canEdit($newList->board_id, Yii::$app->user->id)) {
                throw new \yii\web\ForbiddenHttpException('Access denied to target list.');
            }
            $model->list_id = $newListId;
        }

        $model->load($data, '');

        // Validate assignee_id if provided
        // if ($model->assignee_id && !$model->validateAssignee()) {
        //     throw new \yii\web\BadRequestHttpException('Assignee does not have access to this board');
        // }

        if ($model->save()) {
            $list = $model->list;
            return [
                'id' => $model->id,
                'title' => $model->title,
                'description' => $model->description,
                'assigneeId' => $model->assignee_id,
                'createdAt' => $model->created_at,
                'ListId' => $model->list_id
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
            return ['message' => 'Card deleted'];
        } else {
            throw new \yii\web\ServerErrorHttpException('Failed to delete the card.');
        }
    }

    public function actionOptions()
    {
        Yii::$app->response->statusCode = 200;
        return [];
    }
}
