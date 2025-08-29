# Скрипт загрузки датасета картинок капч, хранящихся на S3.
# Объекты на S3:
#  - хранятся в формате {ИМЯ_ИСТОЧНИКА}/{ХЭШ_ФАЙЛА}.{РАСШИРЕНИЕ} ;
#  - загружаются в формате {ХЭШ_ФАЙЛА}--{ОТВЕТ}.{РАСШИРЕНИЕ} .

# Запуск скрипта производится с помощью файла loader_images.sh

# Выборка объектов для загрузки осуществляется исходя из параметров, указанных в файле ./loader/config.json.
# Пример конфигурации config.json (загрузка картинок источника gosuslugi со статусом правильности решения 'Success'):
# {
#     "source": "gosuslugi",  - полное имя источника (str)
#     "status": "Success" - статус правильности решения (str)
# }

# Пример запуска с указанной выше конфигурацией:
# > sh loader.sh
#  Running images dataset loader...
#  Downloading images, source: gosuslugi, status: Success
#  Created storage dir: /app/2024_01_26-17_08_43_gosuslugi_True
#  Downloaded totally 25/25 images...
#  Downloaded totally 50/50 images...
#  ...
#  Downloaded totally 1099/1100 images...
#  Downloaded totally 1114/1115 images...
#  Downloaded 1114 images by 150.02443591899646s

import base64
import binascii
import json
import os
import pathlib
import sys
import time
from datetime import datetime
from typing import Any

from boto3 import client
from botocore.client import Config
from dotenv import load_dotenv
from putils_logic.putils import PUtils

load_dotenv(override=True)


def validate_config_data(data: dict[str, Any]) -> None:
    source: str = data.get("source", "")
    status: str = data.get("status", "")

    if not source:
        print("Parameter 'source' must be provided in config.json")
        sys.exit(3)
    if not status:
        print(
            "Parameter 'status' must be provided in config.json as 'InUse', 'Success' or 'Fail'"
        )
        sys.exit(3)

    data["source"] = source.lower()
    data["status"] = status


def load_config() -> dict[str, Any]:
    path = PUtils.bp_abs(pathlib.Path(__file__), "..", "config.json")

    with open(path) as config_file:
        data = json.load(config_file)
    validate_config_data(data)
    return data


class S3CaptchaDatasetLoader:
    PAGINATION_PAGE_SIZE: int = 25
    CONNECTION_CONFIG: dict[str, Any] = {
        "aws_access_key_id": os.getenv("S3_ACCESS_KEY_ID"),
        "aws_secret_access_key": os.getenv("S3_SECRET_ACCESS_KEY"),
        "region_name": os.getenv("S3_DEFAULT_REGION"),
        "endpoint_url": os.getenv("S3_URL_PATH"),
        "config": Config(connect_timeout=20, retries={"max_attempts": 5}),
    }

    def __init__(self, config: dict[str, Any]):
        self.client = client(service_name="s3", **self.CONNECTION_CONFIG)
        self.bucket: Any = os.getenv("S3_BUCKET_MAIN")
        self.counter: dict[str, int] = {"fetched_by_pages": 0, "loaded": 0}
        self.objects_prefix: Any = os.getenv("S3_PREFIX_IMAGES")
        self.source: str = config["source"]
        self.status: str = config["status"]

    def _create_download_dir(self) -> str:
        storage_dir = PUtils.bp_abs(
            f'{datetime.now().strftime("%Y_%m_%d-%H_%M_%S")}_{self.source}_{self.status}'
        )
        if not PUtils.is_dir_exists(storage_dir):
            PUtils.mkdir(storage_dir)
            print(f"Created storage dir: {storage_dir}")
        return storage_dir

    def get_paginator_pages(self, s3_folder: str):
        paginator = self.client.get_paginator("list_objects_v2")
        pages = paginator.paginate(
            Bucket=self.bucket,
            Prefix=f"{self.objects_prefix.rstrip('/')}/{s3_folder}/",
            PaginationConfig={
                "PageSize": self.PAGINATION_PAGE_SIZE,
            },
        )
        return pages

    def get_tags(self, bucket: str, s3_object: str):
        object_meta = self.client.get_object_tagging(
            Bucket=bucket,
            Key=s3_object,
        )
        params = {}
        tag_set = object_meta.get("TagSet")
        if tag_set is not None:
            for tag_param in object_meta["TagSet"]:
                key, value = tag_param["Key"], tag_param["Value"]
                if key == "solution":
                    try:
                        value = base64.b16decode(value.encode()).decode()
                    except binascii.Error:
                        pass
                params[key] = value
        return params

    def download_s3_folder(self):
        storage_dir = self._create_download_dir()
        pages = self.get_paginator_pages(s3_folder=self.source)
        print(f"Downloading images, source: '{self.source}', status: '{self.status}'")

        for page in pages:
            objs_list = page.get("Contents", [])
            self.counter["fetched_by_pages"] += len(objs_list)

            for obj in objs_list:
                obj_key = obj.get("Key")
                obj_tags = self.get_tags(bucket=self.bucket, s3_object=obj_key)
                solution, status = obj_tags.get("solution"), obj_tags.get("status")
                if status == self.status and solution != "None":
                    name, extension = obj_key.split("/")[-1].split(".")
                    self.client.download_file(
                        Bucket=self.bucket,
                        Key=obj_key,
                        Filename=f"{storage_dir}/{name}--{solution}.{extension}",
                    )
                    self.counter["loaded"] += 1

            print(
                f"Downloaded {self.counter['loaded']}/{self.counter['fetched_by_pages']} images..."
            )


if __name__ == "__main__":
    config = load_config()
    loader = S3CaptchaDatasetLoader(config)
    start_time = time.perf_counter()
    loader.download_s3_folder()
    print(
        f"Downloaded {loader.counter['loaded']} images by {time.perf_counter() - start_time:.2f}s"
    )
