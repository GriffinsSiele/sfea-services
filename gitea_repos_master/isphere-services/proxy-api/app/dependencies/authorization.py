from fastapi import Depends, HTTPException, status
from fastapi.security import HTTPBasic, HTTPBasicCredentials
from sqlalchemy.ext.asyncio import AsyncSession

from app.managers.session import SessionManager
from app.models import Session
from app.utils.database import DatabaseManager


security = HTTPBasic()


async def authorization_dependence(
    session: AsyncSession = Depends(DatabaseManager.get_async_session),
    credentials: HTTPBasicCredentials = Depends(security),
):
    session_manager = SessionManager(session)
    existed_session = await session_manager.get_by_conditions(
        [Session.login == credentials.username, Session.password == credentials.password]
    )
    if not existed_session:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            headers={"WWW-Authenticate": "Basic"},
        )
    return existed_session
