from fastapi import FastAPI
from fastapi.utils import is_body_allowed_for_status_code
from starlette.exceptions import HTTPException as StarletteHTTPException
from starlette.responses import JSONResponse, Response

from app.routers import proxy, tag, main, worker
from app.utils.initializer import Initializer


app = FastAPI()
app.include_router(main.router)
app.include_router(proxy.router)
app.include_router(tag.router)
app.include_router(worker.router)


@app.on_event("startup")
async def startup():
    await Initializer.initialize()


@app.exception_handler(StarletteHTTPException)
async def http_exception_handler(_, exc):
    headers = getattr(exc, "headers", None)
    if not is_body_allowed_for_status_code(exc.status_code):
        return Response(status_code=exc.status_code, headers=headers)
    return JSONResponse(
        {"error": exc.detail}, status_code=exc.status_code, headers=headers
    )
