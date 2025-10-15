# Task Tracker API Documentation - Authentication

Этот документ описывает систему авторизации API Task Tracker приложения.

## Base URL
Все endpoints начинаются с base URL сервера (например, `http://localhost:5000/api`).

## Обзор авторизации

API использует JWT (JSON Web Tokens) для авторизации. После успешного входа пользователь получает токен, который должен передаваться в заголовке `Authorization` для всех защищенных endpoints.

**Формат заголовка:**
```
Authorization: Bearer <token>
```

## Public endpoints
- POST /api/auth/register - регистрация пользователя
- POST /api/auth/login - вход в систему

## Protected endpoints
Все остальные endpoints требуют авторизации:
- /api/boards
- /api/lists
- /api/cards

## Error Response Format
Все error responses имеют формат:
```json
{
  "message": "Описание ошибки"
}
```

## Authentication Endpoints

### POST /api/auth/register

Регистрация нового пользователя.

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "password123",
  "name": "User Name"
}
```

**Response (201):**
```json
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": 1,
    "email": "user@example.com",
    "name": "User Name"
  }
}
```

**Error (400):**
```json
{
  "message": "User already exists"
}
```

### POST /api/auth/login

Вход в систему.

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Response (200):**
```json
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": 1,
    "email": "user@example.com",
    "name": "User Name"
  }
}
```

**Error (400):**
```json
{
  "message": "Invalid credentials"
}
```

## Frontend Integration

### Сохранение токена

После успешного входа/регистрации сохраните token в localStorage или sessionStorage:

```javascript
// JavaScript/TypeScript
const response = await fetch('/api/auth/login', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    email: 'test@example.com',
    password: 'testpass123'
  })
});

const data = await response.json();

// Сохраните токен
localStorage.setItem('authToken', data.token);
// Сохраните информацию о пользователе
localStorage.setItem('user', JSON.stringify(data.user));
```

### Отправка авторизованных запросов

Для всех защищенных запросов добавляйте Authorization header:

```javascript
// JavaScript/TypeScript
const token = localStorage.getItem('authToken');

const response = await fetch('/api/boards', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
});
```

### Проверка статуса авторизации

```javascript
// Проверка, авторизован ли пользователь
const isAuthenticated = () => {
  const token = localStorage.getItem('authToken');
  return !!token;
};

// Получение данных пользователя
const getUser = () => {
  const userJson = localStorage.getItem('user');
  return userJson ? JSON.parse(userJson) : null;
};
```

### Выход из системы

```javascript
// JavaScript/TypeScript
const logout = () => {
  localStorage.removeItem('authToken');
  localStorage.removeItem('user');
  // Перенаправить на страницу входа
  window.location.href = '/login';
};
```

## React Hooks Example

```javascript
// useAuth.js
import { useState, useEffect } from 'react';

export const useAuth = () => {
  const [user, setUser] = useState(null);
  const [token, setToken] = useState(null);

  useEffect(() => {
    const savedToken = localStorage.getItem('authToken');
    const savedUser = localStorage.getItem('user');

    if (savedToken && savedUser) {
      setToken(savedToken);
      setUser(JSON.parse(savedUser));
    }
  }, []);

  const login = async (email, password) => {
    const response = await fetch('/api/auth/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password })
    });

    if (response.ok) {
      const data = await response.json();
      setToken(data.token);
      setUser(data.user);

      localStorage.setItem('authToken', data.token);
      localStorage.setItem('user', JSON.stringify(data.user));

      return true;
    }
    return false;
  };

  const logout = () => {
    setToken(null);
    setUser(null);
    localStorage.removeItem('authToken');
    localStorage.removeItem('user');
  };

  const apiRequest = async (url, options) => {
    const headers = {
      'Content-Type': 'application/json',
      ...(token && { 'Authorization': `Bearer ${token}` }),
      ...(options.headers || {})
    };

    return fetch(url, { ...options, headers });
  };

  return { user, token, login, logout, apiRequest, isAuthenticated: !!token };
};
```

## Board Access System

Система поддерживает множественный доступ к доскам с различными уровнями прав:

### Роли пользователей:
- **viewer** - может просматривать доску и её содержимое
- **editor** - может редактировать доску и управлять её содержимым
- **admin** - полный доступ, включая управление участниками и удаление доски

### Текущая конфигурация:
- **Все активные пользователи имеют права администратора ко всем доскам**
- Владелец доски автоматически получает роль **admin**
- Пользователь может видеть только те доски, к которым имеет доступ
- Для управления участниками требуются права **admin**

**Важное примечание:** В текущей реализации все пользователи системы автоматически получают права администратора ко всем доскам. Это означает, что любой авторизованный пользователь может:
- Просматривать все доски в системе
- Редактировать любую доску
- Управлять участниками всех досок
- Удалять любые доски

**Важно:** Все операции с досками (lists, cards) наследуют права доступа от родительской доски. Если пользователь имеет доступ к доске, он автоматически имеет соответствующие права на все списки и карточки этой доски.

### Структура ответа API для досок:

Все эндпоинты досок теперь возвращают дополнительную информацию о правах доступа:

```json
{
  "id": 1,
  "title": "Название доски",
  "description": "Описание доски",
  "userId": 1,
  "createdAt": "2025-01-01T12:00:00.000Z",
  "updatedAt": "2025-01-01T12:00:00.000Z",
  "owner": {
    "id": 1,
    "email": "owner@example.com",
    "name": "Owner Name"
  },
  "userRole": "admin",
  "accessId": 1
}
```

Где:
- `userRole` - роль текущего пользователя в доске ("admin", "editor", "viewer")
- `accessId` - ID записи доступа (может быть null для владельцев досок)

## Board Endpoints

Доски (Boards) являются основными контейнерами для организации задач. Теперь доски поддерживают множественный доступ пользователей с различными ролями.

### GET /api/boards

Получить все доски текущего пользователя.

**Headers:**
- `Authorization: Bearer <token>`

**Response (200):**
```json
[
  {
    "id": 1,
    "title": "Проект разработки",
    "description": "Основная доска для проекта",
    "userId": 1,
    "createdAt": "2025-01-01T12:00:00.000Z",
    "updatedAt": "2025-01-01T12:00:00.000Z",
    "owner": {
      "id": 1,
      "email": "user@example.com",
      "name": "User Name"
    },
    "userRole": "admin",
    "accessId": 1
  }
]
```

**Примечание:** Возвращает доски, принадлежащие текущему пользователю, а также доски без владельца (для обратной совместимости).

### GET /api/boards/:id

Получить доску по ID.

**Headers:**
- `Authorization: Bearer <token>`

**Parameters:**
- `id` (integer) - ID доски

**Response (200):**
```json
{
  "id": 1,
  "title": "Проект разработки",
  "description": "Основная доска для проекта",
  "userId": 1,
  "createdAt": "2025-01-01T12:00:00.000Z",
  "updatedAt": "2025-01-01T12:00:00.000Z",
  "owner": {
    "id": 1,
    "email": "user@example.com",
    "name": "User Name"
  },
  "userRole": "admin",
  "accessId": 1
}
```

**Error (404):**
```json
{
  "message": "Board not found or access denied"
}
```

**Примечание:** Доступ разрешен только к доскам, принадлежащим текущему пользователю или доскам без владельца.

### POST /api/boards

Создать новую доску.

**Headers:**
- `Authorization: Bearer <token>`
- `Content-Type: application/json`

**Request Body:**
```json
{
  "title": "Название доски",
  "description": "Описание доски (необязательно)"
}
```

**Response (201):**
```json
{
  "id": 1,
  "title": "Название доски",
  "description": "Описание доски",
  "userId": 1,
  "createdAt": "2025-01-01T12:00:00.000Z",
  "updatedAt": "2025-01-01T12:00:00.000Z",
  "owner": {
    "id": 1,
    "email": "user@example.com",
    "name": "User Name"
  },
  "userRole": "admin",
  "accessId": 1
}
```

**Error (400):**
```json
{
  "message": "Validation error message"
}
```

### PUT /api/boards/:id

Обновить доску по ID.

**Headers:**
- `Authorization: Bearer <token>`
- `Content-Type: application/json`

**Parameters:**
- `id` (integer) - ID доски

**Request Body:**
```json
{
  "title": "Новое название доски",
  "description": "Новое описание доски"
}
```

**Response (200):**
```json
{
  "id": 1,
  "title": "Новое название доски",
  "description": "Новое описание доски",
  "userId": 1,
  "createdAt": "2025-01-01T12:00:00.000Z",
  "updatedAt": "2025-01-01T12:05:00.000Z",
  "owner": {
    "id": 1,
    "email": "user@example.com",
    "name": "User Name"
  },
  "userRole": "admin",
  "accessId": 1
}
```

**Error (404):**
```json
{
  "message": "Board not found or access denied"
}
```

**Error (400):**
```json
{
  "message": "Validation error message"
}
```

**Примечание:** Обновлять можно только доски, принадлежащие текущему пользователю.

### DELETE /api/boards/:id

Удалить доску по ID.

**Headers:**
- `Authorization: Bearer <token>`

**Parameters:**
- `id` (integer) - ID доски

**Response (200):**
```json
{
  "message": "Board deleted"
}
```

**Error (404):**
```json
{
  "message": "Board not found or access denied"
}
```

**Error (500):**
```json
{
  "message": "Internal server error"
}
```

**Примечание:** Удалять можно только доски, где пользователь имеет роль admin.

## Board Access Management Endpoints

Эти эндпоинты позволяют управлять доступом пользователей к доскам.

### GET /api/boards/:boardId/members

Получить всех участников доски.

**Headers:**
- `Authorization: Bearer <token>`

**Parameters:**
- `boardId` (integer) - ID доски

**Response (200):**
```json
[
  {
    "id": 1,
    "role": "admin",
    "user": {
      "id": 1,
      "email": "admin@example.com",
      "name": "Admin User"
    }
  },
  {
    "id": 2,
    "role": "editor",
    "user": {
      "id": 2,
      "email": "editor@example.com",
      "name": "Editor User"
    }
  }
]
```

**Error (404):**
```json
{
  "message": "Board not found or access denied"
}
```

### POST /api/boards/:boardId/members

Добавить пользователя к доске или изменить его роль.

**Headers:**
- `Authorization: Bearer <token>`
- `Content-Type: application/json`

**Parameters:**
- `boardId` (integer) - ID доски

**Request Body:**
```json
{
  "userId": 2,
  "role": "editor"
}
```

**Response (201):**
```json
{
  "message": "User added to board",
  "access": {
    "id": 2,
    "role": "editor",
    "user": {
      "id": 2,
      "email": "editor@example.com",
      "name": "Editor User"
    }
  }
}
```

**Response (200) - если пользователь уже имеет доступ:**
```json
{
  "message": "User role updated",
  "access": {
    "id": 2,
    "role": "admin",
    "user": {
      "id": 2,
      "email": "editor@example.com",
      "name": "Editor User"
    }
  }
}
```

**Error (400):**
```json
{
  "message": "User already has access to this board"
}
```

**Error (403):**
```json
{
  "message": "Only administrators can manage board access"
}
```

**Error (404):**
```json
{
  "message": "Board not found or access denied"
}
```

### PUT /api/boards/:boardId/members/:memberId

Изменить роль участника доски.

**Headers:**
- `Authorization: Bearer <token>`
- `Content-Type: application/json`

**Parameters:**
- `boardId` (integer) - ID доски
- `memberId` (integer) - ID записи доступа

**Request Body:**
```json
{
  "role": "admin"
}
```

**Response (200):**
```json
{
  "message": "Member role updated",
  "access": {
    "id": 2,
    "role": "admin",
    "user": {
      "id": 2,
      "email": "editor@example.com",
      "name": "Editor User"
    }
  }
}
```

**Error (403):**
```json
{
  "message": "Only administrators can manage board access"
}
```

**Error (404):**
```json
{
  "message": "Access record not found"
}
```

### DELETE /api/boards/:boardId/members/:memberId

Удалить участника из доски.

**Headers:**
- `Authorization: Bearer <token>`

**Parameters:**
- `boardId` (integer) - ID доски
- `memberId` (integer) - ID записи доступа

**Response (200):**
```json
{
  "message": "Member removed from board"
}
```

**Error (400):**
```json
{
  "message": "Cannot remove the last administrator from the board"
}
```

**Error (403):**
```json
{
  "message": "Only administrators can manage board access"
}
```

**Error (404):**
```json
{
  "message": "Access record not found"
}
```

## Обновленные возможности досок

### Изменения в ответах API:

Теперь все эндпоинты досок возвращают дополнительную информацию о правах доступа:

```json
{
  "id": 1,
  "title": "Название доски",
  "description": "Описание доски",
  "userId": 1,
  "createdAt": "2025-01-01T12:00:00.000Z",
  "updatedAt": "2025-01-01T12:00:00.000Z",
  "owner": {
    "id": 1,
    "email": "owner@example.com",
    "name": "Owner Name"
  },
  "userRole": "admin",
  "accessId": 1
}
```

Где:
- `userRole` - роль текущего пользователя в доске
- `accessId` - ID записи доступа (может быть null для владельцев досок)

## Frontend Integration для досок

### Получение списка досок

```javascript
// JavaScript/TypeScript
const getBoards = async () => {
  const token = localStorage.getItem('authToken');

  try {
    const response = await fetch('/api/boards', {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    });

    if (response.ok) {
      const boards = await response.json();
      return boards;
    } else {
      throw new Error('Failed to fetch boards');
    }
  } catch (error) {
    console.error('Error fetching boards:', error);
    throw error;
  }
};
```

### Создание новой доски

```javascript
// JavaScript/TypeScript
const createBoard = async (title, description = '') => {
  const token = localStorage.getItem('authToken');

  try {
    const response = await fetch('/api/boards', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        title,
        description
      })
    });

    if (response.ok) {
      const newBoard = await response.json();
      return newBoard;
    } else {
      const error = await response.json();
      throw new Error(error.message || 'Failed to create board');
    }
  } catch (error) {
    console.error('Error creating board:', error);
    throw error;
  }
};
```

### Обновление доски

```javascript
// JavaScript/TypeScript
const updateBoard = async (boardId, updates) => {
  const token = localStorage.getItem('authToken');

  try {
    const response = await fetch(`/api/boards/${boardId}`, {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(updates)
    });

    if (response.ok) {
      const updatedBoard = await response.json();
      return updatedBoard;
    } else {
      const error = await response.json();
      throw new Error(error.message || 'Failed to update board');
    }
  } catch (error) {
    console.error('Error updating board:', error);
    throw error;
  }
};
```

### Удаление доски

```javascript
// JavaScript/TypeScript
const deleteBoard = async (boardId) => {
  const token = localStorage.getItem('authToken');

  try {
    const response = await fetch(`/api/boards/${boardId}`, {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    });

    if (response.ok) {
      return true;
    } else {
      const error = await response.json();
      throw new Error(error.message || 'Failed to delete board');
    }
  } catch (error) {
    console.error('Error deleting board:', error);
    throw error;
  }
};
```

## Comment Endpoints

Комментарии связаны с задачами (cards) и позволяют пользователям обсуждать задачи.

### GET /api/comments/:cardId

Получить все комментарии для конкретной задачи.

**Headers:**
- `Authorization: Bearer <token>`

**Response (200):**
```json
[
  {
    "id": 1,
    "text": "Это комментарий к задаче",
    "CardId": 1,
    "UserId": 1,
    "createdAt": "2025-01-01T12:00:00.000Z",
    "updatedAt": "2025-01-01T12:00:00.000Z",
    "User": {
      "id": 1,
      "name": "User Name",
      "email": "user@example.com"
    }
  }
]
```

### POST /api/comments

Создать новый комментарий для задачи.

**Headers:**
- `Authorization: Bearer <token>`
- `Content-Type: application/json`

**Request Body:**
```json
{
  "text": "Новый комментарий",
  "cardId": 1,
  "userId": 1
}
```

**Response (201):**
```json
{
  "id": 1,
  "text": "Новый комментарий",
  "CardId": 1,
  "UserId": 1,
  "createdAt": "2025-01-01T12:00:00.000Z",
  "updatedAt": "2025-01-01T12:00:00.000Z",
  "User": {
    "id": 1,
    "name": "User Name",
    "email": "user@example.com"
  }
}
```

### PUT /api/comments/:id

Обновить текст комментария.

**Headers:**
- `Authorization: Bearer <token>`
- `Content-Type: application/json`

**Request Body:**
```json
{
  "text": "Обновленный комментарий"
}
```

**Response (200):**
```json
{
  "id": 1,
  "text": "Обновленный комментарий",
  "CardId": 1,
  "UserId": 1,
  "createdAt": "2025-01-01T12:00:00.000Z",
  "updatedAt": "2025-01-01T12:05:00.000Z",
  "User": {
    "id": 1,
    "name": "User Name",
    "email": "user@example.com"
  }
}
```

### DELETE /api/comments/:id

Удалить комментарий.

**Headers:**
- `Authorization: Bearer <token>`

**Response (200):**
```json
{
  "message": "Comment deleted"
}
```

## Lists Endpoints

Списки (Lists) представляют собой колонки на доске, которые содержат карточки задач. Каждый список принадлежит определенной доске.

### GET /api/lists

Получить все списки или списки конкретной доски.

**Headers:**
- `Authorization: Bearer <token>`

**Query Parameters (optional):**
- `board` (integer) - ID доски для фильтрации списков

**Response (200):**
```json
[
  {
    "id": 1,
    "title": "To Do",
    "createdAt": "2025-01-01T12:00:00.000Z",
    "BoardId": 1,
    "Board": {
      "id": 1,
      "title": "Проект разработки",
      "description": "Основная доска для проекта"
    }
  }
]
```

**Error (500):**
```json
{
  "message": "Internal server error"
}
```

### GET /api/lists/:id

Получить список по ID.

**Headers:**
- `Authorization: Bearer <token>`

**Parameters:**
- `id` (integer) - ID списка

**Response (200):**
```json
{
  "id": 1,
  "title": "To Do",
  "createdAt": "2025-01-01T12:00:00.000Z",
  "BoardId": 1,
  "Board": {
    "id": 1,
    "title": "Проект разработки",
    "description": "Основная доска для проекта"
  }
}
```

**Error (404):**
```json
{
  "message": "List not found"
}
```

**Error (500):**
```json
{
  "message": "Internal server error"
}
```

### POST /api/lists

Создать новый список.

**Headers:**
- `Authorization: Bearer <token>`
- `Content-Type: application/json`

**Request Body:**
```json
{
  "title": "Название списка",
  "board": 1
}
```

**Response (201):**
```json
{
  "id": 1,
  "title": "Название списка",
  "createdAt": "2025-01-01T12:00:00.000Z",
  "BoardId": 1
}
```

**Error (400):**
```json
{
  "message": "Validation error message"
}
```

**Error (404):**
```json
{
  "message": "Board not found"
}
```

### PUT /api/lists/:id

Обновить список по ID.

**Headers:**
- `Authorization: Bearer <token>`
- `Content-Type: application/json`

**Parameters:**
- `id` (integer) - ID списка

**Request Body:**
```json
{
  "title": "Новое название списка",
  "board": 2
}
```

**Response (200):**
```json
{
  "id": 1,
  "title": "Новое название списка",
  "createdAt": "2025-01-01T12:00:00.000Z",
  "BoardId": 2
}
```

**Error (404):**
```json
{
  "message": "List not found"
}
```

**Error (400):**
```json
{
  "message": "Validation error message"
}
```

### DELETE /api/lists/:id

Удалить список по ID.

**Headers:**
- `Authorization: Bearer <token>`

**Parameters:**
- `id` (integer) - ID списка

**Response (200):**
```json
{
  "message": "List deleted"
}
```

**Error (404):**
```json
{
  "message": "List not found"
}
```

**Error (500):**
```json
{
  "message": "Internal server error"
}
```

## Cards Endpoints

Карточки (Cards) представляют собой задачи или элементы работы. Каждая карточка принадлежит определенному списку.

### GET /api/cards

Получить все карточки или карточки конкретного списка.

**Headers:**
- `Authorization: Bearer <token>`

**Query Parameters (optional):**
- `list` (integer) - ID списка для фильтрации карточек

**Response (200):**
```json
[
  {
    "id": 1,
    "title": "Создать API документацию",
    "description": "Подробно описать все endpoints",
    "createdAt": "2025-01-01T12:00:00.000Z",
    "ListId": 1,
    "List": {
      "id": 1,
      "title": "To Do",
      "BoardId": 1
    }
  }
]
```

**Error (500):**
```json
{
  "message": "Internal server error"
}
```

### GET /api/cards/:id

Получить карточку по ID.

**Headers:**
- `Authorization: Bearer <token>`

**Parameters:**
- `id` (integer) - ID карточки

**Response (200):**
```json
{
  "id": 1,
  "title": "Создать API документацию",
  "description": "Подробно описать все endpoints",
  "createdAt": "2025-01-01T12:00:00.000Z",
  "ListId": 1,
  "List": {
    "id": 1,
    "title": "To Do",
    "BoardId": 1
  }
}
```

**Error (404):**
```json
{
  "message": "Card not found"
}
```

**Error (500):**
```json
{
  "message": "Internal server error"
}
```

### POST /api/cards

Создать новую карточку.

**Headers:**
- `Authorization: Bearer <token>`
- `Content-Type: application/json`

**Request Body:**
```json
{
  "title": "Название задачи",
  "description": "Описание задачи (необязательно)",
  "list": 1
}
```

**Response (201):**
```json
{
  "id": 1,
  "title": "Название задачи",
  "description": "Описание задачи",
  "createdAt": "2025-01-01T12:00:00.000Z",
  "ListId": 1
}
```

**Error (400):**
```json
{
  "message": "Validation error message"
}
```

**Error (404):**
```json
{
  "message": "List not found"
}
```

### PUT /api/cards/:id

Обновить карточку по ID.

**Headers:**
- `Authorization: Bearer <token>`
- `Content-Type: application/json`

**Parameters:**
- `id` (integer) - ID карточки

**Request Body:**
```json
{
  "title": "Новое название задачи",
  "description": "Новое описание задачи",
  "list": 2
}
```

**Response (200):**
```json
{
  "id": 1,
  "title": "Новое название задачи",
  "description": "Новое описание задачи",
  "createdAt": "2025-01-01T12:00:00.000Z",
  "ListId": 2
}
```

**Error (404):**
```json
{
  "message": "Card not found"
}
```

**Error (400):**
```json
{
  "message": "Validation error message"
}
```

### DELETE /api/cards/:id

Удалить карточку по ID.

**Headers:**
- `Authorization: Bearer <token>`

**Parameters:**
- `id` (integer) - ID карточки

**Response (200):**
```json
{
  "message": "Card deleted"
}
```

**Error (404):**
```json
{
  "message": "Card not found"
}
```

**Error (500):**
```json
{
  "message": "Internal server error"
}
```

## Frontend Integration для Lists и Cards

### Работа со списками

```javascript
// JavaScript/TypeScript
const getLists = async (boardId = null) => {
  const token = localStorage.getItem('authToken');
  const params = boardId ? `?board=${boardId}` : '';

  try {
    const response = await fetch(`/api/lists${params}`, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    });

    if (response.ok) {
      const lists = await response.json();
      return lists;
    } else {
      throw new Error('Failed to fetch lists');
    }
  } catch (error) {
    console.error('Error fetching lists:', error);
    throw error;
  }
};

const createList = async (title, boardId) => {
  const token = localStorage.getItem('authToken');

  try {
    const response = await fetch('/api/lists', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        title,
        board: boardId
      })
    });

    if (response.ok) {
      const newList = await response.json();
      return newList;
    } else {
      const error = await response.json();
      throw new Error(error.message || 'Failed to create list');
    }
  } catch (error) {
    console.error('Error creating list:', error);
    throw error;
  }
};
```

### Работа с карточками

```javascript
// JavaScript/TypeScript
const getCards = async (listId = null) => {
  const token = localStorage.getItem('authToken');
  const params = listId ? `?list=${listId}` : '';

  try {
    const response = await fetch(`/api/cards${params}`, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    });

    if (response.ok) {
      const cards = await response.json();
      return cards;
    } else {
      throw new Error('Failed to fetch cards');
    }
  } catch (error) {
    console.error('Error fetching cards:', error);
    throw error;
  }
};

const createCard = async (title, description = '', listId) => {
  const token = localStorage.getItem('authToken');

  try {
    const response = await fetch('/api/cards', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        title,
        description,
        list: listId
      })
    });

    if (response.ok) {
      const newCard = await response.json();
      return newCard;
    } else {
      const error = await response.json();
      throw new Error(error.message || 'Failed to create card');
    }
  } catch (error) {
    console.error('Error creating card:', error);
    throw error;
  }
};

const moveCard = async (cardId, newListId) => {
  const token = localStorage.getItem('authToken');

  try {
    const response = await fetch(`/api/cards/${cardId}`, {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        list: newListId
      })
    });

    if (response.ok) {
      const updatedCard = await response.json();
      return updatedCard;
    } else {
      const error = await response.json();
      throw new Error(error.message || 'Failed to move card');
    }
  } catch (error) {
    console.error('Error moving card:', error);
    throw error;
  }
};
```

## Test User

Для тестирования создан test пользователь:
- Email: `test@example.com`
- Password: `testpass123`

## HTTP Status Codes

- 200: Success
- 201: Created
- 400: Bad Request
- 401: Unauthorized (invalid or missing token)
- 404: Not Found
- 500: Internal Server Error
