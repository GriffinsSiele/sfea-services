from fastapi import APIRouter
from src.fastapi.schemas import StatusResponse

default_router = APIRouter(tags=["holehe"])


@default_router.get("/status", response_model=StatusResponse)
async def status() -> dict:
    return {"status": "ok"}
