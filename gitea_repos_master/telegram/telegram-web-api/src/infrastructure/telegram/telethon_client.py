import asyncio
import logging
from typing import Optional, List, Dict, Any
from telethon import TelegramClient
from telethon.sessions import StringSession
from telethon.tl.functions.users import GetFullUserRequest
from telethon.tl.functions.contacts import ImportContactsRequest, DeleteContactsRequest
from telethon.tl.types import InputPhoneContact, InputUser
from telethon.errors import (
    PhoneNumberInvalidError, 
    UsernameInvalidError, 
    UsernameNotOccupiedError,
    FloodWaitError,
    SessionPasswordNeededError,
    PhoneCodeInvalidError
)

from src.domain.entities.telegram_user import TelegramUser
from src.domain.value_objects.phone_number import PhoneNumber
from src.domain.value_objects.username import Username
from src.domain.value_objects.api_credentials import APICredentials


class TelethonClientAdapter:
    """Adapter for Telethon Telegram client"""
    
    def __init__(
        self,
        credentials: APICredentials,
        auth_key: str,
        password: Optional[str] = None,
        proxy: Optional[Dict[str, Any]] = None
    ):
        self.credentials = credentials
        self.auth_key = auth_key
        self.password = password
        self.proxy = proxy
        self.client: Optional[TelegramClient] = None
        self.logger = logging.getLogger(__name__)
    
    async def connect(self) -> bool:
        """Connect to Telegram"""
        try:
            self.client = TelegramClient(
                StringSession(self.auth_key),
                api_id=self.credentials.api_id,
                api_hash=self.credentials.api_hash,
                proxy=self.proxy,
                connection_retries=3,
                request_retries=2,
                retry_delay=2,
                sequential_updates=True,
                receive_updates=False,
                timeout=30,
            )
            
            await self.client.connect()
            
            if not await self.client.is_user_authorized():
                self.logger.error("User not authorized")
                return False
            
            self.logger.info("Successfully connected to Telegram")
            return True
            
        except Exception as e:
            self.logger.error(f"Failed to connect to Telegram: {e}")
            return False
    
    async def disconnect(self):
        """Disconnect from Telegram"""
        if self.client and self.client.is_connected():
            await self.client.disconnect()
            self.logger.info("Disconnected from Telegram")
    
    async def search_by_phone(self, phone: PhoneNumber) -> List[TelegramUser]:
        """Search for users by phone number"""
        if not self.client or not self.client.is_connected():
            raise RuntimeError("Client not connected")
        
        try:
            # Import contact to search
            contact = InputPhoneContact(
                client_id=0,
                phone=phone.digits_only,
                first_name="Search",
                last_name="User"
            )
            
            result = await self.client(ImportContactsRequest([contact]))
            
            if not result.users:
                return []
            
            # Convert to domain entities
            users = []
            for user in result.users:
                telegram_user = TelegramUser(
                    id=user.id,
                    username=getattr(user, 'username', None),
                    first_name=getattr(user, 'first_name', None),
                    last_name=getattr(user, 'last_name', None),
                    phone=phone.value,
                    is_bot=getattr(user, 'bot', False),
                    is_verified=getattr(user, 'verified', False),
                    is_restricted=getattr(user, 'restricted', False),
                    is_scam=getattr(user, 'scam', False),
                    is_fake=getattr(user, 'fake', False),
                    access_hash=user.access_hash,
                    photo=None,  # Will be populated by extend_profile
                    status=None,  # Will be populated by extend_profile
                    created_at=None,
                    updated_at=None
                )
                users.append(telegram_user)
            
            # Clean up imported contacts
            if result.users:
                user_ids = [InputUser(user.id, user.access_hash) for user in result.users]
                await self.client(DeleteContactsRequest(user_ids))
            
            return users
            
        except PhoneNumberInvalidError:
            self.logger.warning(f"Invalid phone number: {phone}")
            return []
        except FloodWaitError as e:
            self.logger.warning(f"Rate limited: {e}")
            raise
        except Exception as e:
            self.logger.error(f"Error searching by phone: {e}")
            raise
    
    async def search_by_username(self, username: Username) -> Optional[TelegramUser]:
        """Search for user by username"""
        if not self.client or not self.client.is_connected():
            raise RuntimeError("Client not connected")
        
        try:
            # Get user by username
            user = await self.client.get_entity(username.with_at)
            
            if not user:
                return None
            
            # Get full user info
            full_user = await self.client(GetFullUserRequest(user))
            
            telegram_user = TelegramUser(
                id=user.id,
                username=getattr(user, 'username', None),
                first_name=getattr(user, 'first_name', None),
                last_name=getattr(user, 'last_name', None),
                phone=None,  # Not available for username search
                is_bot=getattr(user, 'bot', False),
                is_verified=getattr(user, 'verified', False),
                is_restricted=getattr(user, 'restricted', False),
                is_scam=getattr(user, 'scam', False),
                is_fake=getattr(user, 'fake', False),
                access_hash=user.access_hash,
                photo=None,  # Will be populated by extend_profile
                status=None,  # Will be populated by extend_profile
                created_at=None,
                updated_at=None
            )
            
            return telegram_user
            
        except UsernameNotOccupiedError:
            self.logger.warning(f"Username not found: {username}")
            return None
        except UsernameInvalidError:
            self.logger.warning(f"Invalid username: {username}")
            return None
        except FloodWaitError as e:
            self.logger.warning(f"Rate limited: {e}")
            raise
        except Exception as e:
            self.logger.error(f"Error searching by username: {e}")
            raise
    
    async def extend_user_profile(self, user: TelegramUser) -> TelegramUser:
        """Extend user profile with additional information"""
        if not self.client or not self.client.is_connected():
            raise RuntimeError("Client not connected")
        
        try:
            # Get full user info
            input_user = InputUser(user.id, user.access_hash)
            full_user = await self.client(GetFullUserRequest(input_user))
            
            # Update user with additional info
            if full_user.full_user.profile_photo:
                user.photo = str(full_user.full_user.profile_photo.id)
            
            if full_user.full_user.status:
                user.status = str(full_user.full_user.status)
            
            return user
            
        except Exception as e:
            self.logger.error(f"Error extending user profile: {e}")
            return user
    
    async def is_connected(self) -> bool:
        """Check if client is connected"""
        return self.client is not None and self.client.is_connected()
