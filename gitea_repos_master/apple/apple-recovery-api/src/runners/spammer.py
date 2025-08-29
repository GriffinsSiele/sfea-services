import asyncio
import logging
from time import sleep

import requests
from aiohttp import ClientSession
from worker_classes.logger import Logger

from src.runners.mobile_phones import get_random_mobile_phones

URL = "http://0.0.0.0/search"

headers = {
    "accept": "application/json",
    "Content-Type": "application/json",
}

json_data = {
    "phone": "79166367863",
}


def make_phones_spam(count: int, interval: int) -> None:
    task_counter = 0
    for phone in get_random_mobile_phones(count):
        task_counter += 1
        logging.info(f"Task {task_counter} of {count}.")
        send_phone(phone)
        if interval:
            sleep(interval)


def send_phone(phone: str) -> None:
    json_data["phone"] = phone
    response = requests.post(URL, headers=headers, json=json_data)
    logging.info(f"Send phone: {phone}, status code: {response.status_code}")


async def make_phones_spam_async(
    total_tasks: int,
    tasks_per_interval: int,
) -> None:
    """
    Отправляет задачи асинхронно, по несколько штук.
    :param total_tasks: общее количество задач.
    :param tasks_per_interval: количество задач, отправляемых одновременно.
    :return: None
    """
    async with ClientSession() as session:
        tasks = []
        task_counter = 0

        for number, phone in enumerate(get_random_mobile_phones(total_tasks)):
            tasks.append(asyncio.create_task(send_phone_async(session, phone)))
            task_counter += 1
            if task_counter < tasks_per_interval:
                continue

            logging.info(
                f"Create {task_counter} tasks: {number + 2 - task_counter} - {number + 1}"
            )
            responses = await asyncio.gather(*tasks, return_exceptions=True)
            print_search_result(responses)
            tasks = []
            task_counter = 0

        if tasks:
            len_tasks = len(tasks)
            logging.info(
                f"Create {len_tasks} tasks: {total_tasks + 1 - len_tasks} - {total_tasks}"
            )
            responses = await asyncio.gather(*tasks)
            print_search_result(responses)

    logging.info("Done tasks")


def print_search_result(responses: list[tuple[str, int, str] | BaseException]) -> None:
    for response in responses:
        if isinstance(response, BaseException):
            logging.error(response)
            continue
        ph, code, text = response
        msg = f"Phone: {ph}, status code: {code}"
        if text:
            msg += f", message: {text}"
        logging.info(msg)


async def send_phone_async(session: ClientSession, phone: str) -> tuple[str, int, str]:
    json_data["phone"] = phone
    async with session.post(URL, headers=headers, json=json_data) as response:
        return phone, response.status, await response.text()


if __name__ == "__main__":
    Logger().create()
    # make_phones_spam(50, 2)
    asyncio.run(make_phones_spam_async(5, 5))
