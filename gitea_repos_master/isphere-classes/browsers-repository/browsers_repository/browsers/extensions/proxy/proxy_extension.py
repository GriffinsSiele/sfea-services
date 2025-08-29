import pathlib
import tempfile

from browsers_repository.utils.utils import (
    copy_file,
    delete_dir,
    is_dir_exists,
    join_path,
    mk_dir,
)

_current_path = pathlib.Path(__file__).parent.absolute()


class ProxyExtension:
    def __init__(self):
        self._dir = join_path(tempfile.mkdtemp())

    def prepare(self, host: str, port: int, user: str, password: str) -> None:
        if is_dir_exists(self._dir):
            delete_dir(self._dir)
        mk_dir(self._dir)

        copy_file(join_path(_current_path, "manifest.json"), self._dir)

        with open(join_path(_current_path, "background_js.template"), "r") as file:
            background_js = file.read()
        background_js = background_js % (host, port, user, password)
        background_file = join_path(self._dir, "background.js")
        with open(background_file, mode="w") as f:
            f.write(background_js)

    @property
    def directory(self) -> str:
        return self._dir

    def __del__(self) -> None:
        delete_dir(self._dir)
