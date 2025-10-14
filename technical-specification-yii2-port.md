# Technical Specification: Port Task-Tracker Backend to PHP Yii2

## Overview

This document provides a detailed technical specification for porting the existing Node.js/Express task tracker backend to PHP using the Yii2 framework. The current application is a comprehensive task management system with user authentication, board management, lists, cards, comments, and role-based access control.

### Current Backend Analysis

**Technology Stack:**
- **Framework**: Node.js + Express.js v5.1.0
- **ORM**: Sequelize v6.37.7
- **Database**: PostgreSQL
- **Authentication**: JWT (jsonwebtoken v9.0.2)
- **Password Hashing**: bcryptjs v3.0.2
- **CORS**: cors v2.8.5
- **Environment**: dotenv v17.2.3

**Dependencies:**
- axios: ^1.12.2
- express: ^5.1.0
- jsonwebtoken: ^9.0.2
- pg: ^8.16.3
- sequelize: ^6.37.7
- bcryptjs: ^3.0.2
- cors: ^2.8.5
- dotenv: ^17.2.3

**Project Structure:**
- Authentication routes: `/api/auth`
- Board management: `/api/boards`
- Board access control: `/api/board-access`
- Lists: `/api/lists`
- Cards: `/api/cards`
- Comments: `/api/comments`

## Database Schema Analysis

### User Model
```javascript
{
  email: STRING, // unique, not null
  password: STRING, // hashed, not null
  name: STRING // nullable
}
```
- Uses bcrypt for password hashing
- Has associations with Boards (ownedBoards, boards through BoardAccess), BoardAccess, Comments

### Board Model
```javascript
{
  title: STRING, // not null
  description: TEXT, // nullable
  userId: INTEGER, // foreign key, nullable for backward compatibility
  createdAt: DATE // auto-generated
}
```
- Belongs to User as owner
- Has many BoardAccess entries
- Many-to-many with Users through BoardAccess

### BoardAccess Model (Many-to-many relationship)
```javascript
{
  id: INTEGER, // primary key
  boardId: INTEGER, // foreign key, not null
  userId: INTEGER, // foreign key, not null
  role: ENUM('viewer', 'editor', 'admin') // default 'viewer'
}
```

### List Model
```javascript
{
  title: STRING, // not null
  createdAt: DATE // auto-generated
}
```
- Belongs to Board
- Has many Cards

### Card Model
```javascript
{
  title: STRING, // not null
  description: TEXT, // nullable
  createdAt: DATE // auto-generated
}
```
- Belongs to List and Board
- Has many Comments

### Comment Model
```javascript
{
  text: TEXT, // not null
  createdAt: DATE, // auto-generated
  updatedAt: DATE // auto-generated
}
```
- Belongs to Card and User

## Yii2 Implementation Requirements

### Framework Setup
- **Yii2 Version**: 2.0.45 or later (LTS)
- **Extensions Required**:
  - `yiisoft/yii2-jui` (for UI components if needed)
  - `yiisoft/yii2-bootstrap` (for styling)
  - `yiisoft/yii2-httpclient` (for API calls)
  - `sizeg/yii2-jwt` (for JWT authentication)
  - `yiisoft/yii2-swiftmailer` (for email if needed)

### Project Structure
```
task-tracker-yii2/
├── backend/
│   ├── config/
│   │   ├── main.php
│   │   ├── db.php
│   │   └── params.php
│   ├── controllers/
│   │   ├── AuthController.php
│   │   ├── BoardController.php
│   │   ├── ListController.php
│   │   ├── CardController.php
│   │   ├── CommentController.php
│   │   └── BoardAccessController.php
│   ├── models/
│   │   ├── User.php
│   │   ├── Board.php
│   │   ├── ListModel.php (since List is reserved)
│   │   ├── Card.php
│   │   ├── Comment.php
│   │   ├── BoardAccess.php
│   │   └── LoginForm.php
│   ├── runtime/
│   ├── web/
│   ├── yiic
│   └── composer.json
├── console/
│   ├── config/
│   ├── controllers/
│   ├── migrations/
│   └── yiic
└── vendor/
```

## Detailed Model Implementations

### User Model (User.php)
```php
class User extends \yii\db\ActiveRecord implements \yii\web\IdentityInterface
{
    const SCENARIO_REGISTER = 'register';
    const SCENARIO_UPDATE = 'update';

    public static function tableName() { return 'users'; }

    public function scenarios() {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_REGISTER] = ['email', 'password', 'name'];
        $scenarios[self::SCENARIO_UPDATE] = ['email', 'name', 'password'];
        return $scenarios;
    }

    public function rules() {
        return [
            [['email'], 'required'],
            [['password'], 'required', 'on' => self::SCENARIO_REGISTER],
            [['name'], 'safe'],
            [['email'], 'email'],
            [['email'], 'unique'],
            [['password'], 'string', 'min' => 6],
        ];
    }

    public function behaviors() {
        return [
            \yii\behaviors\TimestampBehavior::class,
        ];
    }

    // Hash password before save
    public function beforeSave($insert) {
        if ($this->isAttributeChanged('password')) {
            $this->password = Yii::$app->security->generatePasswordHash($this->password);
        }
        return parent::beforeSave($insert);
    }

    // Relationships
    public function getBoards() { return $this->hasMany(Board::class, ['user_id' => 'id']); }
    public function getBoardAccesses() { return $this->hasMany(BoardAccess::class, ['user_id' => 'id']); }
    public function getComments() { return $this->hasMany(Comment::class, ['user_id' => 'id']); }
    public function getOwnedBoards() { return $this->hasMany(Board::class, ['user_id' => 'id']); }
    public function getSharedBoards() { return $this->hasMany(Board::class, ['id' => 'board_id'])->via('boardAccesses'); }

    // Identity interface methods
    public static function findIdentity($id) { return static::findOne($id); }
    public function getId() { return $this->id; }
    public function getAuthKey() { return $this->auth_key; }
    public function validateAuthKey($authKey) { return $this->auth_key === $authKey; }
    public function validatePassword($password) { return Yii::$app->security->validatePassword($password, $this->password); }
    public static function findByUsername($username) { return static::findOne(['email' => $username]); }
}
```

### Board Model (Board.php)
```php
class Board extends \yii\db\ActiveRecord
{
    public static function tableName() { return 'boards'; }

    public function rules() {
        return [
            [['title'], 'required'],
            [['title'], 'string', 'max' => 255],
            [['description'], 'string'],
            [['user_id'], 'integer'],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    public function behaviors() {
        return [
            \yii\behaviors\TimestampBehavior::class,
        ];
    }

    // Relationships
    public function getOwner() { return $this->hasOne(User::class, ['id' => 'user_id']); }
    public function getLists() { return $this->hasMany(ListModel::class, ['board_id' => 'id']); }
    public function getCards() { return $this->hasMany(Card::class, ['board_id' => 'id']); }
    public function getBoardAccesses() { return $this->hasMany(BoardAccess::class, ['board_id' => 'id']); }
    public function getUsers() { return $this->hasMany(User::class, ['id' => 'user_id'])->via('boardAccesses'); }
    public function getMembers() { return $this->hasMany(User::class, ['id' => 'user_id'])->via('boardAccesses'); }
}
```

## API Controller Implementations

### Authentication Controller (AuthController.php)
```php
class AuthController extends \yii\rest\Controller
{
    public function behaviors() {
        $behaviors = parent::behaviors();
        $behaviors['cors'] = [
            'class' => \yii\filters\Cors::class,
        ];
        $behaviors['contentNegotiator'] = [
            'class' => \yii\filters\ContentNegotiator::class,
            'formats' => [
                'application/json' => \yii\web\Response::FORMAT_JSON,
            ],
        ];
        return $behaviors;
    }

    public function actionLogin() {
        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post(), '') && $model->login()) {
            $user = $model->user;
            $token = \sizeg\jwt\Jwt::getToken([
                'iss' => 'task-tracker',
                'aud' => 'task-tracker-users',
                'iat' => time(),
                'exp' => time() + 86400, // 1 day
                'uid' => $user->id,
                'email' => $user->email,
            ]);

            return [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                ],
            ];
        }
        return $model;
    }

    public function actionRegister() {
        $user = new User(['scenario' => User::SCENARIO_REGISTER]);
        if ($user->load(Yii::$app->request->post(), '') && $user->save()) {
            $token = \sizeg\jwt\Jwt::getToken([
                'iss' => 'task-tracker',
                'aud' => 'task-tracker-users',
                'iat' => time(),
                'exp' => time() + 86400,
                'uid' => $user->id,
                'email' => $user->email,
            ]);

            return [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                ],
            ];
        }
        return $user;
    }
}
```

### Board Controller (BoardController.php)
```php
class BoardController extends \yii\rest\ActiveController
{
    public $modelClass = Board::class;

    public function behaviors() {
        $behaviors = parent::behaviors();

        // JWT Authentication
        $behaviors['authenticator'] = [
            'class' => \sizeg\jwt\JwtHttpBearerAuth::class,
            'except' => ['options'],
        ];

        // CORS
        $behaviors['cors'] = [
            'class' => \yii\filters\Cors::class,
        ];

        return $behaviors;
    }

    // Override actions for custom logic
    public function actions() {
        $actions = parent::actions();
        // Customize actions as needed
        unset($actions['create'], $actions['update'], $actions['delete']); // We'll implement these custom
        return $actions;
    }

    public function actionCreate() {
        $board = new Board();
        $board->load(Yii::$app->request->post(), '');
        $board->user_id = Yii::$app->user->id;

        if ($board->save()) {
            // Create board access for owner
            $access = new BoardAccess([
                'board_id' => $board->id,
                'user_id' => Yii::$app->user->id,
                'role' => BoardAccess::ROLE_ADMIN,
            ]);
            $access->save();

            return $board;
        }
        return $board;
    }

    public function checkAccess($action, $model = null, $params = []) {
        if (in_array($action, ['view', 'update', 'delete'])) {
            $boardId = $model ? $model->id : Yii::$app->request->get('id');
            if (!$this->checkBoardAccess($boardId, Yii::$app->user->id)) {
                throw new \yii\web\ForbiddenHttpException('Access denied.');
            }
        }
    }

    private function checkBoardAccess($boardId, $userId) {
        // Implementation of access checking logic
        $access = BoardAccess::find()->where(['board_id' => $boardId, 'user_id' => $userId])->one();
        return $access !== null;
    }
}
```

## Database Migrations

### Base Migration Structure
All migrations should extend `yii\db\Migration` and use the following structure:

```php
class m241013_000000_create_boards_table extends \yii\db\Migration
{
    public function safeUp() {
        // Create table logic
    }

    public function safeDown() {
        // Drop table logic
    }
}
```

### Key Migration Classes
1. `m241013_000001_create_users_table` - User table with email, password, name
2. `m241013_000002_create_boards_table` - Board table with title, description, user_id
3. `m241013_000003_create_lists_table` - List table
4. `m241013_000004_create_cards_table` - Card table
5. `m241013_000005_create_comments_table` - Comment table
6. `m241013_000006_create_board_access_table` - BoardAccess junction table

## Access Control Implementation

### BoardAccess Model
```php
class BoardAccess extends \yii\db\ActiveRecord
{
    const ROLE_VIEWER = 'viewer';
    const ROLE_EDITOR = 'editor';
    const ROLE_ADMIN = 'admin';

    public static function tableName() { return 'board_access'; }

    // Relationships and rules implementation
}
```

### Access Control Helper
```php
class BoardAccessHelper
{
    public static function checkAccess($boardId, $userId, $requiredRole = BoardAccess::ROLE_VIEWER) {
        $access = BoardAccess::find()
            ->where(['board_id' => $boardId, 'user_id' => $userId])
            ->one();

        if (!$access) return false;

        $roleHierarchy = [
            BoardAccess::ROLE_VIEWER => 1,
            BoardAccess::ROLE_EDITOR => 2,
            BoardAccess::ROLE_ADMIN => 3,
        ];

        return $roleHierarchy[$access->role] >= $roleHierarchy[$requiredRole];
    }

    public static function grantAccess($boardId, $userId, $role = BoardAccess::ROLE_VIEWER) {
        $access = BoardAccess::find()
            ->where(['board_id' => $boardId, 'user_id' => $userId])
            ->one();

        if (!$access) {
            $access = new BoardAccess([
                'board_id' => $boardId,
                'user_id' => $userId,
                'role' => $role,
            ]);
        } else {
            $access->role = $role;
        }

        return $access->save();
    }
}
```

## Configuration Files

### Main Configuration (main.php)
```php
$config = [
    'id' => 'task-tracker-backend',
    'basePath' => dirname(__DIR__),
    'components' => [
        'request' => [
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ]
        ],
        'response' => [
            'format' => \yii\web\Response::FORMAT_JSON,
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
        ],
        'jwt' => [
            'class' => \sizeg\jwt\Jwt::class,
            'key' => getenv('JWT_SECRET'),
            'jwtValidationData' => \sizeg\jwt\Jwt::getDefaultValidationData(),
        ],
        'db' => require __DIR__ . '/db.php',
        // ... other components
    ],
];
```

## Implementation Phases

### Phase 1: Core Setup
- [ ] Create Yii2 application structure
- [ ] Configure PostgreSQL database
- [ ] Create User model with IdentityInterface
- [ ] Implement JWT authentication
- [ ] Create migrations for all tables

### Phase 2: Basic CRUD
- [ ] Implement AuthController (login/register)
- [ ] Implement BoardController with access control
- [ ] Add ListController, CardController, CommentController
- [ ] Create BoardAccessController

### Phase 3: Security & Testing
- [ ] Implement full access control system
- [ ] Add comprehensive validation rules
- [ ] Create API tests
- [ ] Performance optimization

### Phase 4: Refinement
- [ ] Add error handling and logging
- [ ] Implement rate limiting
- [ ] Add caching where appropriate
- [ ] Documentation and deployment

## API Endpoints Mapping

| Node.js Route | HTTP Method | Yii2 Action | Controller |
|---------------|-------------|-------------|------------|
| `POST /api/auth/login` | POST | `actionLogin` | AuthController |
| `POST /api/auth/register` | POST | `actionCreate` | AuthController |
| `GET /api/boards` | GET | `actionIndex` | BoardController |
| `POST /api/boards` | POST | `actionCreate` | BoardController |
| `GET /api/boards/:id` | GET | `actionView` | BoardController |
| `PUT /api/boards/:id` | PUT | `actionUpdate` | BoardController |
| `DELETE /api/boards/:id` | DELETE | `actionDelete` | BoardController |
| `GET /api/lists` | GET | `actionIndex` | ListController |
| `POST /api/lists` | POST | `actionCreate` | ListController |
| `GET /api/lists/:id` | GET | `actionView` | ListController |
| `PUT /api/lists/:id` | PUT | `actionUpdate` | ListController |
| `DELETE /api/lists/:id` | DELETE | `actionDelete` | ListController |
| `GET /api/cards` | GET | `actionIndex` | CardController |
| `POST /api/cards` | POST | `actionCreate` | CardController |
| `GET /api/cards/:id` | GET | `actionView` | CardController |
| `PUT /api/cards/:id` | PUT | `actionUpdate` | CardController |
| `DELETE /api/cards/:id` | DELETE | `actionDelete` | CardController |
| `GET /api/comments` | GET | `actionIndex` | CommentController |
| `POST /api/comments` | POST | `actionCreate` | CommentController |
| `DELETE /api/comments/:id` | DELETE | `actionDelete` | CommentController |

## Security Considerations

1. **Password Security**: Use Yii2's security component for hashing
2. **JWT Tokens**: Implement proper expiration and validation
3. **CORS**: Configure based on frontend domains
4. **Rate Limiting**: Implement on authentication endpoints
5. **Input Validation**: Use comprehensive model validation rules
6. **SQL Injection**: Rely on ActiveRecord query building
7. **XSS Protection**: Proper content escaping in responses
8. **HTTPS**: Ensure all communications are encrypted

This specification provides a complete blueprint for porting the task tracker backend from Node.js/Express to PHP Yii2 while maintaining all functionality and improving the architecture with Yii2's robust features.
