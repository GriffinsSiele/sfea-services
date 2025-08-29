from typing import List, Optional
from src.domain.services.telegram_search_service import TelegramSearchService
from src.domain.entities.telegram_user import TelegramUser
from src.domain.entities.telegram_session import TelegramSession
from src.domain.value_objects.phone_number import PhoneNumber
from src.application.dto.search_request import SearchRequest
from src.application.dto.search_response import SearchResponse
from src.application.dto.error_response import ErrorResponse


class SearchUserByPhoneUseCase:
    """Use case for searching users by phone number"""
    
    def __init__(self, search_service: TelegramSearchService):
        self.search_service = search_service
    
    async def execute(
        self, 
        request: SearchRequest
    ) -> SearchResponse:
        """Execute the search by phone use case"""
        try:
            # Validate request
            if not request.phone:
                return SearchResponse(
                    success=False,
                    errors=["Phone number is required"],
                    data=None
                )
            
            # Create phone number value object
            try:
                phone = PhoneNumber(request.phone)
            except ValueError as e:
                return SearchResponse(
                    success=False,
                    errors=[f"Invalid phone number format: {str(e)}"],
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
            users = await self.search_service.search_by_phone(phone, session)
            
            # Update session after successful search
            await self.search_service.update_session_after_search(session)
            
            # Convert users to response format
            user_data = [user.to_dict() for user in users]
            
            return SearchResponse(
                success=True,
                data=user_data,
                errors=[],
                metadata={
                    "search_type": "phone",
                    "query": str(phone),
                    "results_count": len(users),
                    "session_id": session.id
                }
            )
            
        except Exception as e:
            return SearchResponse(
                success=False,
                errors=[f"Search failed: {str(e)}"],
                data=None
            )
