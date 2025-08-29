from typing import List, Optional
from dataclasses import asdict

from src.domain.entities.telegram_session import TelegramSession
from src.domain.entities.telegram_user import TelegramUser
from src.domain.repositories.telegram_session_repository import TelegramSessionRepository
from src.domain.repositories.telegram_user_repository import TelegramUserRepository
from src.domain.services.telegram_search_service import TelegramSearchService
from src.domain.value_objects.phone_number import PhoneNumber
from src.domain.value_objects.username import Username
from src.domain.value_objects.api_credentials import APICredentials
from src.infrastructure.telegram.telethon_client import TelethonClientAdapter


class TelethonTelegramSearchService(TelegramSearchService):
    def __init__(
        self,
        user_repository: TelegramUserRepository,
        session_repository: TelegramSessionRepository,
        credentials: APICredentials,
        auth_key: str,
        proxy: Optional[dict] = None,
        password: Optional[str] = None,
    ):
        super().__init__(user_repository, session_repository)
        self.client = TelethonClientAdapter(credentials, auth_key, password=password, proxy=proxy)

    async def get_available_session(self) -> Optional[TelegramSession]:
        # Use repository or build from config; simplified here
        return TelegramSession(
            id="live-session",
            auth_key="masked",
            api_id=self.client.credentials.api_id,
            api_hash=self.client.credentials.api_hash,
        )

    async def validate_session_for_search(self, session: TelegramSession) -> bool:
        return await self.client.connect()

    async def update_session_after_search(self, session: TelegramSession) -> None:
        await self.client.disconnect()

    async def search_by_phone(self, phone: PhoneNumber, session: TelegramSession) -> List[TelegramUser]:
        return await self.client.search_by_phone(phone)

    async def search_by_username(self, username: Username, session: TelegramSession) -> Optional[TelegramUser]:
        return await self.client.search_by_username(username)



