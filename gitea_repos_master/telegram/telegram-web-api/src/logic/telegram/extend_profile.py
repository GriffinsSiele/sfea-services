import asyncio
import base64
import logging
import os

from pydash import get
from requests_logic.proxy import ProxyManager

from src.config.app import ConfigApp
from src.config.settings import PROXY_URL
from src.logic.adapters.bs4_adapter import BS4Adapter
from src.logic.adapters.profile import ProfileAdapter
from src.logic.proxy.proxy import proxy_cache_manager
from src.logic.validation import ResponseValidation
from src.request_params.api.telegram_delete import TelegramDeleteParams
from src.request_params.api.telegram_download import TelegramDownloadParams
from src.request_params.api.telegram_get import TelegramGetParams
from src.request_params.api.telegram_photo import TelegramPhotoParams
from src.request_params.api.username import UsernameParams


class ExtendProfile:
    def __init__(self, client, proxy=None, logger=logging):
        self.client = client
        self.proxy = proxy
        self.logging = logger

    async def extend_users(self, users, *args, **kwargs):
        self.logging.info(
            f"Detected users count: {len(users)}. Search for only first user."
        )
        user = await self.extend_user(users[0], *args, **kwargs)
        self.logging.info(f"Extended user: {user}")
        users = await self.__concat_images([user])
        return users

    async def extend_user(self, user, is_full_user=False):
        user_casted = (
            ProfileAdapter.cast_full(user)
            if is_full_user
            else ProfileAdapter.cast(user, extract_name=False)
        )
        self.logging.info(f"User: {user_casted}")

        username = get(user_casted, "list__username.0")
        user_id = get(user_casted, "id")

        async def dummy_async(x):
            return x

        if is_full_user:
            extension_function = dummy_async(user_casted)
        elif username:
            extension_function = self.__extend_by_link(username)
        else:
            extension_function = self.__extend_by_deletion(user_id)

        tasks = [
            self.supressed_error(TelegramPhotoParams(self.client, user_id)),
            extension_function,
        ]

        responses = await asyncio.gather(*tasks)

        return {**user_casted, **responses[1], "__photos": responses[0]}

    async def supressed_error(self, request):
        try:
            return await ResponseValidation.validate_request(request)
        except Exception as e:
            logging.info(e)
            return {}

    async def __extend_by_link(self, username):
        self.logging.info(f"Extend {username} by link")
        proxy = await proxy_cache_manager.get_proxy(
            query={"proxygroup": "1"}, repeat=3, fallback_query={"proxygroup": "1"}
        )
        response = await self.supressed_error(UsernameParams(username, proxy=proxy))
        user_casted = BS4Adapter.cast(response.text) if response else {}
        self.logging.info(f"Extension response by link: {user_casted}")
        return user_casted if user_casted else {}

    async def __extend_by_deletion(self, user_id):
        self.logging.info(f"Extend {user_id} by Telegram API")

        await TelegramDeleteParams(self.client, user_id).request()
        self.logging.info("Deleted contact from imported")

        user = await self.supressed_error(TelegramGetParams(self.client, user_id))
        logging.info(f"Extension responses by Telegram API: {user}")
        return ProfileAdapter.cast_full(user)

    async def __concat_images(self, users):
        tasks = []

        for i, user in enumerate(users):
            photos = get(user, "__photos", [])[: ConfigApp.MAX_AVATAR_DOWNLOAD_COUNT]
            for photo in photos:
                tasks.append({"photo": photo, "user": i})

        self.logging.info(f"Downloading {len(tasks)} images...")

        images = await asyncio.gather(*[self.__extend_image(task) for task in tasks])
        users = await self.__merge_images(images, users)

        return users

    async def __extend_image(self, options):
        content = None
        try:
            photo_link = options.get("photo")
            photo_file = await TelegramDownloadParams(self.client, photo_link).request()

            with open(photo_file, "rb") as f:
                content = base64.b64encode(f.read()).decode()
                content = f"data:image/jpeg;base64,{content}"
            os.remove(photo_file)

        except Exception as e:
            logging.info(e)
        return {**options, "content": content}

    async def __merge_images(self, images, users):
        for photo in images:
            if photo["content"]:
                if not get(users[photo["user"]], "list__image"):
                    users[photo["user"]]["list__image"] = [photo["content"]]
                else:
                    users[photo["user"]]["list__image"].append(photo["content"])
        return users
