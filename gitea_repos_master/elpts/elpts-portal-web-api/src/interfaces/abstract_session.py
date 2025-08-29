from abc import ABC, abstractmethod

SessionAndMainPageData = tuple[str, dict] | tuple[None, None]


class AbstractElPtsSession(ABC):
    session_id: str
    main_page: dict

    @abstractmethod
    def load_saved_session(self) -> SessionAndMainPageData:
        pass

    @abstractmethod
    def save_session(self, session_id: str, main_page: dict) -> None:
        pass

    @abstractmethod
    def clean_session(self) -> None:
        pass
