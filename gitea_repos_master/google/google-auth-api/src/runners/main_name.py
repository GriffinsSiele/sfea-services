import asyncio
import json
import logging
import pathlib

from putils_logic.putils import PUtils
from worker_classes.logger import Logger

from src.logic.google.search_manager_name import SearchGoogleNameManager

_current_file_path = pathlib.Path(__file__).parent.absolute()
_root_dir = PUtils.bp(_current_file_path, "..", "..")


async def run(json_data: dict):
    Logger().create()

    for _ in range(1):
        sm = SearchGoogleNameManager()
        await sm.prepare()
        print(await sm.is_ready())

        print(await sm.search(json.dumps(json_data)))


async def search(count: int = 10):
    Logger().create()
    steps = 0
    steps_max = count
    sm = SearchGoogleNameManager()
    await sm.prepare()
    with open(PUtils.bp(_root_dir, "test_data_searched.log"), "w") as file:
        for data_str, auth_data in load_from_file():
            result = ""
            if steps != 0:
                try:
                    result = await sm.search(auth_data)
                except Exception as e:
                    logging.info(f"Search result: {e}")
                    result = str(e)
                    await sm.prepare()
            if steps >= steps_max:
                break
            steps += 1
            logging.info(f"STEP: {steps}")

            file.write(f"{data_str}: {result}\n")
    await sm.clean()


def load_from_file():
    keys = ("last_name", "first_name", "middle_name", "birthdate", "phone", "email")
    with open(PUtils.bp(_root_dir, "test_data.log"), "r") as file:
        for _ in range(5001):
            data_str = file.readline().strip()
            data_list = data_str.split(";")
            yield data_str, dict(zip(keys, data_list))


if __name__ == "__main__":
    asyncio.run(search(5))
    # asyncio.run(run({"last_name": "", "first_name": "", "phone": ""}))
