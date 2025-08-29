#!/usr/bin/env python3
"""
Basic test script to verify the refactored Telegram service structure
Run this first to ensure everything is working before full testing
"""

import asyncio
import sys
import os

# Add src to path
sys.path.insert(0, os.path.join(os.path.dirname(__file__), 'src'))

# Global imports for use across tests
from domain.value_objects.phone_number import PhoneNumber
from domain.value_objects.username import Username
from domain.value_objects.api_credentials import APICredentials
from domain.entities.telegram_user import TelegramUser
from domain.entities.telegram_session import TelegramSession
from application.dto.search_request import SearchRequest
from application.dto.search_response import SearchResponse
from application.dto.error_response import ErrorResponse
from interface.middleware.rate_limiting import RateLimiter

async def test_basic_imports():
    """Test that all modules can be imported without errors"""
    print("🔍 Testing basic imports...")
    
    try:
        # Test domain layer
        from domain.entities.telegram_user import TelegramUser
        from domain.entities.telegram_session import TelegramSession
        from domain.value_objects.phone_number import PhoneNumber
        from domain.value_objects.username import Username
        from domain.value_objects.api_credentials import APICredentials
        print("✅ Domain layer imports successful")
        
        # Test application layer
        from application.dto.search_request import SearchRequest
        from application.dto.search_response import SearchResponse
        from application.dto.error_response import ErrorResponse
        print("✅ Application layer imports successful")
        
        # Test infrastructure layer
        from infrastructure.telegram.telethon_client import TelethonClientAdapter
        print("✅ Infrastructure layer imports successful")
        
        # Test interface layer
        from interface.controllers.telegram_controller import TelegramController
        from interface.middleware.validation import validate_search_request
        from interface.middleware.rate_limiting import RateLimiter
        print("✅ Interface layer imports successful")
        
        return True
        
    except ImportError as e:
        print(f"❌ Import error: {e}")
        return False
    except Exception as e:
        print(f"❌ Unexpected error: {e}")
        return False

async def test_value_objects():
    """Test value object validation"""
    print("\n🔍 Testing value objects...")
    
    try:
        # Test PhoneNumber
        phone = PhoneNumber("79319999999")
        print(f"✅ PhoneNumber created: {phone}")
        print(f"   Digits only: {phone.digits_only}")
        print(f"   Formatted: {phone.formatted}")
        
        # Test Username
        username = Username("testuser")
        print(f"✅ Username created: {username}")
        print(f"   Normalized: {username.normalized}")
        print(f"   With @: {username.with_at}")
        
        # Test APICredentials
        credentials = APICredentials(12345, "a" * 32)
        print(f"✅ APICredentials created: {credentials}")
        
        return True
        
    except Exception as e:
        print(f"❌ Value object test failed: {e}")
        return False

async def test_entities():
    """Test domain entities"""
    print("\n🔍 Testing domain entities...")
    
    try:
        # Test TelegramUser
        user = TelegramUser(
            id=123456789,
            username="testuser",
            first_name="Test",
            last_name="User",
            phone="79319999999",
            is_bot=False,
            is_verified=False,
            is_restricted=False,
            is_scam=False,
            is_fake=False,
            access_hash=12345678901234567890,
            photo=None,
            status="online",
            created_at=None,
            updated_at=None
        )
        print(f"✅ TelegramUser created: {user.get_display_name()}")
        print(f"   Is active: {user.is_active()}")
        print(f"   To dict: {user.to_dict()}")
        
        # Test TelegramSession
        session = TelegramSession(
            id="session_123",
            auth_key="auth_key_123",
            api_id=12345,
            api_hash="a" * 32,
            password=None,
            proxy_id="proxy_1",
            last_message=None,
            friends=[],
            is_active=True,
            search_count=0,
            max_searches_per_day=29,
            created_at=None,
            updated_at=None
        )
        print(f"✅ TelegramSession created: {session.id}")
        print(f"   Can perform search: {session.can_perform_search()}")
        print(f"   To dict: {session.to_dict()}")
        
        return True
        
    except Exception as e:
        print(f"❌ Entity test failed: {e}")
        return False

async def test_dtos():
    """Test DTOs"""
    print("\n🔍 Testing DTOs...")
    
    try:
        # Test SearchRequest
        request = SearchRequest(phone="79319999999")
        print(f"✅ SearchRequest created: {request}")
        
        # Test SearchResponse
        response = SearchResponse(
            success=True,
            data=[{"id": 123, "username": "test"}],
            errors=[],
            metadata={"search_type": "phone"}
        )
        print(f"✅ SearchResponse created: {response}")
        
        # Test ErrorResponse
        error = ErrorResponse(
            success=False,
            error="Test error",
            error_code="TEST_ERROR",
            details={"test": "data"},
            timestamp="2024-01-01T00:00:00Z"
        )
        print(f"✅ ErrorResponse created: {error}")
        
        return True
        
    except Exception as e:
        print(f"❌ DTO test failed: {e}")
        return False

async def test_middleware():
    """Test middleware components"""
    print("\n🔍 Testing middleware...")
    
    try:
        # Test RateLimiter
        rate_limiter = RateLimiter(max_requests=10, window_seconds=60)
        can_request = await rate_limiter.check_rate_limit()
        print(f"✅ RateLimiter created: {can_request}")
        
        # Test validation
        from interface.middleware.validation import validate_search_request
        request = SearchRequest(phone="79319999999")
        validation_result = validate_search_request(request)
        print(f"✅ Validation test: {validation_result}")
        
        return True
        
    except Exception as e:
        print(f"❌ Middleware test failed: {e}")
        return False

async def main():
    """Run all tests"""
    print("Starting basic tests for refactored Telegram service...\n")
    
    tests = [
        test_basic_imports,
        test_value_objects,
        test_entities,
        test_dtos,
        test_middleware
    ]
    
    results = []
    for test in tests:
        try:
            result = await test()
            results.append(result)
        except Exception as e:
            print(f"❌ Test {test.__name__} failed with exception: {e}")
            results.append(False)
    
    print("\n" + "="*50)
    print("📊 TEST RESULTS SUMMARY")
    print("="*50)
    
    passed = sum(results)
    total = len(results)
    
    for i, (test, result) in enumerate(zip(tests, results)):
        status = "✅ PASS" if result else "❌ FAIL"
        print(f"{i+1:2d}. {test.__name__:20s} - {status}")
    
    print(f"\nOverall: {passed}/{total} tests passed")
    
    if passed == total:
        print("🎉 All tests passed! The refactored structure is working correctly.")
        print("\nNext steps:")
        print("1. Run the FastAPI application: python main.py")
        print("2. Test the API endpoints")
        print("3. Run integration tests")
    else:
        print("⚠️  Some tests failed. Please check the errors above.")
        return 1
    
    return 0

if __name__ == "__main__":
    exit_code = asyncio.run(main())
    sys.exit(exit_code)
