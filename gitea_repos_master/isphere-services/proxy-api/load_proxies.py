"""
Run this script with a one argument (the path to the json file with the proxies' data):
proxy-api$ python3 load_proxies.py /home/user/proxies.json
"""

import asyncio
import datetime
import json
import logging
import sys

from app.models import Proxy, Tag
from app.settings import LOGGER_NAME
from app.utils.database import DatabaseManager
from app.utils.initializer import Initializer


logger = logging.getLogger(LOGGER_NAME)


class TagsProxiesLoader:
    # Tag names used to create Tag instances
    __TAG_NAMES = [
        "static",
        "mobile",
        "resident",
        "non_resident",
        "reserved",
        "ipv4",
        "ipv6",
        "traffic",
        "unlimited",
        "rotate_ip",
        "rotate_ip_per_60",
    ]

    # Matches the group number with tags by indexes from the __TAG_NAMES list
    __TAG_GROUPS = {
        1: [0, 2],
        2: [0, 3],
        3: [0, 2, 4],
        4: [1, 2, 7, 9],
        5: [1, 2, 8, 10],
        6: [1, 6],
    }

    def __init__(self, proxy_file_path: str):
        self.proxy_file_path = proxy_file_path

    def __read_json(self) -> list[dict]:
        with open(self.proxy_file_path, "r") as json_file:
            data = json.load(json_file)
        return data

    def __get_tag_objects(self) -> list[Tag]:
        """Returns Tag instances for each tag name from __TAG_NAMES list"""
        return [Tag(name=tag_name) for tag_name in self.__TAG_NAMES]

    def __build_tag_proxy_groups(self, tag_objects: list[Tag]) -> dict:
        """
        Returns a dictionary containing a list of Tag instances as key value. Each key
        represents the proxy group number. Buildings dictionary using the __TAG_GROUPS
        dictionary and the tag_objects parameter.
        """
        tags = {}
        len_tag_objects = len(tag_objects)
        for group_id, tag_ids in self.__TAG_GROUPS.items():
            tags[group_id] = []
            for tag_id in tag_ids:
                if tag_id < len_tag_objects:
                    tags[group_id].append(tag_objects[tag_id])
                else:
                    logger.error(
                        f"Tag with index {tag_id} does not exist. Check group with "
                        f"key {group_id} in __TAG_GROUPS dictionary.\n"
                    )
                    sys.exit(3)
        return tags

    async def load_tags_proxies(self):
        try:
            proxies_data = self.__read_json()
        except Exception as error:
            logger.error(error)
            sys.exit(2)

        # create Tag instances and the dictionary of tag groups
        tags = self.__get_tag_objects()
        tag_groups = self.__build_tag_proxy_groups(tags)

        await Initializer.create_db_tables()

        async with DatabaseManager.with_async_session() as session:
            session.add_all(tags)
            for proxy in proxies_data:
                proxy_obj = Proxy(
                    id=int(proxy["id"]),
                    created=datetime.datetime.fromisoformat(proxy["starttime"]),
                    host=proxy["server"],
                    port=int(proxy["port"]),
                    login=proxy["login"],
                    password=proxy["password"],
                    country=proxy["country"],
                    tags=tag_groups.get(int(proxy["proxygroup"]), []),
                )
                session.add(proxy_obj)
            await session.commit()

            # reset primary key sequence for Proxy model
            await session.execute(
                "SELECT setval('proxy_id_seq', (SELECT COALESCE(MAX(id), 0) FROM proxy) "
                "+ 1)"
            )


async def main():
    if len(sys.argv) < 2:
        logger.error(
            "Incorrect number of parameters. Pass the path to the json file with the "
            "proxies' data."
        )
        sys.exit(1)
    loader = TagsProxiesLoader(sys.argv[1])
    await loader.load_tags_proxies()


if __name__ == "__main__":
    asyncio.run(main())
