from fastapi.responses import JSONResponse
from fastapi_utils.cbv import cbv
from fastapi_utils.inferring_router import InferringRouter


router = InferringRouter()


@cbv(router)
class MainRouter:
    @router.get("/")
    def home(self):
        return JSONResponse(content={"service": "proxy-app"})
