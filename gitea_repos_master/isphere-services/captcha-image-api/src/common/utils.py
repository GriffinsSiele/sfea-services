import base64
import re
import time
from functools import wraps
from typing import Any, Optional, Union

from aiohttp import ClientSession, client_exceptions
from async_timeout import timeout as async_timeout
from pydantic import BaseModel, model_validator
from sqlalchemy import Column

from src.common.exceptions import BadRequestException
from src.common.logger import Logger


class ImageObject(BaseModel):
    content: bytes
    extension: Optional[str] = None

    @model_validator(mode="after")
    def serialize_extension(self) -> "ImageObject":
        if self.extension:
            content_type = self.extension.split(";")[0]
            self.extension = content_type.split("/")[-1]
        return self


class DecoderResult(BaseModel):
    id: Optional[int] = None
    accuracy: Optional[float] = None
    decode_time: Optional[float] = None
    provider: Optional[str] = None
    solution: Optional[str] = None


def format_float(num: float, precision: int = 5) -> float:
    return float("{:.{}f}".format(num, precision))


def format_data(
    data: dict[str, Any], exclude_null: bool, exclude_fields: list[str]
) -> dict[str, Any]:
    result = {}
    for key, value in data.items():
        if key not in exclude_fields:
            if isinstance(value, dict):
                nested_dict = format_data(value, exclude_null, exclude_fields)
                if nested_dict:
                    result[key] = nested_dict
            elif not exclude_null or (exclude_null and value is not None):
                result[key] = value
    return result


def extract_number(param: Union[str, int]) -> Optional[int]:
    if isinstance(param, (str, int)):
        match = re.search(r"\d+", str(param))
        if match:
            return int(match.group())
    return None


def timer(func):
    @wraps(func)
    def timer_wrapper(*args, **kwargs) -> tuple[Any, float]:
        start_time = time.perf_counter()
        result = func(*args, **kwargs)
        end_time = time.perf_counter()
        total_time = format_float(end_time - start_time)
        return result, total_time

    return timer_wrapper


def with_timeout(deadline: float = 60.0, msg_on_expire: Optional[str] = None):
    def decorator(func):
        @wraps(func)
        async def wrapper(*args, **kwargs):
            try:
                async with async_timeout(delay=deadline) as timeout_cm:
                    return await func(*args, **kwargs)
            except Exception as exc:
                if timeout_cm.expired:
                    _exc_message = (
                        msg_on_expire
                        or f"Expired timer error after {deadline}s for {func.__name__}."
                    )
                    raise BadRequestException(_exc_message)
                raise exc

        return wrapper

    return decorator


async def fetch_image_content(url: str) -> tuple[bytes, dict[str, Any]]:
    try:
        async with ClientSession() as session:
            async with session.get(url) as response:
                if response.status != 200:
                    raise BadRequestException(
                        message=f"Failed to fetch image. Response status code: {response.status}"
                    )
                image = await response.read()
                content_params = {
                    "type": response.content_type,
                    "size": response.content_length,
                }
                return image, content_params
    except TypeError:
        raise BadRequestException(message="Provided url-path is invalid: must be str")
    except client_exceptions.ClientError:
        raise BadRequestException(message=f"Unable to connect to {url}")


class S3Coder:
    @staticmethod
    def encode(payload: Optional[Union[str, Column[str]]] = None) -> Optional[str]:
        if payload is not None:
            return base64.b16encode(payload.encode()).decode()
        return None

    @staticmethod
    def decode(payload: Optional[str] = None) -> Optional[str]:
        if payload is not None:
            return base64.b16decode(payload.encode()).decode()
        return None


class SolutionSpecificationFormatter:
    @staticmethod
    def update_spec(spec: dict[str, Any]):
        for key, value in spec.items():
            if key in {"case", "math", "phrase"} and value is None:
                spec[key] = False
            if key in {"maxLength", "minLength"}:
                spec[key] = extract_number(value)

    @staticmethod
    def downgrade_spec(spec: dict[str, Any]):
        for key, value in spec.items():
            if key in {"case", "math", "phrase"} and value is False:
                spec[key] = None
            elif key == "maxLength" and value:
                spec[key] = f"<={extract_number(value)}"
            elif key == "minLength" and value:
                spec[key] = f">={extract_number(value)}"


class Singleton(type):
    _instances: Any = {}

    def __call__(cls, *args, **kwargs):
        if cls not in cls._instances:
            cls._instances[cls] = super(Singleton, cls).__call__(*args, **kwargs)
        return cls._instances[cls]


class SingletonLogging(metaclass=Singleton):
    def __init__(self):
        self.logger = Logger(self.__class__.__name__).get_logger()


class classproperty:
    def __init__(self, func):
        self.fget = func

    def __get__(self, instance, owner) -> Any:
        return self.fget(owner)
