from typing import Optional
from src.domain.services.telegram_search_service import TelegramSearchService
from src.domain.entities.telegram_user import TelegramUser
from src.domain.entities.telegram_session import TelegramSession
from src.domain.value_objects.username import Username
from src.application.dto.search_request import SearchRequest
from src.application.dto.search_response import SearchResponse


class SearchUserByUsernameUseCase:
    """Use case for searching users by username"""
    
    def __init__(self, search_service: TelegramSearchService):
        self.search_service = search_service
    
    async def execute(
        self, 
        request: SearchRequest
    ) -> SearchResponse:
        """Execute the search by username use case"""
        try:
            # Validate request
            if not request.username:
                return SearchResponse(
                    success=False,
                    errors=["Username is required"],
                    data=None
                )
            
            # Create username value object
            try:
                username = Username(request.username)
            except ValueError as e:
                return SearchResponse(
                    success=False,
                    errors=[f"Invalid username format: {str(e)}"],
                    data=None
                )
            
            # Get available session
            session = await self.search_service.get_available_session()
            if not session:
                return SearchResponse(
                    success=False,
                    errors=["No available sessions for search"],
                    data=None
                )
            
            # Validate session for search
            if not await self.search_service.validate_session_for_search(session):
                return SearchResponse(
                    success=False,
                    errors=["Session cannot perform search at this time"],
                    data=None
                )
            
            # Perform search
            user = await self.search_service.search_by_username(username, session)
            
            if not user:
                return SearchResponse(
                    success=False,
                    errors=["User not found"],
                    data=None
                )
            
            # Update session after successful search
            await self.search_service.update_session_after_search(session)
            
            return SearchResponse(
                success=True,
                data=[user.to_dict()],
                errors=[],
                metadata={
                    "search_type": "username",
                    "query": str(username),
                    "results_count": 1,
                    "session_id": session.id
                }
            )
            
        except Exception as e:
            return SearchResponse(
                success=False,
                errors=[f"Search failed: {str(e)}"],
                data=None
            )
