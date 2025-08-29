"""
Domain layer tests for Telegram service
Tests entities, value objects, and domain services
"""

import pytest
from datetime import datetime, timedelta, timezone
from src.domain.entities.telegram_user import TelegramUser
from src.domain.entities.telegram_session import TelegramSession
from src.domain.value_objects.phone_number import PhoneNumber
from src.domain.value_objects.username import Username
from src.domain.value_objects.api_credentials import APICredentials


class TestPhoneNumber:
    """Test PhoneNumber value object"""
    
    def test_valid_phone_numbers(self):
        """Test valid phone number formats"""
        valid_numbers = [
            "79319999999",
            "1234567890",
            "5551234567",
            "+1234567890"
        ]
        
        for number in valid_numbers:
            phone = PhoneNumber(number)
            assert phone.value == number
            assert phone.digits_only.isdigit()
    
    def test_invalid_phone_numbers(self):
        """Test invalid phone number formats"""
        invalid_numbers = [
            "123",  # Too short
            "12345678901234567890",  # Too long
            "abc123def",  # Contains letters
            ""  # Empty
        ]
        
        for number in invalid_numbers:
            with pytest.raises(ValueError):
                PhoneNumber(number)
    
    def test_phone_formatting(self):
        """Test phone number formatting"""
        phone = PhoneNumber("79319999999")
        assert phone.formatted == "+7 (931) 999-99-99"
        
        phone = PhoneNumber("15551234567")
        assert phone.formatted == "+1 (555) 123-4567"
    
    def test_phone_equality(self):
        """Test phone number equality"""
        phone1 = PhoneNumber("79319999999")
        phone2 = PhoneNumber("79319999999")
        phone3 = PhoneNumber("79319999998")
        
        assert phone1 == phone2
        assert phone1 != phone3
        assert hash(phone1) == hash(phone2)


class TestUsername:
    """Test Username value object"""
    
    def test_valid_usernames(self):
        """Test valid username formats"""
        valid_usernames = [
            "testuser",
            "user123",
            "test_user",
            "a" * 32  # Max length
        ]
        
        for username in valid_usernames:
            user = Username(username)
            assert user.value == username
    
    def test_invalid_usernames(self):
        """Test invalid username formats"""
        invalid_usernames = [
            "test",  # Too short
            "123user",  # Starts with number
            "test__user",  # Consecutive underscores
            "testuser_",  # Ends with underscore
            "a" * 33,  # Too long
            "",  # Empty
        ]
        
        for username in invalid_usernames:
            with pytest.raises(ValueError):
                Username(username)
    
    def test_username_normalization(self):
        """Test username normalization"""
        username = Username("TestUser")
        assert username.normalized == "testuser"
        assert username.with_at == "@TestUser"
    
    def test_username_equality(self):
        """Test username equality"""
        user1 = Username("testuser")
        user2 = Username("testuser")
        user3 = Username("TestUser")
        
        assert user1 == user2
        assert user1 == user3  # Case insensitive
        assert hash(user1) == hash(user2)


class TestAPICredentials:
    """Test APICredentials value object"""
    
    def test_valid_credentials(self):
        """Test valid API credentials"""
        api_id = 12345
        api_hash = "a" * 32
        
        credentials = APICredentials(api_id, api_hash)
        assert credentials.api_id == api_id
        assert credentials.api_hash == api_hash
    
    def test_invalid_api_id(self):
        """Test invalid API ID"""
        with pytest.raises(ValueError):
            APICredentials(0, "a" * 32)
        
        with pytest.raises(ValueError):
            APICredentials(-1, "a" * 32)
    
    def test_invalid_api_hash(self):
        """Test invalid API hash"""
        with pytest.raises(ValueError):
            APICredentials(12345, "short")
        
        with pytest.raises(ValueError):
            APICredentials(12345, "invalid_hash_with_letters")
    
    def test_credentials_equality(self):
        """Test credentials equality"""
        cred1 = APICredentials(12345, "a" * 32)
        cred2 = APICredentials(12345, "a" * 32)
        cred3 = APICredentials(12346, "a" * 32)
        
        assert cred1 == cred2
        assert cred1 != cred3
        assert hash(cred1) == hash(cred2)


class TestTelegramUser:
    """Test TelegramUser entity"""
    
    def test_user_creation(self):
        """Test user creation with all fields"""
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
        
        assert user.id == 123456789
        assert user.username == "testuser"
        assert user.first_name == "Test"
        assert user.last_name == "User"
        assert user.phone == "79319999999"
        assert user.is_bot is False
        assert user.is_verified is False
        assert user.is_restricted is False
        assert user.is_scam is False
        assert user.is_fake is False
        assert user.access_hash == 12345678901234567890
        assert user.photo is None
        assert user.status == "online"
        assert user.created_at is not None
        assert user.updated_at is not None
    
    def test_user_display_name(self):
        """Test user display name logic"""
        # Full name
        user = TelegramUser(
            id=1, first_name="John", last_name="Doe",
            is_bot=False, is_verified=False, is_restricted=False,
            is_scam=False, is_fake=False, created_at=None, updated_at=None
        )
        assert user.get_display_name() == "John Doe"
        
        # First name only
        user.first_name = "John"
        user.last_name = None
        assert user.get_display_name() == "John"
        
        # Username only
        user.first_name = None
        user.username = "johndoe"
        assert user.get_display_name() == "@johndoe"
        
        # ID only
        user.username = None
        assert user.get_display_name() == "User 1"
    
    def test_user_active_status(self):
        """Test user active status logic"""
        user = TelegramUser(
            id=1, is_bot=False, is_verified=False, is_restricted=False,
            is_scam=False, is_fake=False, created_at=None, updated_at=None
        )
        assert user.is_active() is True
        
        user.is_restricted = True
        assert user.is_active() is False
        
        user.is_restricted = False
        user.is_scam = True
        assert user.is_active() is False
        
        user.is_scam = False
        user.is_fake = True
        assert user.is_active() is False
    
    def test_user_to_dict(self):
        """Test user serialization"""
        user = TelegramUser(
            id=123, username="test", first_name="Test", last_name="User",
            is_bot=False, is_verified=False, is_restricted=False,
            is_scam=False, is_fake=False, created_at=None, updated_at=None
        )
        
        user_dict = user.to_dict()
        assert user_dict["id"] == 123
        assert user_dict["username"] == "test"
        assert user_dict["first_name"] == "Test"
        assert user_dict["last_name"] == "User"
        assert "created_at" in user_dict
        assert "updated_at" in user_dict


class TestTelegramSession:
    """Test TelegramSession entity"""
    
    def test_session_creation(self):
        """Test session creation"""
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
        
        assert session.id == "session_123"
        assert session.auth_key == "auth_key_123"
        assert session.api_id == 12345
        assert session.api_hash == "a" * 32
        assert session.proxy_id == "proxy_1"
        assert session.is_active is True
        assert session.search_count == 0
        assert session.max_searches_per_day == 29
        assert session.friends == []
        assert session.created_at is not None
        assert session.updated_at is not None
    
    def test_session_search_validation(self):
        """Test session search validation logic"""
        session = TelegramSession(
            id="test", auth_key="key", api_id=123, api_hash="a" * 32,
            is_active=True, search_count=0, max_searches_per_day=29,
            created_at=None, updated_at=None
        )
        
        # Should be able to search initially
        assert session.can_perform_search() is True
        
        # After max searches, should not be able to search
        session.search_count = 29
        assert session.can_perform_search() is False
        
        # Reset search count for new day
        session.last_message = datetime.now(timezone.utc) - timedelta(days=2)
        assert session.can_perform_search() is True
        assert session.search_count == 0
    
    def test_session_search_count_increment(self):
        """Test search count increment"""
        session = TelegramSession(
            id="test", auth_key="key", api_id=123, api_hash="a" * 32,
            is_active=True, search_count=0, max_searches_per_day=29,
            created_at=None, updated_at=None
        )
        
        initial_count = session.search_count
        initial_time = session.last_message
        
        session.increment_search_count()
        
        assert session.search_count == initial_count + 1
        assert session.last_message != initial_time
        assert session.updated_at != initial_time
    
    def test_session_friend_management(self):
        """Test friend management"""
        session = TelegramSession(
            id="test", auth_key="key", api_id=123, api_hash="a" * 32,
            is_active=True, search_count=0, max_searches_per_day=29,
            created_at=None, updated_at=None
        )
        
        # Add friend
        session.add_friend("friend_1")
        assert "friend_1" in session.friends
        
        # Add duplicate friend (should not add again)
        initial_count = len(session.friends)
        session.add_friend("friend_1")
        assert len(session.friends) == initial_count
        
        # Remove friend
        session.remove_friend("friend_1")
        assert "friend_1" not in session.friends
    
    def test_session_next_use_time(self):
        """Test next use time calculation"""
        session = TelegramSession(
            id="test", auth_key="key", api_id=123, api_hash="a" * 32,
            is_active=True, search_count=0, max_searches_per_day=29,
            created_at=None, updated_at=None
        )
        
        # Should be able to use now
        next_use = session.get_next_use_time()
        assert next_use <= datetime.now(timezone.utc)
        
        # After max searches, should wait until tomorrow
        session.search_count = 29
        next_use = session.get_next_use_time()
        tomorrow = datetime.now(timezone.utc).replace(hour=0, minute=0, second=0, microsecond=0) + timedelta(days=1)
        assert next_use >= tomorrow
    
    def test_session_to_dict(self):
        """Test session serialization"""
        session = TelegramSession(
            id="test", auth_key="key", api_id=123, api_hash="a" * 32,
            is_active=True, search_count=0, max_searches_per_day=29,
            created_at=None, updated_at=None
        )
        
        session_dict = session.to_dict()
        assert session_dict["id"] == "test"
        assert session_dict["auth_key"] == "key"
        assert session_dict["api_id"] == 123
        assert session_dict["is_active"] is True
        assert session_dict["search_count"] == 0
        assert session_dict["max_searches_per_day"] == 29
        assert "created_at" in session_dict
        assert "updated_at" in session_dict


if __name__ == "__main__":
    pytest.main([__file__])
