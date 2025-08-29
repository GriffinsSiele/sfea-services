from fastapi import APIRouter

from .endpoints import (
    decode_router,
    external_providers_router,
    image_task_router,
    source_router,
    token_tasks_router,
    website_router,
)

router: APIRouter = APIRouter()

router.include_router(decode_router, prefix="/decode", tags=["image_decoder"])

router.include_router(image_task_router, prefix="/tasks", tags=["image_tasks"])

router.include_router(token_tasks_router, prefix="/tokens", tags=["token_tasks"])

router.include_router(source_router, prefix="/sources", tags=["sources"])

router.include_router(website_router, prefix="/websites", tags=["websites"])

router.include_router(external_providers_router, prefix="/providers", tags=["providers"])
