from typing import Optional

from pydantic import BaseModel, Field

from fastapi import UploadFile


class InputData(BaseModel):
    header_file: UploadFile = Field(description="Изображение основных заголовков")
    body_file: UploadFile = Field(description="Изображение основных значений")
    policy_file: Optional[UploadFile] = Field(
        default=None, description="Изображение информации страхового полиса"
    )
    meta_info: Optional[str] = Field(
        "", description="Meta-информация в формате JSON-строки"
    )
