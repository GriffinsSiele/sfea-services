from fastapi import FastAPI, Query
import uvicorn
from typing import Optional
from src.mongo import ishere_session
from src.kdb import KeyDBQueue
from settings import APP_PORT, KEYDB_HOST, KEYDB_PASSWORD, MONGO_HOST
from async_timeout import timeout
from src.asyncify import async_wrap

session = ishere_session(f'mongodb://{MONGO_HOST}', 27017)
mng = FastAPI(title='Monitoring API')
kdb_isphere = KeyDBQueue(host=KEYDB_HOST, password=KEYDB_PASSWORD)


@mng.get("/sessions", tags=['Mongo'])
async def sessions(app: str = Query(kdb_isphere.get_keys()[0], enum=session.list_apps()),
                   active: Optional[bool] = None,
                   phone: Optional[str] = None):
    return await async_wrap(session.list)(app, active, phone)


@mng.get("/kdb_queue", tags=['KeyDB'])
async def lens(app: str = Query(kdb_isphere.get_keys()[0], enum=kdb_isphere.get_keys())):
    return await async_wrap(kdb_isphere.get_len)(app)


@mng.get("/ping_mongo", tags=['Mongo'])
async def ping_mongo(timeout_ping: float):
    try:
        async with timeout(timeout_ping):
            mongo_test_isphere = await async_wrap(ishere_session)(f'mongodb://{MONGO_HOST}', 27017)
            ping_response = await async_wrap(mongo_test_isphere.db.command)('ping')
            return bool(ping_response['ok'])
    except:
        return False


@mng.get("/ping_keydb", tags=['KeyDB'])
async def ping_keydb(timeout_ping: float):
    try:
        async with timeout(timeout_ping):
            kdb_test_isphere = await async_wrap(KeyDBQueue)(host=KEYDB_HOST, password=KEYDB_PASSWORD)
            return await async_wrap(kdb_test_isphere.db.ping)()
    except Exception as e:
        return False


if __name__ == '__main__':
    uvicorn.run('main:mng', host='0.0.0.0', port=APP_PORT)
