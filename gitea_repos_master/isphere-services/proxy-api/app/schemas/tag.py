from pydantic import BaseModel, Field


class BaseTagSchema(BaseModel):
    name: str = Field(max_length=50)

    class Config:
        orm_mode = True
        schema_extra = {"example": {"name": "mobile"}}


class TagSchema(BaseTagSchema):
    id: int

    class Config:
        orm_mode = True
        schema_extra = {"example": {"id": 1, "name": "mobile"}}
