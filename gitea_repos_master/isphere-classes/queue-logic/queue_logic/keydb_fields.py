from queue_logic.logger import logger


class KeyDBFields:
    """Вспомогательный класс для хранения значений по умолчанию класса клиента KeyDB"""

    def __init__(self):
        self.service = ""
        self.task_ttl_ok = 86400  # 60 * 60 * 24 = 24h
        self.task_ttl_error = 600  # 60 * 10 = 10min

    @property
    def task_ttl_ok(self) -> int:
        """Геттер на получение времени хранения успешного ответа в очереди KeyDB

        :return: время в секундах
        """
        return self._task_ttl_ok

    @task_ttl_ok.setter
    def task_ttl_ok(self, v: int):
        """Сеттер на установку времени хранения успешного ответа в очереди KeyDB

        :param v: время в секундах
        """
        logger.debug(f"Updated field task_ttl_ok: {v}")
        self._task_ttl_ok = v

    @property
    def task_ttl_error(self) -> int:
        """Геттер на получение времени хранения неуспешного ответа в очереди KeyDB

        :return: время в секундах
        """
        return self._task_ttl_error

    @task_ttl_error.setter
    def task_ttl_error(self, v: int):
        """Сеттер на установку времени хранения неуспешного ответа в очереди KeyDB

        :param v: время в секундах
        """
        logger.debug(f"Updated field task_ttl_error: {v}")
        self._task_ttl_error = v

    @property
    def queue_main(self) -> str:
        """Получение имени очереди в KeyDB, из которой будут браться задачи.

        Например, ``callapp_queue`` - очередь задач

        :return: название очереди
        """
        return f"{self.service}_queue"

    @property
    def queue_registry(self) -> str:
        """Получение имени очереди в KeyDB, из которой будут браться задачи
        обработки реестров.

        Например, ``callapp_reestr`` - очередь задач реестра

        :return: название очереди
        """
        return f"{self.service}_reestr"

    @property
    def queue_response(self) -> str:
        """Получение имени очереди в KeyDB, в которую будут класться ответы
        на задачи из предыдуших выше очередей

        Например, ``callapp`` - очередь ответов

        :return: название очереди
        """
        return self.service
