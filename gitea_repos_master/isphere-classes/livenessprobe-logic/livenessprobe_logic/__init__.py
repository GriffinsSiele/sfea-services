import time

from putils_logic.putils import PUtils


class HealthCheck:
    """Класс проверки состояния LivenessProbe k8s

    Экземпляр класса создается в исполняемом файле /bin/healthtest, который периодически
    вызывается k8s для проверки работоспособности запущенного приложения. В случае exit
    code = 0 - все ок, иначе перезапуск приложения. Проверка осуществляется за счет оценки
    времени записи последнего ответа (timestamp) в определенный файл. Если timestamp был
    записан более N секунд (например, 10 мин) назад, то exit code = 1. Обработчики
    периодически вызывают метод ``checkpoint`` с целью обновления timestamp, тем самым
    обновляя время жизни. Зависшие обработчики этого не делают, и поэтому проверку по
    timestamp не пройдут
    """

    def __init__(self) -> None:
        """Создание класса, указание имени файла с timestamp"""
        self.file = "/tmp/app_alive.pid"

    def check(self, min_period: float) -> None:
        """Проверка записанного timestamp.

        Если разница между текущим временем и timestamp больше `min_period` в секундах,
        то exit code = 0

        :param min_period: максимальное время, когда обработчик может не вызывать
        ``checkpoint`` метод, в секундах.
        :return: exit code 0 или 1
        """
        if not PUtils().is_file_exists(self.file):
            self.checkpoint()
        last_time = float(self.__read_timestamp())
        if time.time() - last_time > min_period:
            exit(1)
        else:
            exit(0)

    def __read_timestamp(self) -> str:
        with open(self.file, "r") as f:
            return f.read()

    def checkpoint(self, timestamp=None):
        """Основная общедоступная функция для обработчика.

        Должна быть вызвана обработчиком для того, чтобы показать, что он 'живой'.
        Основной сценарий использования - вызвать после успешной обработки задачи
        :param timestamp: опциональное время timestamp на запись в файл
        """
        if not timestamp:
            timestamp = time.time()
        with open(self.file, "w") as f:
            f.write(str(timestamp))
