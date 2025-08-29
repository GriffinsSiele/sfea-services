import asyncio
import pathlib

from putils_logic import PUtils

from src.config import ConfigApp, settings
from src.logger.context_logger import logging

_current_path = pathlib.Path(__file__).parent.absolute()
_hmac_js_path = PUtils.bp(_current_path, "hmac.js")


class Hmac:
    def __init__(self):
        if not PUtils.is_file_exists(settings.NODE_PATH):
            raise FileNotFoundError(settings.NODE_PATH)

    @staticmethod
    async def async_hash(input_data: str) -> str:
        cmd = f"{settings.NODE_PATH} {_hmac_js_path} {input_data}"
        subprocess = asyncio.create_subprocess_shell(
            cmd, stdout=asyncio.subprocess.PIPE, stderr=asyncio.subprocess.PIPE
        )
        subprocess_task = asyncio.create_task(subprocess)
        try:
            proc = await asyncio.wait_for(subprocess_task, timeout=ConfigApp.HMAC_TIMEOUT)
            stdout, stderr = await proc.communicate()
            if stdout:
                return stdout.decode(encoding="utf-8").strip()
        except asyncio.exceptions.TimeoutError:
            logging.error("Hmac calculation timeout")

        return ""
