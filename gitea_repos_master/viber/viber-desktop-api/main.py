from asyncio.log import logger
from pyvirtualdisplay.smartdisplay import SmartDisplay
from src.mongo import mongo
from queue_logic.answer import KeyDBAnswer
from src.kdb import NewKeyDBQueue as KeyDBQueue
import logging
from settings import (
    POD,
    MONGO_URL,
    MONGO_DB,
    KEYDB_URL,
    USE_BEFORE_EXIT,
    MONGO_COLLECTION,
    APP,
)
from src.exceptions import UsesError
from pydash import get
import time

logging.basicConfig(
    format="%(asctime)s - [%(levelname)s] - (%(filename)s).%(funcName)s(%(lineno)d) - %(message)s",
    level=logging.INFO,
)


def prepare_data(source_data=None):
    response = KeyDBAnswer()
    try:
        if source_data:
            response.add_record()
            if get(source_data, "nic"):
                response.add_field(
                    field_name="name",
                    value=get(source_data, "nic"),
                    description="Имя",
                    field_type="string",
                )
            if get(source_data, "img"):
                response.add_field(
                    field_name="Photo",
                    value=f'data:image/png;base64,{get(source_data, "img")}',
                    description="Фото",
                    field_type="image",
                )
            response.finish_record()
            response.dst["code"] = 200
    finally:
        return response.get_dst()


def main(phone):
    start = time.time()
    # try:
    if viber.uses > USE_BEFORE_EXIT:
        raise UsesError()
    res = viber.search(phone)
    mongo.increment_success()
    if get(res, "in_viber"):
        logging.info(f"phone {phone} found by {time.time() - start} sec")

    else:
        logging.info(f"phone {phone} not found by {time.time() - start} sec")
        res = None
    return prepare_data(res)


if __name__ == "__main__":
    with SmartDisplay(visible=False, size=(1024, 768), use_xauth=True) as display:
        from src.viber import Viber

        mongo.init(
            mongo_url=MONGO_URL,
            mongo_db=MONGO_DB,
            mongo_collection=MONGO_COLLECTION,
            pod=POD,
        )
        session = mongo.get()
        viber = Viber(display=display)
        start = viber.start()
        if start["code"] == 500:
            logging.error("i can not register profile")
            exit(1)
        elif start["code"] == 201:
            mongo.add(phone=start["phone"])
            logger.info(f'registered profile {start["phone"]}')
            session = mongo.get()

        kdb = KeyDBQueue(KEYDB_URL, APP)
        kdb.run_loop(main)
