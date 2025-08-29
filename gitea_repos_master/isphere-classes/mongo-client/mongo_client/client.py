import asyncio
from datetime import datetime, timedelta
from typing import Dict, List, Optional

from pymongo.results import DeleteResult, InsertOneResult, UpdateResult

from mongo_client.connection import MongoConnectionInterface
from mongo_client.fields import Period


class MongoSessions(MongoConnectionInterface):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)

    def _filter_one(self, session: Dict) -> Dict:
        return {"filter": {"_id": session["_id"]}}

    def _filter_exclude_lock(self, offset=0) -> Dict:
        return {
            "$or": [
                {self.next_use: {"$lt": datetime.now() + timedelta(seconds=offset)}},
                {self.next_use: None},
            ]
        }

    def _filter_include_lock(self, offset=0):
        return {self.next_use: {"$gt": datetime.now() + timedelta(seconds=offset)}}

    def __next_use_delay(self, delay: Optional[int] = None) -> Optional[datetime]:
        delay = delay if delay is not None else self.next_use_delay
        return datetime.now() + timedelta(seconds=delay) if (delay is not None) else None

    async def session_lock(
        self, session: Dict, period: Optional[Period] = None
    ) -> UpdateResult:
        """Временная блокировка сессии

        Используется для защиты сессии от частого использования, в случае временного бана
        со стороны источника или по другим причинам. После данного времени сессия станет
        активно использоваться обработчиками

        :param session: dict имеющий _id сессии
        :param period: опциональное время блокировки, например {'hours': 10}. По умолчанию - атрибут lock_time
        """
        next_use = datetime.now() + (timedelta(**period) if period else self.lock_time)
        update = {"$set": {self.next_use: next_use}}
        return await self.update_one(update=update, **self._filter_one(session))

    async def session_block(
        self, session: Dict, period: Optional[Period] = None
    ) -> UpdateResult:
        """Временная блокировка сессии продолжительного характера

        Используется для защиты сессии от частого использования, в случае временного бана
        со стороны источника или по другим причинам. После данного времени сессия станет
        активно использоваться обработчиками

        :param session: dict имеющий _id сессии
        :param period: опциональное время блокировки, например {'hours': 10}. По умолчанию - атрибут block_time
        """
        next_use = datetime.now() + (timedelta(**period) if period else self.block_time)
        update = {"$set": {self.next_use: next_use}}
        return await self.update_one(update=update, **self._filter_one(session))

    async def count_active(self) -> int:
        """Количество активных сессий

        Данный метод считает количество записей в БД, соответствующих критерию
        default_filter (можно переопределить) и _filter_exclude_lock. По умолчанию,
        активная учетка - не имеет false в поле active, не имеет блокирующих интервалов в
        полях next_use (время больше текущего)

        :return: количество сессий
        """
        filter_ = {**self.default_filter, **self._filter_exclude_lock()}
        return await self.count_documents(filter=filter_)

    async def get_sessions(self) -> List[Dict]:
        """Получение всех сессий в БД

        Учитываются только сессий по критерию default_filter (можно переопределить), по
        умолчанию - не имеет false в поле active. Дополнительно выбираются только поля
        сессии согласно проекции (например, только поля session, _id)

        :return: список словарей (сессий)
        """
        filter_ = self.default_filter
        projection = self.projection if self.projection else None
        return await self.find(filter=filter_, projection=projection)

    async def get_session(
        self, count_use: int = 1, next_use_delay: Optional[int] = None
    ) -> Dict:
        """Получение сессии для обработчика на задачу

        Обработчику для задач необходимы сессии. Учитывая, что обработчиков много и работают
        параллельно, то необходим механизм выбора сессий, чтобы одна и та же сессия не
        попала в одинаковый момент времени к разным обработчикам.

        Для этого существует поле last_use (время последнего использования), которое
        автоматически обновляется в момент выборки. Выборка сессий происходит в порядке
        от самого старого использования к последнему. Также обновляется поле next_use, временно
        блокируется сессий (чаще всего не более чем на 3 сек), чтобы избежать выборки N
        раз за несколько секунд одинаковой сессии


        :param count_use: число, указать сколько раз будет использована сессия
        в рамках данной выборки (для внутренней статистики)
        :param next_use_delay: время блокировки, в сек, поле next_use
        :return: сессия
        """
        update = {
            "$set": {
                self.last_use: datetime.now(),
                self.next_use: self.__next_use_delay(next_use_delay),
            },
            "$inc": {"count_use": count_use},
        }
        filter_ = {**self.default_filter, **self._filter_exclude_lock()}
        projection = self.projection if self.projection else None
        sort = self.default_sort

        return await self.find_one_and_update(
            filter=filter_, sort=sort, update=update, projection=projection
        )

    async def session_success(
        self,
        session: Dict,
        count_success: int = 1,
        next_use_delay: Optional[int] = None,
    ) -> UpdateResult:
        """Обновление внутренней статистики успешности

        :param session: dict имеющий _id сессии
        :param count_success: число, указать на сколько раз увеличить счетчик успешности использования
        :param next_use_delay: время блокировки, в сек, поле next_use
        """
        update = {
            "$inc": {"count_success": count_success},
            "$set": {self.next_use: self.__next_use_delay(next_use_delay)},
        }
        return await self.update_one(update=update, **self._filter_one(session))

    async def session_inactive(self, session: Dict) -> UpdateResult:
        """Постоянная блокировка сессии.

        После данной действия сессия может быть активирована только активатором или руками в БД!

        :param session: dict имеющий _id сессии
        """
        update = {"$set": {self.active: False}}
        return await self.update_one(update=update, **self._filter_one(session))

    async def add(self, data: Dict) -> InsertOneResult:
        """Добавление сессии в БД

        :param data: произвольный dict
        """
        payload = {
            self.active: True,
            "count_use": 0,
            "count_success": 0,
            "created": datetime.now(),
            self.last_use: None,
            self.next_use: None,
            **data,
        }
        return await self.insert_one(payload)

    async def session_update(
        self, session: Dict, payload: Dict, unset_payload: Optional[Dict] = None
    ) -> UpdateResult:
        """Обновление полей сессии в БД

        Для обновления внутренних session, нужно передать ``{'session.phone': '+79...'}``, а не ``{'session': {'phone': '+79...'}}``

        :param session: dict имеющий _id сессии
        """
        update = {
            "$set": payload,
            **({"$unset": unset_payload} if unset_payload else {}),
        }
        return await self.update_one(update=update, **self._filter_one(session))

    async def session_delete(self, session: Dict) -> DeleteResult:
        """Удаление сессии в БД

        :param session: dict имеющий _id сессии
        """
        return await self.delete_one(**self._filter_one(session))

    async def aggregate_statistics(self):
        """Получение внутренней статистики для мониторинга"""
        [count_active, count_blocked, count_locked, count_total] = await asyncio.gather(
            self.count_active(),
            self.count_documents(filter={self.active: False}),
            self.count_documents(
                filter={**self.default_filter, **self._filter_include_lock()}
            ),
            self.count_documents(filter={}),
        )

        return {
            "count_active": count_active,
            "count_blocked": count_blocked,
            "count_locked": count_locked,
            "count_total": count_total,
        }
