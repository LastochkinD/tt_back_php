<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\models\Card;

class CardController extends Controller
{
    public function behaviors()
    {
        return [
            'corsFilter' => [
                'class' => \yii\filters\Cors::class,
                'cors' => [
                    'Origin' => ['*'],
                    'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                    'Access-Control-Request-Headers' => ['*'],
                    'Access-Control-Allow-Credentials' => null,
                    'Access-Control-Max-Age' => 86400,
                    'Access-Control-Expose-Headers' => [],
                ],
            ],
            'authFilter' => [
                'class' => \app\filters\AuthFilter::class,
            ],
        ];
    }

    public function actionIndex()
    {
        $userId = Yii::$app->user->id;
        $listId = Yii::$app->request->get('list_id');

        if (!$listId) {
            Yii::$app->response->statusCode = 400;
            return $this->asJson(['error' => 'list_id parameter is required']);
        }

        // Check if user has access to the board through the list
        $card = Card::find()
            ->joinWith(['list.board.boardAccesses'])
            ->where(['cards.list_id' => $listId])
            ->andWhere([
                'OR',
                ['boards.user_id' => $userId],
                ['board_accesses.user_id' => $userId]
            ])
            ->one();

        if (!$card && Card::find()->where(['list_id' => $listId])->exists()) {
            Yii::$app->response->statusCode = 403;
            return $this->asJson(['error' => 'Access denied']);
        }

        $cards = Card::find()
            ->where(['list_id' => $listId])
            ->all();

        return $this->asJson($cards);
    }

    public function actionView($id)
    {
        $userId = Yii::$app->user->id;

        $card = Card::find()
            ->joinWith(['list.board.boardAccesses'])
            ->where(['cards.id' => $id])
            ->andWhere([
                'OR',
                ['boards.user_id' => $userId],
                ['board_accesses.user_id' => $userId]
            ])
            ->one();

        if (!$card) {
            Yii::$app->response->statusCode = 404;
            return $this->asJson(['error' => 'Card not found or access denied']);
        }

        return $this->asJson($card);
    }

    public function actionCreate()
    {
        $userId = Yii::$app->user->id;
        $data = Yii::$app->request->post();
        $listId = $data['list_id'] ?? null;

        if (!$listId) {
            Yii::$app->response->statusCode = 400;
            return $this->asJson(['error' => 'list_id is required']);
        }

        // Check access through list's board
        $list = \app\models\ListEntity::find()
            ->joinWith(['board.boardAccesses'])
            ->where(['lists.id' => $listId])
            ->andWhere([
                'OR',
                ['boards.user_id' => $userId],
                ['board_accesses.user_id' => $userId]
            ])
            ->one();

        if (!$list) {
            Yii::$app->response->statusCode = 404;
            return $this->asJson(['error' => 'List not found or access denied']);
        }

        if (!Yii::$app->boardAccessManager->canEdit($list->board_id, $userId)) {
            Yii::$app->response->statusCode = 403;
            return $this->asJson(['error' => 'Access denied']);
        }

        $card = new Card();
        $card->load($data, '');

        if ($card->validate() && $card->save()) {
            Yii::$app->response->statusCode = 201;
            return $this->asJson($card);
        } else {
            Yii::$app->response->statusCode = 400;
            return $this->asJson(['errors' => $card->errors]);
        }
    }

    public function actionUpdate($id)
    {
        $userId = Yii::$app->user->id;
        $data = Yii::$app->request->post();

        $card = Card::find()
            ->joinWith(['list.board'])
            ->where(['cards.id' => $id])
            ->one();

        if (!$card) {
            Yii::$app->response->statusCode = 404;
            return $this->asJson(['error' => 'Card not found']);
        }

        // Check access
        if (!Yii::$app->boardAccessManager->canEdit($card->list->board_id, $userId)) {
            Yii::$app->response->statusCode = 403;
            return $this->asJson(['error' => 'Access denied']);
        }

        $card->load($data, '');

        if ($card->validate() && $card->save()) {
            return $this->asJson($card);
        } else {
            Yii::$app->response->statusCode = 400;
            return $this->asJson(['errors' => $card->errors]);
        }
    }

    public function actionDelete($id)
    {
        $userId = Yii::$app->user->id;

        $card = Card::find()
            ->joinWith(['list.board'])
            ->where(['cards.id' => $id])
            ->one();

        if (!$card) {
            Yii::$app->response->statusCode = 404;
            return $this->asJson(['error' => 'Card not found']);
        }

        // Check access
        if (!Yii::$app->boardAccessManager->canEdit($card->list->board_id, $userId)) {
            Yii::$app->response->statusCode = 403;
            return $this->asJson(['error' => 'Access denied']);
        }

        $card->delete();
        return $this->asJson(['message' => 'Card deleted successfully']);
    }
}
