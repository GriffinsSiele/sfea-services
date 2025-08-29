from isphere_exceptions.worker import InternalWorkerError

from src.logic.repository.screens import Screen


class ScreensRepository(dict):
    def add_page(self, title: str, obj: Screen) -> None:
        if not isinstance(obj, Screen):
            raise ValueError(f'{obj} must be an instance of the "Screen" class')
        self[title] = obj

    def get_page(self, title: str) -> Screen:
        if screen := self.get(title):
            return screen
        raise InternalWorkerError(f'The screen "{title}" not defined')
