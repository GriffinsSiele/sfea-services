from typing import Optional, List
from datetime import datetime

from src.domain.services.telegram_search_service import TelegramSearchService
from src.domain.entities.telegram_user import TelegramUser
from src.domain.entities.telegram_session import TelegramSession
from src.domain.value_objects.phone_number import PhoneNumber
from src.domain.value_objects.username import Username


class InMemoryTelegramSearchService(TelegramSearchService):
    """
    Minimal in-memory implementation for live/demo usage.
    Returns deterministic sample data and tracks a simple search counter.
    """

    def __init__(self):
        # No repositories required for demo
        self._session = TelegramSession(
            id="session_demo",
            auth_key="demo_auth_key",
            api_id=12345,
            api_hash="a" * 32,
            is_active=True,
            search_count=0,
            max_searches_per_day=29,
        )

    async def search_by_phone(
        self,
        phone: PhoneNumber,
        session: TelegramSession
    ) -> List[TelegramUser]:
        user = TelegramUser(
            id=123456789,
            username="testuser",
            first_name="Test",
            last_name="User",
            phone=str(phone),
            is_bot=False,
            is_verified=False,
            is_restricted=False,
            is_scam=False,
            is_fake=False,
            access_hash=12345678901234567890,
            photo=None,
            status="online",
            created_at=None,
            updated_at=None,
        )
        return [user]

    async def search_by_username(
        self,
        username: Username,
        session: TelegramSession
    ) -> Optional[TelegramUser]:
        return TelegramUser(
            id=987654321,
            username=str(username),
            first_name="Test",
            last_name="User",
            phone=None,
            is_bot=False,
            is_verified=False,
            is_restricted=False,
            is_scam=False,
            is_fake=False,
            access_hash=98765432109876543210,
            photo=None,
            status="online",
            created_at=None,
            updated_at=None,
        )

    async def validate_session_for_search(self, session: TelegramSession) -> bool:
        return session.is_active and session.search_count < session.max_searches_per_day

    async def update_session_after_search(self, session: TelegramSession) -> None:
        session.increment_search_count()

    async def get_available_session(self) -> Optional[TelegramSession]:
        return self._session


