import requests
import json
import time

# Base URL for the API
BASE_URL = "http://localhost:8080/api"

def _print_request_details(method, url, headers=None, data=None):
    print(f"--- {method} {url} ---")
    if headers:
        print("Headers:")
        for key, value in headers.items():
            if 'Authorization' in key:
                print(f"  {key}: Bearer ***{value[-20:] if len(str(value)) > 20 else value}***")
            else:
                print(f"  {key}: {value[:50]}..." if len(str(value)) > 50 else f"  {key}: {value}")
    if data:
        print("Body:")
        print(json.dumps(data, indent=2))
    print()

def _print_response_details(response):
    print("Response Status:", response.status_code)
    print("Response Headers:")
    for key, value in response.headers.items():
        print(f"  {key}: {value}")
    try:
        response_json = response.json()
        print("Response Body:")
        print(json.dumps(response_json, indent=2))
    except json.JSONDecodeError:
        print("Response Body (raw):")
        print(response.text)
    print("-" * 50)
    print()

def authenticate_user(email="test@example.com", password="password123"):
    """Get authentication token"""
    url = f"{BASE_URL}/auth/login"
    data = {"email": email, "password": password}
    headers = {"Content-Type": "application/json"}

    response = requests.post(url, json=data, headers=headers)

    if response.status_code == 200:
        result = response.json()
        print(f"Authenticated user {email} with ID {result['user']['id']}")
        return result['token'], result['user']['id']
    else:
        print(f"Failed to authenticate {email}")
        return None, None

def register_and_authenticate():
    """Register a new user and authenticate"""
    import random
    random_num = random.randint(1000, 9999)
    email = f"boardtest{random_num}@example.com"

    # Register
    register_url = f"{BASE_URL}/auth/register"
    register_data = {
        "email": email,
        "password": "password123",
        "name": f"Board Test User {random_num}"
    }

    register_response = requests.post(register_url, json=register_data, headers={"Content-Type": "application/json"})

    if register_response.status_code == 201:
        print(f"Registered new user: {email}")
        return authenticate_user(email, "password123")
    else:
        print("Failed to register user")
        return None, None

def test_get_boards(token):
    """Test GET /api/boards"""
    url = f"{BASE_URL}/boards"
    headers = {
        "Authorization": f"Bearer {token}",
        "Content-Type": "application/json"
    }

    _print_request_details("GET", url, headers)

    response = requests.get(url, headers=headers)

    _print_response_details(response)

    return response.json() if response.status_code == 200 else None

def test_create_board(token, title="Test Board", description="Test board description"):
    """Test POST /api/boards"""
    url = f"{BASE_URL}/boards"
    data = {"title": title, "description": description}
    headers = {
        "Authorization": f"Bearer {token}",
        "Content-Type": "application/json"
    }

    _print_request_details("POST", url, headers, data)

    response = requests.post(url, json=data, headers=headers)

    _print_response_details(response)

    return response.json() if response.status_code == 201 else None

def test_get_board(token, board_id):
    """Test GET /api/boards/:id"""
    url = f"{BASE_URL}/boards/{board_id}"
    headers = {
        "Authorization": f"Bearer {token}",
        "Content-Type": "application/json"
    }

    _print_request_details("GET", url, headers)

    response = requests.get(url, headers=headers)

    _print_response_details(response)

    return response.json() if response.status_code == 200 else None

def test_update_board(token, board_id, title="Updated Board", description="Updated description"):
    """Test PUT /api/boards/:id"""
    url = f"{BASE_URL}/boards/{board_id}"
    data = {"title": title, "description": description}
    headers = {
        "Authorization": f"Bearer {token}",
        "Content-Type": "application/json"
    }

    _print_request_details("PUT", url, headers, data)

    response = requests.put(url, json=data, headers=headers)

    _print_response_details(response)

    return response.json() if response.status_code == 200 else None

def test_get_board_members(token, board_id):
    """Test GET /api/boards/:boardId/members"""
    url = f"{BASE_URL}/boards/{board_id}/members"
    headers = {
        "Authorization": f"Bearer {token}",
        "Content-Type": "application/json"
    }

    _print_request_details("GET", url, headers)

    response = requests.get(url, headers=headers)

    _print_response_details(response)

    return response.json() if response.status_code == 200 else None

def test_delete_board(token, board_id):
    """Test DELETE /api/boards/:id"""
    url = f"{BASE_URL}/boards/{board_id}"
    headers = {
        "Authorization": f"Bearer {token}",
        "Content-Type": "application/json"
    }

    _print_request_details("DELETE", url, headers)

    response = requests.delete(url, headers=headers)

    _print_response_details(response)

    return response.status_code == 200

if __name__ == "__main__":
    print("Testing Board Operations")
    print("=" * 50)

    # Wait a moment for server to be ready
    time.sleep(2)

    # Authenticate user
    print("1. Authenticating user...")
    token, user_id = register_and_authenticate()

    if not token:
        print("Failed to authenticate user. Exiting.")
        exit(1)

    # Test 1: Get all boards (should be empty for new user)
    print("2. Testing GET /api/boards (empty list)")
    boards = test_get_boards(token)

    # Test 2: Create a new board
    print("3. Testing POST /api/boards (create board)")
    created_board = test_create_board(token, "My Test Board", "This is a test board")

    if created_board:
        board_id = created_board['id']

        # Test 3: Get the specific board
        print("4. Testing GET /api/boards/:id")
        board = test_get_board(token, board_id)

        # Test 4: Update the board
        print("5. Testing PUT /api/boards/:id")
        updated_board = test_update_board(token, board_id, "Updated Test Board", "Updated description")

        # Test 5: Get board members
        print("6. Testing GET /api/boards/:boardId/members")
        members = test_get_board_members(token, board_id)

        # Test 6: Get all boards again (should include the created board)
        print("7. Testing GET /api/boards (with created board)")
        boards_after = test_get_boards(token)

        # Test 7: Delete the board
        print("8. Testing DELETE /api/boards/:id")
        deleted = test_delete_board(token, board_id)

        # Test 8: Try to get the deleted board (should fail)
        print("9. Testing GET /api/boards/:id (deleted board)")
        deleted_board = test_get_board(token, board_id)

    print("Board testing complete.")
