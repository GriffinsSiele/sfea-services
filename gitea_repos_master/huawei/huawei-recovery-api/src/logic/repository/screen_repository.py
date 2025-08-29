from isphere_exceptions.worker import InternalWorkerError

from src.logic.repository.screen import Screen
from src.logic.repository.screen_names import ScreenNames


class ScreenRepository(dict):
    """Хранилище экранов. Содержит методы для работы с экранами.
    Экраны хранятся в виде словаря, где ключ - название экрана,
    а значение - список всех известных версток данного экрана
    в формате экземпляров класса Screen.
    """

    def add_screen(self, name: ScreenNames, screen: Screen) -> None:
        """Добавляет новый экран в хранилище.

        :param name: Название экрана.
        :param screen: Описание экрана в формате экземпляра класса Screen.
        :return: None
        """
        if not super().get(name):
            self[name] = [screen]
            return
        self[name].append(screen)

    def get_screen(self, name: ScreenNames) -> list[Screen]:
        """Возвращает список всех известных версток экрана.
        Если экрана с запрошенным именем нет - возбуждает исключение InternalWorkerError.

        :param name: Название экрана.
        :return: Список всех известных версток экрана.
        """
        return self.get(name)

    def get_screens(
        self, names: list[ScreenNames]
    ) -> list[tuple[ScreenNames, list[Screen]]]:
        """Возвращает список всех известных версток экранов.
        В качестве аргумента принимает список имен экранов для извлечения.
        Если экрана с запрошенным именем нет - возбуждает исключение InternalWorkerError.

        :param names: Список названий экранов для извлечения.
        :return: Список кортежей из названия экрана и списка всех известных версток данного экрана.
        """
        result = []
        for name in names:
            result.append((name, self.get(name)))
        return result

    def get_screen_definitions(self, name: ScreenNames) -> int:
        """Возвращает общее количество определений экрана из всех известных версток экрана.
        В процессе работы проходит по всем версткам данного экрана, считает количество
        определений и суммирует, по окончании - возвращает общую сумму.
        Если экрана с запрошенным именем нет - возбуждает исключение InternalWorkerError.
        Данное значение может быть использовано для расчета времени ожидания на одно
        определение при ожидании появления экрана.

        :param name: Название экрана.
        :return: Общее количество определений экрана.
        """
        count_definitions = 0
        screens = self.get(name)
        for screen in screens:
            count_definitions += len(screen.definitions)
        return count_definitions

    def get_screens_definitions(self, names: list[ScreenNames]) -> int:
        """Возвращает общее количество определений экранов из всех известных версток экранов
        согласно запрошенного списка. В процессе работы проходит по всем экранам списка
        и всем версткам экрана, считает количество определений и суммирует,
        по окончании - возвращает общую сумму.

        Если экрана с запрошенным именем нет - возбуждает исключение InternalWorkerError.
        Данное значение может быть использовано доя расчета времени ожидания на одно
        определение при ожидании появления экранов.

        :param names:
        :return:
        """
        count_definitions = 0
        for name in names:
            screens = self.get(name)
            for screen in screens:
                count_definitions += len(screen.definitions)
        return count_definitions

    def get(self, key):
        """Возвращает значение из словаря по запрошенному ключу.
        Если ключа нет в словаре - возбуждает исключение InternalWorkerError.

        :param key: Ключ.
        :return: Значение.
        """
        result = super().get(key)
        if not result:
            raise InternalWorkerError(f'Screens "{key}" not defined')
        return result
