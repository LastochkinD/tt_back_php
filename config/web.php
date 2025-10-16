<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'as corsFilter' => [
        'class' => \yii\filters\Cors::class,
        'cors' => [
            'Origin' => ['*'],
            'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
            'Access-Control-Request-Headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
            'Access-Control-Allow-Credentials' => false,
            'Access-Control-Max-Age' => 86401,
            'Access-Control-Allow-Headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
            'Access-Control-Expose-Headers' => [''],
        ],
    ],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'vzXOpkSr4vwJvMRkXTEalObPSZsR4TcJ',
            'enableCsrfValidation' => false,
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ],
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@app/mail',
            // send all mails to a file by default.
            'useFileTransport' => true,
        ],
        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning', 'info'],
                    'logFile' => '@runtime/logs/app.log',
                ],
            ],
        ],
        'db' => $db,
    'urlManager' => [
        'enablePrettyUrl' => true,
        'enableStrictParsing' => false,
        'showScriptName' => false,
        'suffix' => '',
            'rules' => [
                // Auth
                'POST api/auth/register' => 'auth/register',
                'POST api/auth/login' => 'auth/login',

                // Boards
                'GET api/boards' => 'board/index',
                'GET api/boards/<id>' => 'board/view',
                'POST api/boards' => 'board/create',
                'PUT api/boards/<id>' => 'board/update',
                'DELETE api/boards/<id>' => 'board/delete',

                // Lists
                'GET api/lists' => 'list/index',
                'POST api/lists' => 'list/create',
                'GET api/lists/<id>' => 'list/view',
                'PUT api/lists/<id>' => 'list/update',
                'DELETE api/lists/<id>' => 'list/delete',

                // Cards
                'GET api/cards' => 'card/index',
                'GET api/cards/<id>' => 'card/view',
                'POST api/cards' => 'card/create',
                'PUT api/cards/<id>' => 'card/update',
                'DELETE api/cards/<id>' => 'card/delete',

                // Comments
                'GET api/comments/card/<cardId>' => 'comment/card-comments',
                'GET api/comments/<id>' => 'comment/view',
                'POST api/comments' => 'comment/create',
                'PUT api/comments/<id>' => 'comment/update',
                'DELETE api/comments/<id>' => 'comment/delete',
            ],
        ],
        'jwt' => [
            'class' => \app\components\JwtComponent::class,
            'secret' => getenv('JWT_SECRET'),
        ],
        'boardAccessManager' => [
            'class' => 'app\components\BoardAccessManager',
        ],
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;
