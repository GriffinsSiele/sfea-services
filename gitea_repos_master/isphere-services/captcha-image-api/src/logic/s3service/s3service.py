from typing import Any, Optional, Union

from asgiref.sync import sync_to_async
from boto3 import client
from botocore import exceptions as botocore_exceptions
from botocore.client import Config
from sqlalchemy import Column

from src.common import exceptions, logger, utils
from src.config.s3_config import s3_settings
from src.db.models import ImageTaskModel


class S3Service:
    """Service for S3 interactions."""

    def __init__(self):
        self.client = client(
            service_name="s3",
            region_name=s3_settings.S3_DEFAULT_REGION,
            aws_access_key_id=s3_settings.S3_ACCESS_KEY_ID,
            aws_secret_access_key=s3_settings.S3_SECRET_ACCESS_KEY,
            endpoint_url=s3_settings.S3_URL_PATH,
            config=Config(connect_timeout=20, retries={"max_attempts": 5}),
        )
        self.logger = logger.Logger("S3Service").get_logger()
        self.images_prefix = s3_settings.S3_PREFIX_IMAGES

    @staticmethod
    def clear_object_prefix(s3_object: str) -> str:
        return s3_object.split("/")[-1]

    @staticmethod
    def add_object_prefix(prefix: str, filename: Union[str, Column[str]]) -> str:
        return f"{prefix.rstrip('/')}/{filename.lstrip('/')}"

    async def ping_bucket(self, bucket: str) -> Optional[str]:
        try:
            await sync_to_async(self.client.head_bucket)(Bucket=bucket)
            return None
        except botocore_exceptions.ClientError as e:
            error_code = e.response["Error"]["Code"]
            if error_code == "404":
                return f"Bucket '{bucket}' does not exist."
            elif error_code == "403":
                return f"No permission to access the bucket '{bucket}'."
            return f"An error occurred: {type(e)} {e}"
        except botocore_exceptions.BotoCoreError as e:
            return f"An error occurred: {type(e)} {e}"

    async def get_object(self, bucket: str, key: str):
        try:
            s3_object = await sync_to_async(self.client.get_object)(
                Bucket=bucket, Key=key
            )
        except botocore_exceptions.ClientError:
            raise exceptions.BadRequestException(message="S3 object does not exist")
        return s3_object

    def get_bucket_objects(self, bucket: str, prefix: str = "") -> list[str]:
        bucket_objects: dict[str, Any] = self.client.list_objects_v2(
            Bucket=bucket,
            Prefix=prefix,
        )
        contents = bucket_objects.get("Contents")
        return (
            sorted({obj["Key"] for obj in bucket_objects["Contents"]})
            if contents is not None
            else []
        )

    def get_paginator_pages(self, bucket: str, prefix: str = "", page_size: int = 25):
        paginator = self.client.get_paginator("list_objects_v2")
        pages = paginator.paginate(
            Bucket=bucket,
            Prefix=prefix,
            PaginationConfig={
                "PageSize": page_size,
            },
        )
        return pages

    async def get_object_tags(
        self, bucket: str, s3_object: Union[Column[str], str]
    ) -> dict[str, Any]:
        try:
            object_meta = await sync_to_async(self.client.get_object_tagging)(
                Bucket=bucket,
                Key=s3_object,
            )
        except botocore_exceptions.ClientError:
            raise exceptions.BadRequestException(message="S3 object does not exist")

        params = {}
        tag_set = object_meta.get("TagSet")
        if tag_set is not None:
            for tag_param in object_meta["TagSet"]:
                key, value = tag_param["Key"], tag_param["Value"]
                if key == "characters":
                    value = utils.S3Coder.decode(value)
                params[key] = value
        return params

    async def add_object_tags(
        self, bucket: str, s3_object: Union[Column[str], str], tags_data: dict[str, Any]
    ):
        tags = [{"Key": k, "Value": str(v)} for k, v in tags_data.items()]
        await sync_to_async(self.client.put_object_tagging)(
            Bucket=bucket, Key=s3_object, Tagging={"TagSet": tags}
        )

    async def update_image_tags(
        self, bucket: str, task: "ImageTaskModel", update_data: dict[str, Any]
    ):
        if task.s3_filename is not None:
            s3_filename = self.add_object_prefix(prefix=self.images_prefix, filename=task.s3_filename)  # type: ignore[arg-type]
            object_tags = await self.get_object_tags(bucket, s3_filename)
            object_tags.update(update_data)
            await self.add_object_tags(
                bucket=bucket, s3_object=s3_filename, tags_data=object_tags
            )
            self.logger.info(
                f"Updated S3 object tags, TASK: {task.id}, TAGS DATA: {object_tags}"
            )

    async def upload_image(
        self, bucket: str, file: bytes, filename: str, tags_data: dict[str, Any]
    ):
        s3_filename = self.add_object_prefix(prefix=self.images_prefix, filename=filename)
        await sync_to_async(self.client.put_object)(
            Body=file,
            Bucket=bucket,
            Key=s3_filename,
        )
        await self.add_object_tags(
            bucket=bucket, s3_object=s3_filename, tags_data=tags_data
        )

    async def download_file(self, bucket: str, s3_object: str, store_path: str) -> None:
        await sync_to_async(self.client.download_file)(
            Bucket=bucket, Key=s3_object, Filename=store_path
        )

    async def delete_objects(self, bucket: str, s3_objects: list[str]) -> None:
        objs_keys = [{"Key": obj_key} for obj_key in s3_objects]
        await sync_to_async(self.client.delete_objects)(
            Bucket=bucket,
            Delete={"Objects": objs_keys},
        )


service: "S3Service" = S3Service()
