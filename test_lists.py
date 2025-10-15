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
    email = f"listtest{random_num}@example.com"

    # Register
    register_url = f"{BASE_URL}/auth/register"
    register_data = {
        "email": email,
        "password": "password123",
        "name": f"List Test User {random_num}"
    }

    register_response = requests.post(register_url, json=register_data, headers={"Content-Type": "application/json"})

    if register_response.status_code == 201:
        print(f"Registered new user: {email}")
        return authenticate_user(email, "password123")
    else:
        print("Failed to register user")
        return None, None

def create_test_board(token, title="Test Board for Lists", description="Board for testing lists"):
    """Create a test board"""
    url = f"{BASE_URL}/boards"
    data = {"title": title, "description": description}
    headers = {
        "Authorization": f"Bearer {token}",
        "Content-Type": "application/json"
    }

    response = requests.post(url, json=data, headers=headers)

    return response.json() if response.status_code == 201 else None

def test_get_lists(token, board=None):
    """Test GET /api/lists"""
    url = f"{BASE_URL}/lists"
    params = {}
    if board is not None:
        params['board_id'] = board
    headers = {
        "Authorization": f"Bearer {token}",
        "Content-Type": "application/json"
    }

    _print_request_details("GET", url, headers)

    response = requests.get(url, headers=headers, params=params)

    _print_response_details(response)

    return response.json() if response.status_code == 200 else None

def test_get_list_by_id(token, list_id):
    """Test GET /api/lists/:id"""
    url = f"{BASE_URL}/lists/{list_id}"
    headers = {
        "Authorization": f"Bearer {token}",
        "Content-Type": "application/json"
    }

    _print_request_details("GET", url, headers)

    response = requests.get(url, headers=headers)

    _print_response_details(response)

    return response.json() if response.status_code == 200 else None

def test_create_list(token, title, board):
    """Test POST /api/lists"""
    url = f"{BASE_URL}/lists"
    data = {
        "title": title,
        "board_id": board
    }
    headers = {
        "Authorization": f"Bearer {token}",
        "Content-Type": "application/json"
    }

    _print_request_details("POST", url, headers, data)

    response = requests.post(url, json=data, headers=headers)

    _print_response_details(response)

    return response.json() if response.status_code == 201 else None

def test_update_list(token, list_id, title=None, board=None):
    """Test PUT /api/lists/:id"""
    url = f"{BASE_URL}/lists/{list_id}"
    data = {}
    if title is not None:
        data['title'] = title
    if board is not None:
        data['board_id'] = board
    headers = {
        "Authorization": f"Bearer {token}",
        "Content-Type": "application/json"
    }

    _print_request_details("PUT", url, headers, data)

    response = requests.put(url, json=data, headers=headers)

    _print_response_details(response)

    return response.json() if response.status_code == 200 else None

def test_delete_list(token, list_id):
    """Test DELETE /api/lists/:id"""
    url = f"{BASE_URL}/lists/{list_id}"
    headers = {
        "Authorization": f"Bearer {token}",
        "Content-Type": "application/json"
    }

    _print_request_details("DELETE", url, headers)

    response = requests.delete(url, headers=headers)

    _print_response_details(response)

    return response.status_code == 200

if __name__ == "__main__":
    print("Testing List Operations")
    print("=" * 50)

    # Wait a moment for server to be ready
    time.sleep(2)

    # Authenticate user
    print("1. Authenticating user...")
    token, user_id = register_and_authenticate()

    if not token:
        print("Failed to authenticate user. Exiting.")
        exit(1)

    # Create a board for testing
    print("2. Creating test board...")
    board = create_test_board(token, "List Test Board", "Board for list testing")

    if not board:
        print("Failed to create board. Exiting.")
        exit(1)

    board_id = board['id']

    # Test 2: Get lists for a specific board
    print("3. Testing GET /api/lists?board={board_id}")
    lists_board = test_get_lists(token, board_id)

    # Test 3: Create a list
    print("4. Testing POST /api/lists")
    created_list = test_create_list(token, "Test List", board_id)

    list_id = None
    if created_list:
        list_id = created_list['id']

        # Test 4: Get lists again (should include the created list)
        print("5. Testing GET /api/lists?board={board_id} (with created list)")
        lists_after_create = test_get_lists(token, board_id)

        # Test 5: Get specific list by ID
        print("6. Testing GET /api/lists/:id")
        list_by_id = test_get_list_by_id(token, list_id)

        # Test 6: Update the list
        print("7. Testing PUT /api/lists/:id (update title)")
        updated_list = test_update_list(token, list_id, title="Updated Test List")

        # Test 7: Update the list board (if possible, but since only one board, expect error or same)
        # Note: Assuming user has access to another board or same
        print("8. Testing PUT /api/lists/:id (update board to same)")
        updated_list_board = test_update_list(token, list_id, board=board_id)

        # Test 8: Delete the list
        print("9. Testing DELETE /api/lists/:id")
        deleted = test_delete_list(token, list_id)

        # Test 9: Get lists after deletion
        print("10. Testing GET /api/lists?board={board_id} (after deletion)")
        lists_after_delete = test_get_lists(token, board_id)

        # Test 10: Try to get deleted list by ID (should 404)
        print("11. Testing GET /api/lists/:id (deleted, expect 404)")
        list_deleted = test_get_list_by_id(token, list_id)

    # Note: Board cleanup is omitted for simplicity

    print("List testing complete.")
