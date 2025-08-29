from fastapi import Depends, Path, status, Query
from fastapi_utils.cbv import cbv
from fastapi_utils.inferring_router import InferringRouter

from app.dependencies.authorization import authorization_dependence
from app.dependencies.proxy import ProxyControllerDependence
from app.models import Session
from app.schemas.proxy import ProxyCreateSchema, ProxyDetailSchema, ProxyUpdateSchema
from app.utils.alias import FilterAlias, AliasesLoader


router = InferringRouter(tags=["proxy"])
alias_loader = AliasesLoader()


# DESCRIPTION OF QUERY PARAMETERS
PARAMETERS = {
    "worker": Query(
        default="default",
        title="Worker name",
        description='Name of data collection source, for example "avito", "callapp"',
        max_length=100,
    ),
    "proxy_id": Path(
        ge=1, title="Proxy ID", description="The ID of the proxy to get", example=1
    ),
}


@cbv(router)
class ProxyRouter:
    controller: ProxyControllerDependence = Depends()
    session_data: Session = Depends(authorization_dependence)
    alias = FilterAlias(alias_loader.get("proxy"))

    @router.get("/proxy")
    async def get_proxy_list(
        self,
        worker: str | None = PARAMETERS["worker"],
        limit: int = Query(
            default=1,
            ge=1,
            title="Max count of proxies by request",
            description="Number of proxies in the response",
        ),
        offset: int = Query(
            default=0,
            ge=0,
            title="Proxies offset",
            description="Offset from the first element in the resulting selection",
        ),
        sort: str
        | None = Query(
            default=None,
            title="Sorting list",
            description="List of field name for sorting",
            example='["-id", "+proxy_usage.count_use", "proxy_usage.last_success$NF"]',
        ),
        _filter: str
        | None = Query(
            default=None,
            alias="filter",
            title="filter",
            description="Filtering Object",
            example='{"tags": {"name$AND": ["mobile", "resident"]}, "country": "ru"}',
        ),
        f: str
        | None = Query(
            default=None,
            title="filter_alias",
            description=(
                "Alias for filter and sorter. The sort and filter are ignored if it is "
                "passed"
            ),
            example="mobile",
        ),
    ) -> list[ProxyDetailSchema]:
        sort, _filter, limit, offset = self.alias.get_filter(
            f, sort, _filter, limit, offset
        )
        return await self.controller.object.get_proxy_list(
            worker, limit, offset, sort, _filter
        )

    @router.post("/proxy")
    async def create_proxy(self, proxy: ProxyCreateSchema) -> ProxyDetailSchema:
        return await self.controller.object.create(proxy)

    @router.delete("/proxy/{proxy_id}")
    async def delete_proxy(self, proxy_id: int = PARAMETERS["proxy_id"]):
        return await self.controller.object.delete(proxy_id)

    @router.get("/proxy/{proxy_id}")
    async def get_proxy(
        self,
        proxy_id: int = PARAMETERS["proxy_id"],
    ) -> ProxyDetailSchema:
        return await self.controller.object.get_proxy(proxy_id)

    @router.patch("/proxy/{proxy_id}")
    async def update_proxy(
        self,
        proxy: ProxyUpdateSchema,
        proxy_id: int = PARAMETERS["proxy_id"],
    ) -> ProxyDetailSchema:
        return await self.controller.object.update(proxy_id, proxy)

    @router.post(
        "/proxy/{proxy_id}/report",
        status_code=status.HTTP_200_OK,
        responses={404: {"description": "Proxy or worker does not exist"}},
    )
    async def report(
        self,
        proxy_id: int = PARAMETERS["proxy_id"],
        worker: str | None = PARAMETERS["worker"],
    ):
        return await self.controller.object.report(proxy_id, worker)
