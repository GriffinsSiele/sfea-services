import asyncio
import os
import sys
from typing import Any

from asgiref.sync import sync_to_async
from boto3 import client
from botocore.client import Config
from dotenv import load_dotenv

load_dotenv(override=True)


CONFIG = {
    "S3_BUCKET_OLD": os.getenv("S3_BUCKET_OLD"),
    "S3_URL_PATH_OLD": os.getenv("S3_URL_PATH_OLD"),
    "S3_DEFAULT_REGION_OLD": os.getenv("S3_DEFAULT_REGION_OLD"),
    "S3_ACCESS_KEY_ID_OLD": os.getenv("S3_ACCESS_KEY_ID_OLD"),
    "S3_SECRET_ACCESS_KEY_OLD": os.getenv("S3_SECRET_ACCESS_KEY_OLD"),
    "S3_PREFIX_NNETWORKS_OLD": os.getenv("S3_PREFIX_NNETWORKS_OLD"),
    "S3_BUCKET_NEW": os.getenv("S3_BUCKET_NEW"),
    "S3_ACCESS_KEY_ID_NEW": os.getenv("S3_ACCESS_KEY_ID_NEW"),
    "S3_SECRET_ACCESS_KEY_NEW": os.getenv("S3_SECRET_ACCESS_KEY_NEW"),
    "S3_DEFAULT_REGION_NEW": os.getenv("S3_DEFAULT_REGION_NEW"),
    "S3_URL_PATH_NEW": os.getenv("S3_URL_PATH_NEW"),
    "S3_PREFIX_NNETWORKS_NEW": os.getenv("S3_PREFIX_NNETWORKS_NEW"),
}


def validate_config(config_data: dict[str, str]) -> None:
    not_provided_vars = {k for k, v in config_data.items() if v is None}
    if not_provided_vars:
        print(f"Validation error. Empty config variables: {', '.join(not_provided_vars)}")
        sys.exit(3)


class S3Client:
    def __init__(
        self,
        bucket: str,
        region: str,
        access_key: str,
        secret_access_key: str,
        url: str,
        prefix: str,
    ):
        self.bucket = bucket
        self.prefix = prefix
        self.client = client(
            service_name="s3",
            region_name=region,
            aws_access_key_id=access_key,
            aws_secret_access_key=secret_access_key,
            endpoint_url=url,
            config=Config(connect_timeout=10, retries={"max_attempts": 5}),
        )

    def get_paginator_pages(self):
        paginator = self.client.get_paginator("list_objects_v2")
        pages = paginator.paginate(
            Bucket=self.bucket,
            Prefix=f"{self.prefix.rstrip('/')}",
            PaginationConfig={
                "PageSize": 5,
            },
        )
        return pages

    async def get_object(self, key: str):
        s3_object = await sync_to_async(self.client.get_object)(
            Bucket=self.bucket, Key=key
        )
        return s3_object

    async def get_object_tags(self, key: str) -> dict[str, Any]:
        object_meta = await sync_to_async(self.client.get_object_tagging)(
            Bucket=self.bucket,
            Key=key,
        )
        return object_meta.get("TagSet")

    async def add_object_tags(self, key: str, tags_data: dict[str, Any]):
        await sync_to_async(self.client.put_object_tagging)(
            Bucket=self.bucket, Key=key, Tagging={"TagSet": tags_data}
        )

    async def add_object(self, file: bytes, key: str, tags_data: dict[str, Any]):
        print(f"Uploading object '{key}'")
        await sync_to_async(self.client.put_object)(
            Body=file,
            Bucket=self.bucket,
            Key=key,
        )
        await self.add_object_tags(key=key, tags_data=tags_data)


class S3Transition:
    def __init__(self, client_transit_from: S3Client, client_transit_to: S3Client):
        self.transit_from = client_transit_from
        self.transit_to = client_transit_to
        self.transit_count = 0

    @staticmethod
    def clear_object_prefix(s3_object: str) -> str:
        return s3_object.split("/")[-1]

    @staticmethod
    def add_object_prefix(prefix: str, filename: str) -> str:
        return f"{prefix.rstrip('/')}/{filename.lstrip('/')}"

    async def get_object_data(self, key: str) -> dict[str, Any]:
        print(f"Collecting '{key}' object's data")
        s3_object = await self.transit_from.get_object(key=key)
        tags = await self.transit_from.get_object_tags(key=key)
        return {
            "key": self.clear_object_prefix(key),
            "file": s3_object.get("Body"),
            "tags": tags,
        }

    async def transit_object(self, key: str):
        try:
            obj_data = await self.get_object_data(key)
            await self.transit_to.add_object(
                file=obj_data["file"].read(),
                key=self.add_object_prefix(
                    prefix=self.transit_to.prefix, filename=obj_data["key"]
                ),
                tags_data=obj_data["tags"],
            )
            self.transit_count += 1
        except Exception as exc:
            print(
                f"Error while uploading {key}: {type(exc)} {exc.message if hasattr(exc, 'message') else exc.__str__()}"
            )

    async def init_transition(self) -> None:
        pages = self.transit_from.get_paginator_pages()
        for page in pages:
            objs_list: list[dict[str, Any]] = page.get("Contents", [])
            if objs_list:
                obj_keys = [obj["Key"] for obj in objs_list]
                await asyncio.gather(*[self.transit_object(key) for key in obj_keys])


async def main():
    validate_config(CONFIG)
    client_transit_from = S3Client(
        bucket=CONFIG["S3_BUCKET_OLD"],
        region=CONFIG["S3_DEFAULT_REGION_OLD"],
        access_key=CONFIG["S3_ACCESS_KEY_ID_OLD"],
        secret_access_key=CONFIG["S3_SECRET_ACCESS_KEY_OLD"],
        url=CONFIG["S3_URL_PATH_OLD"],
        prefix=CONFIG["S3_PREFIX_NNETWORKS_OLD"],
    )
    client_transit_to = S3Client(
        bucket=CONFIG["S3_BUCKET_NEW"],
        region=CONFIG["S3_DEFAULT_REGION_NEW"],
        access_key=CONFIG["S3_ACCESS_KEY_ID_NEW"],
        secret_access_key=CONFIG["S3_SECRET_ACCESS_KEY_NEW"],
        url=CONFIG["S3_URL_PATH_NEW"],
        prefix=CONFIG["S3_PREFIX_NNETWORKS_NEW"],
    )
    transition = S3Transition(client_transit_from, client_transit_to)
    await transition.init_transition()
    print(f"Transited overall {transition.transit_count} objects")


if __name__ == "__main__":
    asyncio.run(main())
