import asyncio
from datetime import datetime

from fastapi import HTTPException, status, Response

from app.controllers.base import BaseController
from app.managers.proxy import ProxyManager
from app.managers.proxy_tag import ProxyTagManager
from app.managers.proxy_usage import ProxyUsageManager
from app.managers.tag import TagManager
from app.managers.worker import WorkerManager
from app.models import Proxy, ProxyUsage, Tag
from app.schemas.proxy import ProxyCreateSchema, ProxyDetailSchema, ProxyUpdateSchema
from app.utils.errors import NotFoundError
from app.utils.list_utils import ListUtils
from .filter_sort_mixin import FilterSortMixin


DEFAULT_FILTER = {"active": True}
DEFAULT_SORT = ["proxy_usage.count_use", "proxy_usage.last_success$NF"]


class ProxyController(FilterSortMixin, BaseController):
    @property
    def manager(self) -> ProxyManager:
        return super().manager

    async def __get_object(self, proxy_id: int) -> Proxy:
        try:
            return await self.manager.get(proxy_id)
        except NotFoundError:
            raise HTTPException(status_code=status.HTTP_404_NOT_FOUND)

    async def get_proxy(self, proxy_id: int):
        return ProxyDetailSchema.from_orm(await self.__get_object(proxy_id))

    async def get_proxy_list(
        self,
        worker_name: str | None,
        limit: int,
        offset: int,
        sort: str | None,
        _filter: str | None,
    ) -> list[Proxy]:
        if not worker_name:
            worker_name = "default"

        # managers with a single session instance
        proxy_usage_manager = ProxyUsageManager(self.manager.session)
        worker_manager = WorkerManager(self.manager.session)

        # Check the worker exists in the database, create if it doesn't
        worker = await worker_manager.get_by_conditions(
            [worker_manager.model.name == worker_name]
        )
        if not worker:
            worker, proxy_ids = await asyncio.gather(
                worker_manager.create(worker_name), self.manager.get_all_ids()
            )
            await proxy_usage_manager.bulk_insert_by_ids(
                "proxy_id", proxy_ids, worker_id=worker.id
            )
            await self.manager.session.commit()

        # apply filter and sort
        overlay_filter = {"proxy_usage": {"worker_id": worker.id, "active": True}}
        results = await self.apply_filter_sort(
            self.manager,
            limit,
            offset,
            sort,
            DEFAULT_SORT,
            _filter,
            DEFAULT_FILTER,
            overlay_filter,
        )

        if len(results) == 1:
            await proxy_usage_manager.update(
                where_conditions=[
                    ProxyUsage.proxy_id == results[0].id,
                    ProxyUsage.worker_id == worker.id,
                ],
                count_use=ProxyUsage.count_use + 1,
                last_use=datetime.utcnow(),
            )
            await proxy_usage_manager.session.commit()
        return results

    async def report(self, proxy_id: int, worker: str):
        worker_manager = WorkerManager(self.manager.session)
        _proxy_id, worker_id = await asyncio.gather(
            self.manager.exists_or_none(Proxy.id == proxy_id),
            worker_manager.exists_or_none(WorkerManager.model.name == worker),
        )
        if not _proxy_id or not worker_id:
            msg = f"Proxy with id={proxy_id} does not exist!" if not _proxy_id else ""
            msg = (
                msg + f" Worker with name={worker} does not exist!"
                if not worker_id
                else msg
            )
            raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail=msg)

        pu_manager = ProxyUsageManager(self.manager.session)
        await pu_manager.update(
            where_conditions=[
                ProxyUsage.proxy_id == proxy_id,
                ProxyUsage.worker_id == worker_id,
            ],
            count_success=ProxyUsage.count_success + 1,
            last_success=datetime.utcnow(),
        )
        await pu_manager.session.commit()
        return Response(status_code=status.HTTP_200_OK)

    async def __validate_tags(self, tags: list[str]) -> list[Tag]:
        tag_manager = TagManager(self.manager.session)
        existed_tags = await tag_manager.get_list(_filter={"name": tags})
        if len(tags) != len(existed_tags):
            unknown_tags = ListUtils.difference(tags, [t.name for t in existed_tags])
            raise HTTPException(
                status_code=status.HTTP_404_NOT_FOUND,
                detail=f"Unknown tags: {','.join(unknown_tags)}!",
            )
        return existed_tags

    async def __restore_deleted_proxy_by(self, proxy: ProxyCreateSchema) -> Proxy | None:
        proxies = await self.manager.get_list(
            None,
            None,
            None,
            {
                "protocol": proxy.protocol.value,
                "host": proxy.host,
                "port": proxy.port,
                "login": proxy.login,
                "password": proxy.password,
            },
            execution_options={"include_deleted": True},
            filter_object=self.manager.filter_object_tags_join,
        )
        if len(proxies):
            instance = proxies[0]
            if instance.deleted is None:
                raise HTTPException(
                    status_code=status.HTTP_409_CONFLICT, detail="Proxy already exists"
                )
            else:
                await self.manager.update(
                    where_conditions=[Proxy.id == instance.id],
                    execution_options={"include_deleted": True},
                    deleted=None,
                )
                await self.manager.session.commit()
                return instance
        return None

    async def create(self, proxy: ProxyCreateSchema):
        _proxy = proxy.dict()
        _proxy["created"] = datetime.now()
        tags = _proxy.pop("tags")

        # check the proxy exists in the database
        instance = await self.__restore_deleted_proxy_by(proxy)
        if instance:
            return ProxyDetailSchema.from_orm(instance)

        tasks = []
        # create proxy and proxy_tag
        proxy_object = Proxy(**_proxy)
        self.manager.create(proxy_object)
        if tags:
            tags = await self.__validate_tags(tags)
            proxy_tag_manager = ProxyTagManager(self.manager.session)
            tasks.append(
                (
                    proxy_tag_manager.bulk_insert_by_ids,
                    {
                        "ids_field_name": "tag_id",
                        "ids": [tag.id for tag in tags],
                        "proxy_id": proxy_object.id,
                    },
                )
            )

        # create proxy_usage for all workers
        worker_manager = WorkerManager(self.manager.session)
        worker_ids = [w.id for w in (await worker_manager.all())]
        if worker_ids:
            pu_manager = ProxyUsageManager(self.manager.session)
            tasks.append(
                (
                    pu_manager.bulk_insert_by_ids,
                    {
                        "ids_field_name": "worker_id",
                        "ids": worker_ids,
                        "proxy_id": proxy_object.id,
                    },
                )
            )

        if tasks:
            await asyncio.gather(*[f(**kwargs) for f, kwargs in tasks])

        await self.manager.session.commit()
        return await self.get_proxy(proxy_object.id)

    async def delete(self, instance_id: int) -> Response:
        result = await self.manager.update(
            where_conditions=[self.manager.model.id == instance_id],
            deleted=datetime.now(),
        )
        if result.rowcount == 0:
            return Response(status_code=status.HTTP_404_NOT_FOUND)

        # update statistic
        pu_manager = ProxyUsageManager(self.manager.session)
        await pu_manager.update(
            where_conditions=[ProxyUsage.proxy_id == instance_id],
            count_use=0,
            last_use=None,
            count_success=0,
            last_success=None,
        )

        await self.manager.session.commit()
        return Response(status_code=status.HTTP_204_NO_CONTENT)

    async def __update_tags(
        self, proxy_id: int, new_tags: list[Tag], old_tags: list[Tag]
    ):
        new_tags_id = {t.id for t in new_tags}
        old_tags_id = {t.id for t in old_tags}
        deleted_tags = ListUtils.difference(old_tags_id, new_tags_id)
        new_tags = ListUtils.difference(new_tags_id, old_tags_id)

        pt_manager = ProxyTagManager(self.manager.session)
        tasks = []
        if deleted_tags:
            tasks.append(
                (
                    pt_manager.delete,
                    {
                        "id_or_conditions": [
                            pt_manager.model.proxy_id == proxy_id,
                            pt_manager.model.tag_id.in_(deleted_tags),
                        ]
                    },
                )
            )
        if new_tags:
            tasks.append(
                (
                    pt_manager.bulk_insert_by_ids,
                    {
                        "ids_field_name": "tag_id",
                        "ids": new_tags,
                        "proxy_id": proxy_id,
                    },
                )
            )
        if tasks:
            await asyncio.gather(*[f(**kwargs) for f, kwargs in tasks])

    async def update(self, proxy_id: int, proxy: ProxyUpdateSchema):
        _proxy = proxy.dict(exclude_unset=True)

        tags = _proxy.get("tags", None)
        if tags is not None:
            _proxy.pop("tags")

        instance = await self.__get_object(proxy_id)
        if _proxy:
            await self.manager.update(
                where_conditions=[Proxy.id == instance.id], **_proxy
            )

        # handle tags
        if tags is not None:
            tags = await self.__validate_tags(tags)
            await self.__update_tags(instance.id, tags, instance.tags)

        await self.manager.session.commit()
        await self.manager.session.refresh(instance)
        return ProxyDetailSchema.from_orm(instance)
