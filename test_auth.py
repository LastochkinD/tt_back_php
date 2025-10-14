import requests
import json
import time

# Base URL for the API
BASE_URL = "http://localhost:8080/api/auth"

def _print_request_details(method, url, headers=None, data=None):
    print(f"--- {method} {url} ---")
    if headers:
        print("Headers:")
        for key, value in headers.items():
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

def test_register():
    """Test user registration"""
    import random
    url = f"{BASE_URL}/register"
    random_num = random.randint(1000, 9999)
    data = {
        "email": f"test{random_num}@example.com",
        "password": "password123",
        "name": "Test User"
    }
    headers = {
        "Content-Type": "application/json"
    }

    _print_request_details("POST", url, headers, data)

    response = requests.post(url, json=data, headers=headers)

    _print_response_details(response)

    return response.json() if response.status_code == 201 else None

def test_login(email):
    """Test user login"""
    url = f"{BASE_URL}/login"
    data = {
        "email": email,
        "password": "password123"
    }
    headers = {
        "Content-Type": "application/json"
    }

    _print_request_details("POST", url, headers, data)

    response = requests.post(url, json=data, headers=headers)

    _print_response_details(response)

    return response.json() if response.status_code == 200 else None

if __name__ == "__main__":
    print("Testing Authentication Endpoints")
    print("=" * 50)

    # Wait a moment for server to be ready
    time.sleep(2)

    # Test 1: Register a new user
    print("1. Testing User Registration")
    register_response = test_register()

    # Test 2: Login with the registered user
    print("2. Testing User Login")
    if register_response and 'user' in register_response:
        user_email = register_response['user']['email']
        login_response = test_login(user_email)
    else:
        print("Registration failed, skipping login test.")
        login_response = None

    # Store token if login successful
    if login_response and 'token' in login_response:
        token = login_response['token']
        print(f"Login successful. Token: {token[:50]}...")
    else:
        print("Login failed or no token received.")
