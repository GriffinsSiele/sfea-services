"""
Модуль содержит код для ручной проверки работоспособности приложения.
В работе приложения не участвует и не требуется.
"""

import asyncio
import logging
import random
from time import sleep

import requests
from aiohttp import ClientSession
from worker_classes.logger import Logger

URL = "http://0.0.0.0/search"

headers = {
    "accept": "application/json",
    "Content-Type": "application/json",
}

json_data = {
    "email": "baba@yandex.ru",
}

tasks = [
    "89325378245@yandex.ru",
    "some@yandex.ru",
    "filippovaleksej37@gmail.com",
    "hamokovmartin1@gmail.com",
    "kooloo.smurnov@gmail.com",
    "runaysa@gmail.com",
    "some@yandex.ru",
    "presnovsergej51@gmail.com",
    "den4ikmarvel2000@gmail.com",
    "albinaxam1995@gmail.com",
    "koval.irina1234@gmail.com",
    "semen.rxbz@gmail.com",
    "alik.gasparyan1989@gmail.com",
    "lera.golubtsova.2017@gmail.com",
]


def get_random_email() -> str:
    return random.choice(tasks)


def make_emails_spam(count: int, interval: int) -> None:
    for counter in range(count):
        email = get_random_email()
        logging.info(f"Task {counter + 1} of {count}.")
        send_email(email)
        if interval:
            sleep(interval)


def send_email(email: str) -> None:
    json_data["email"] = email
    response = requests.post(URL, headers=headers, json=json_data)
    logging.info(f"Send email: {email}, status code: {response.status_code}")


async def make_emails_spam_async(
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

        for number in range(total_tasks):
            email = get_random_email()
            tasks.append(asyncio.create_task(send_email_async(session, email)))
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
        eml, code, text = response
        msg = f"Email: {eml}, status code: {code}"
        if text:
            msg += f", message: {text}"
        logging.info(msg)


async def send_email_async(session: ClientSession, email: str) -> tuple[str, int, str]:
    json_data["email"] = email
    async with session.post(URL, headers=headers, json=json_data) as response:
        return email, response.status, await response.text()


if __name__ == "__main__":
    Logger().create()
    # make_emails_spam(50, 2)
    asyncio.run(make_emails_spam_async(50, 3))
