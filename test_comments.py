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
    email = f"commenttest{random_num}@example.com"

    # Register
    register_url = f"{BASE_URL}/auth/register"
    register_data = {
        "email": email,
        "password": "password123",
        "name": f"Comment Test User {random_num}"
    }

    register_response = requests.post(register_url, json=register_data, headers={"Content-Type": "application/json"})

    if register_response.status_code == 201:
        print(f"Registered new user: {email}")
        return authenticate_user(email, "password123")
    else:
        print("Failed to register user")
        return None, None

def create_test_board(token, title="Test Board for Comments", description="Board for testing comments"):
    """Create a test board"""
    url = f"{BASE_URL}/boards"
    data = {"title": title, "description": description}
    headers = {
        "Authorization": f"Bearer {token}",
        "Content-Type": "application/json"
    }

    response = requests.post(url, json=data, headers=headers)

    return response.json() if response.status_code == 201 else None

def create_test_list(token, board_id, title="Test List for Comments"):
    """Create a test list"""
    url = f"{BASE_URL}/lists"
    data = {"name": title, "board_id": board_id}
    headers = {
        "Authorization": f"Bearer {token}",
        "Content-Type": "application/json"
    }

    response = requests.post(url, json=data, headers=headers)

    return response.json() if response.status_code == 201 else None

def create_test_card(token, list_id, title="Test Card for Comments"):
    """Create a test card"""
    url = f"{BASE_URL}/cards"
    data = {"title": title, "description": "Card for testing comments", "list_id": list_id}
    headers = {
        "Authorization": f"Bearer {token}",
        "Content-Type": "application/json"
    }

    response = requests.post(url, json=data, headers=headers)

    return response.json() if response.status_code == 201 else None

def test_get_comments(token, card_id):
    """Test GET /api/comments/:cardId"""
    url = f"{BASE_URL}/comments/{card_id}"
    headers = {
        "Authorization": f"Bearer {token}",
        "Content-Type": "application/json"
    }

    _print_request_details("GET", url, headers)

    response = requests.get(url, headers=headers)

    _print_response_details(response)

    return response.json() if response.status_code == 200 else None

def test_create_comment(token, card_id, text="Test comment text"):
    """Test POST /api/comments"""
    url = f"{BASE_URL}/comments"
    data = {
        "text": text,
        "card_id": card_id
    }
    headers = {
        "Authorization": f"Bearer {token}",
        "Content-Type": "application/json"
    }

    _print_request_details("POST", url, headers, data)

    response = requests.post(url, json=data, headers=headers)

    _print_response_details(response)

    return response.json() if response.status_code == 201 else None

def test_update_comment(token, comment_id, new_text="Updated comment text"):
    """Test PUT /api/comments/:id"""
    url = f"{BASE_URL}/comments/{comment_id}"
    data = {
        "text": new_text
    }
    headers = {
        "Authorization": f"Bearer {token}",
        "Content-Type": "application/json"
    }

    _print_request_details("PUT", url, headers, data)

    response = requests.put(url, json=data, headers=headers)

    _print_response_details(response)

    return response.json() if response.status_code == 200 else None

def test_delete_comment(token, comment_id):
    """Test DELETE /api/comments/:id"""
    url = f"{BASE_URL}/comments/{comment_id}"
    headers = {
        "Authorization": f"Bearer {token}",
        "Content-Type": "application/json"
    }

    _print_request_details("DELETE", url, headers)

    response = requests.delete(url, headers=headers)

    _print_response_details(response)

    return response.status_code == 200

if __name__ == "__main__":
    print("Testing Comment Operations")
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
    board = create_test_board(token, "Comment Test Board", "Board for comment testing")

    if not board:
        print("Failed to create board. Exiting.")
        exit(1)

    board_id = board['id']

    # Create a list
    print("3. Creating test list...")
    list_entity = create_test_list(token, board_id, "Comment Test List")

    if not list_entity:
        print("Failed to create list. Exiting.")
        exit(1)

    list_id = list_entity['id']

    # Create a card
    print("4. Creating test card...")
    card = create_test_card(token, list_id, "Comment Test Card")

    if not card:
        print("Failed to create card. Exiting.")
        exit(1)

    card_id = card['id']

    # Test 1: Get comments for new card (should be empty)
    print("5. Testing GET /api/comments/:cardId (empty list)")
    comments = test_get_comments(token, card_id)

    # Test 2: Create a comment
    print("6. Testing POST /api/comments (create comment)")
    created_comment = test_create_comment(token, card_id, "This is a test comment")

    if created_comment:
        comment_id = created_comment['id']

        # Test 3: Get comments again (should include the created comment)
        print("7. Testing GET /api/comments/:cardId (with comment)")
        comments_after = test_get_comments(token, card_id)

        # Test 4: Update the comment
        print("8. Testing PUT /api/comments/:id")
        updated_comment = test_update_comment(token, comment_id, "Updated test comment")

        # Test 5: Create another comment for more testing
        print("9. Testing POST /api/comments (create second comment)")
        second_comment = test_create_comment(token, card_id, "Second test comment")

        # Test 6: Get comments again (should include both)
        print("10. Testing GET /api/comments/:cardId (with multiple comments)")
        comments_multiple = test_get_comments(token, card_id)

        # Test 7: Delete the first comment
        print("11. Testing DELETE /api/comments/:id")
        deleted = test_delete_comment(token, comment_id)

        # Test 8: Get comments after deletion (should include only second comment)
        print("12. Testing GET /api/comments/:cardId (after deletion)")
        comments_after_delete = test_get_comments(token, card_id)

        # Clean up: delete second comment
        if second_comment:
            test_delete_comment(token, second_comment['id'])

    # Note: Board, list, and card cleanup is omitted for simplicity as they're needed for other tests

    print("Comment testing complete.")
