import base64
import logging
from typing import Optional

from src.request_params.api.image import ImageParams


class ImageAdapter:
    @staticmethod
    async def url_to_base64(url: str) -> Optional[str]:
        request = ImageParams(url=url)
        try:
            response = await request.request()
            content_type = response.headers["Content-Type"]
            content = base64.b64encode(response.content).decode("utf-8")
            return f"data:{content_type};base64,{content}"
        except Exception as e:
            logging.info(e)
        return None
