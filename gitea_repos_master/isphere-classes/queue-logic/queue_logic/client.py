import json

from queue_logic.keydb_fields import KeyDBFields
from queue_logic.operations import KeyDBOperations


class KeyDBQueue(KeyDBOperations, KeyDBFields):
    """Класс клиент для работы с KeyDB (Redis).

    Содержит методы управления очередями ответов и задач.
    """

    def __init__(self, keydb_url: str, service: str | None):
        """Конструктор класса
        :param keydb_url: ссылка подключения к redis, обязательный. Например, ``redis://login:password@keydb.cluster.local``
        :param service: имя очереди. Например, ``callapp``
        """
        super().__init__(keydb_url, service)

    async def check_queue(self):
        """Проверка на существования задачи в очередях задач

        Проверка в очередях вида {service}_queue и {service}_reestr.

        1. Если очередь задач не пуста, то берем задачу из этой очереди, иначе -> return None
        2. Проверяем очередь ответов на наличие уже обработанного кеша задачи.
        3. Если задача не была обработана, возвращаем эту задачу как ответ функции
        4. Если задача уже была обработана, то обновляем ей TTL и return None

        За счет TTL очередь ответов не копится и обновляется примерно раз в сутки
        (зависит от параметров).

        :return: None | str - задача или ничего
        """
        payload = await self.__get_task_from_queue()
        if not payload:
            return None

        is_existed = await self.check_exists_with_update_ttl(payload)
        return None if is_existed else payload

    async def check_exists(self, payload: str) -> str:
        """Проверка на существование обработанной задачи в очереди ответа
        :param payload: задача
        :return: ответ hget
        """
        return await self.hget(self.queue_response, payload)

    async def check_exists_with_update_ttl(self, payload: str) -> bool:
        """Функция проверки на существование обработанной задачи в
        очереди ответа с обновлением TTL

        :param payload: задача
        :return: boolean - существует ли задача
        """
        response = await self.check_exists(payload)
        if not response:
            return False

        response_dict = json.loads(response)
        if self.__is_ok_response(response_dict):
            await self.__set_TTL(payload)
        return True

    async def set_answer(self, payload: str, answer: dict):
        """Сохранение в очередь ответов ответа на задачу. Ключ - задача, значение - ответ

        :param payload: задача
        :param answer: ответ на задачу, либо json, либо контейнер (dict, list)
        """
        answer_json = json.dumps(answer) if not isinstance(answer, str) else answer
        await self.hset(self.service, payload, answer_json)

        is_ok = self.__is_ok_response(answer)
        await self.__set_TTL(payload, not is_ok)

    async def return_to_queue(self, payload: str):
        """Вернуть задачу из очереди задач обратно. Добавление в начало очереди

        Используется в случае, если задача не успешно обработалась

        :param payload: задача
        :return: ответ lpush
        """
        return await self.lpush(self.queue_main, payload)

    async def add_task(self, payload: str):
        """Добавить задачу в очередь задач. Добавление в конец очереди

        Используется для генерации тестовых заданий. Схожая логика с ``return_to_queue``
        :param payload: задача
        :return: ответ rpush
        """

        return await self.rpush(self.queue_main, payload)

    async def __set_TTL(self, payload: str, error=False):
        """Установка TTL на ответ

        :param payload: задача
        :param error: boolean - успешно выполненная задача или нет
        :return: ответ execute_command
        """
        ttl_time = self.task_ttl_error if error else self.task_ttl_ok
        return await self.__expire_member(payload, ttl_time)

    async def __get_task_from_queue(self):
        """Проверка на наличие задач из 2-х очередей - queue, reestr
        :return: None | str - задача или ничего
        """
        payload = await self.lpop(self.queue_main)

        if not payload:
            payload = await self.lpop(self.queue_registry)

        return payload

    def __is_ok_response(self, response: dict):
        """Успешно выполнена ли задача: код в диапазоне [200, 300)
        :return: boolean - да или нет
        """
        return 200 <= response.get("code", 200) < 300

    async def __expire_member(self, payload: str, ttl_time: int):
        """Установка TTL
        :param payload: задача
        :param ttl_time: время в секундах
        :return: ответ execute_command
        """
        return await self.execute_command(
            "EXPIREMEMBER", self.queue_response, payload, ttl_time
        )

    def __repr__(self):
        return f"KeyDBQueue(service={self.service})"
