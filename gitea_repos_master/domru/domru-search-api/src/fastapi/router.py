from fastapi import APIRouter
from src.fastapi.search import router as search_router

router: APIRouter = APIRouter()

router.include_router(search_router, prefix="/search", tags=["search"])
