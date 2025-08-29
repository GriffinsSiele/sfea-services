from pydantic import BaseModel, Field, root_validator, validator

from app.utils.list_utils import ListUtils


class BlockProxySchema(BaseModel):
    worker: str = Field(
        max_length=100,
        description='Name of data collection source, for example "avito", "callapp"',
    )
    block_proxy_ids: list[int] | None = Field(description="List of proxy ids to block")
    unblock_proxy_ids: list[int] | None = Field(
        description="List of proxy ids to unblock"
    )

    @validator("block_proxy_ids", "unblock_proxy_ids")
    def check_list_int(cls, value: list[int]):
        if value and list(filter(lambda x: x <= 0, value)):
            raise ValueError(f"All elements must be a positive: {value}")
        return value

    @root_validator
    def check_intersection(cls, values):
        block_ids = values.get("block_proxy_ids")
        unblock_ids = values.get("unblock_proxy_ids")
        if not block_ids and not unblock_ids:
            raise ValueError(
                f"Both parameters block_proxy_ids and unblock_proxy_ids cannot be empty!"
            )
        if (
            unblock_ids
            and block_ids
            and ListUtils.has_intersection(unblock_ids, block_ids)
        ):
            raise ValueError(f"block_proxy_ids and unblock_proxy_ids should not overlap!")
        return values

    class Config:
        schema_extra = {
            "example": {
                "worker": "callapp",
                "block_proxy_ids": [1, 2],
                "unblock_proxy_ids": [3, 4],
            }
        }
