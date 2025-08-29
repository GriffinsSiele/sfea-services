import asyncio
from datetime import datetime

from mongo_client.client import MongoSessions
from opentele.api import UseCurrentSession
from opentele.td import TDesktop
from putils_logic.putils import PUtils
from pydash import filter_, find, get, sort_by
from telethon.sessions import StringSession
from worker_classes.logger import Logger

from src.config.app import ConfigApp
from src.config.settings import MONGO_COLLECTION, MONGO_DB, MONGO_URL, PROXY_URL
from src.logic.proxy.custom_proxy_register import MyProxyManager
from src.utils.mega_download import MegaDownloader
from src.utils.utils import zip_folder


async def run(path):
    tdata_folder = path
    files = PUtils.get_files(PUtils.bp(tdata_folder, ".."))
    password_file = find(files, lambda f: f.endswith(".txt"))
    if password_file and PUtils.is_file_exists(password_file):
        password = open(password_file, "r").read().strip().split(" ")[0]
    else:
        password = None

    tdesk = TDesktop(tdata_folder)

    client = await tdesk.ToTelethon(session="telethon.session", flag=UseCurrentSession)
    await client.connect()

    try:
        await client.TerminateAllSessions()
    except Exception as e:
        print(e)

    print(await client.get_me())

    auth_key = StringSession.save(client.session)
    api_id = client.api_id
    api_hash = client.api_hash
    proxy_id = "-1"

    tdata = zip_folder(tdata_folder)

    payload = {
        "session": {
            "auth_key": auth_key,
            "api_id": api_id,
            "api_hash": api_hash,
            "proxy_id": proxy_id,
            "password": password,
            "last_message": datetime.now(),
            "friends": [],
        },
        "tdata": tdata,
    }

    await client.disconnect()

    if PUtils.is_file_exists("telethon.session"):
        PUtils.delete_file("telethon.session")

    return payload


async def process_link(mongo, link):
    tdata = MegaDownloader.download(link)
    payload = await run(tdata)
    sessions = await mongo.get_sessions()
    existed = find(
        sessions,
        lambda s: s["session"]["auth_key"] == payload["session"]["auth_key"],
    )
    if existed:
        print(f"Existed session: {existed}")
        return tdata

    payload["session"]["proxy_id"] = await free_proxy(mongo)

    await mongo.add(payload)

    return tdata


async def free_proxy(mongo):
    proxies = await MyProxyManager(PROXY_URL).get_proxies(
        {"proxygroup": "5", "limit": 99999}
    )
    sessions = await mongo.get_sessions()

    data = {
        get(proxy, "extra_fields.id"): 0
        for proxy in proxies
        if get(proxy, "extra_fields.id") not in ConfigApp.BAN_PROXY_IDS
    }

    for session in sessions:
        id = get(session, "session.proxy_id")
        if id in data:
            data[id] += 1
        else:
            data[id] = 1

    if not data:
        raise KeyError("No proxies found")

    sorted_stats = sort_by(data.items(), lambda x: x[1])
    minimal_usage_pair = sorted_stats[0]
    if minimal_usage_pair[1] > ConfigApp.MAX_SESSIONS_ON_ONE_PROXY:
        raise KeyError("Too many sessions on one proxy")

    return minimal_usage_pair[0]


async def main(path):
    Logger().create()
    links = open(path).read().splitlines()
    links = filter_(links, lambda x: x)

    mongo = await MongoSessions(
        MONGO_URL, db=MONGO_DB, collection=MONGO_COLLECTION, max_allowed_reconnect=5
    ).connect()
    mongo.default_filter = {}

    for link in links:
        print(link)
        try:
            tdata = await process_link(mongo, link)
            PUtils.delete_dir(tdata)
        except Exception as e:
            tdata = await process_link(mongo, link)
            PUtils.delete_dir(tdata)


if __name__ == "__main__":
    asyncio.run(main("/home/alien/Downloads/df68b1.txt"))
