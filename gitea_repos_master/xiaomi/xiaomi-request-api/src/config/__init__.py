import pathlib

from putils_logic import PUtils

from .app import ConfigApp
from .settings import settings

_current_file_path = pathlib.Path(__file__).parent.absolute()
_public_key_file = PUtils.bp(_current_file_path, "xiaomi_public_key.cert")

with open(_public_key_file, "r") as file:
    ConfigApp.PUBLIC_KEY = file.read()

__all__ = ("ConfigApp", "settings")
