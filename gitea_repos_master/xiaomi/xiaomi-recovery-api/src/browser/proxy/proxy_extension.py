import pathlib
import tempfile

from putils_logic.putils import PUtils

from src.interfaces.abstract_extension import AbstractProxyExtension

_current_path = pathlib.Path(__file__).parent.absolute()


class ProxyExtension(AbstractProxyExtension):
    def __init__(self):
        self._dir = PUtils.bp(tempfile.mkdtemp())

    def prepare(self, host: str, port: int, user: str, password: str) -> None:
        if PUtils.is_dir_exists(self._dir):
            PUtils.delete_dir(self._dir)
        PUtils.mkdir(self._dir)

        PUtils.copy_file(PUtils.bp(_current_path, "manifest.json"), self._dir)

        with open(PUtils.bp(_current_path, "background_js.template"), "r") as file:
            background_js = file.read()
        background_js = background_js % (host, port, user, password)
        background_file = PUtils.bp(self._dir, "background.js")
        with open(background_file, mode="w") as f:
            f.write(background_js)

    @property
    def directory(self) -> str:
        return self._dir

    def __del__(self) -> None:
        PUtils.delete_dir(self._dir)
