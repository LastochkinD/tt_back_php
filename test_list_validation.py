import requests
import json

# Base URL for the API
BASE_URL = "http://localhost:8080/api"

def get_token():
    """Get authentication token for testing"""
    register_url = f"{BASE_URL}/auth/register"
    import random
    random_num = random.randint(1000, 9999)
    email = f"valtest{random_num}@example.com"
    register_data = {
        "email": email,
        "password": "password123",
        "name": f"Validation Test User {random_num}"
    }
    response = requests.post(register_url, json=register_data, headers={"Content-Type": "application/json"})
    if response.status_code == 201:
        login_data = {"email": email, "password": "password123"}
        response = requests.post(f"{BASE_URL}/auth/login", json=login_data, headers={"Content-Type": "application/json"})
        if response.status_code == 200:
            return response.json()['token'], response.json()['user']['id']
    return None, None

def create_board(token):
    """Create a test board"""
    url = f"{BASE_URL}/boards"
    data = {"title": "Validation Test Board", "description": "Board for validation testing"}
    headers = {"Authorization": f"Bearer {token}", "Content-Type": "application/json"}
    response = requests.post(url, json=data, headers=headers)
    return response.json()['id'] if response.status_code == 201 else None

def test_validation_errors(token, board_id):
    """Test various validation scenarios"""
    print("Testing POST /api/lists validation errors...")
    url = f"{BASE_URL}/lists"
    headers = {"Authorization": f"Bearer {token}", "Content-Type": "application/json"}

    # Test 1: Missing board field
    print("1. Testing missing 'board' field:")
    data = {"title": "Test List"}
    response = requests.post(url, json=data, headers=headers)
    print(f"Status: {response.status_code}")
    print(f"Response: {response.text}")
    print()

    # Test 2: Non-existent board
    print("2. Testing non-existent board:")
    data = {"title": "Test List", "board": 99999}
    response = requests.post(url, json=data, headers=headers)
    print(f"Status: {response.status_code}")
    print(f"Response: {response.text}")
    print()

    # Test 3: Access denied (if we can find a way to test, e.g., different user)

    # Test 4: Invalid title (too long or empty, but ListEntity allows empty? Wait, rules show required)
    print("3. Testing validation error (empty title):")
    data = {"title": "", "board": board_id}
    response = requests.post(url, json=data, headers=headers)
    print(f"Status: {response.status_code}")
    print(f"Response: {response.text}")
    print()

    # Test 5: Valid creation (should work)
    print("4. Testing valid creation:")
    data = {"title": "Valid Test List", "board": board_id}
    response = requests.post(url, json=data, headers=headers)
    print(f"Status: {response.status_code}")
    if response.status_code == 201:
        result = response.json()
        print(f"Created list: {result}")
    else:
        print(f"Response: {response.text}")
    print()

if __name__ == "__main__":
    print("Testing List Validation")
    print("=" * 30)

    import time
    time.sleep(2)  # Wait for server

    token, user_id = get_token()
    if not token:
        print("Failed to get token")
        exit(1)

    board_id = create_board(token)
    if not board_id:
        print("Failed to create board")
        exit(1)

    test_validation_errors(token, board_id)
