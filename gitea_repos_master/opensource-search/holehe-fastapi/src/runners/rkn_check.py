import asyncio
import re
from time import sleep

import requests

from src.proxy import proxy_cache_manager


async def is_banned_rf(site):
    cookies = {}

    headers = {
        "accept": "application/vnd.api+json",
        "accept-language": "ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7",
        "content-type": "application/vnd.api+json",
        "dnt": "1",
        "origin": "https://pr-cy.ru",
        "priority": "u=1, i",
        "referer": "https://pr-cy.ru/",
        "sec-ch-ua": '"Chromium";v="128", "Not;A=Brand";v="24", "Google Chrome";v="128"',
        "sec-ch-ua-mobile": "?0",
        "sec-ch-ua-platform": '"Linux"',
        "sec-fetch-dest": "empty",
        "sec-fetch-mode": "cors",
        "sec-fetch-site": "same-site",
        "user-agent": "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36",
        "x-csr": "1",
    }

    data = (
        '{"data":{"type":"toolTasks","attributes":{"toolName":"roskomnadzor","params":{"domain":"'
        + site
        + '"}}}}'
    )

    response = requests.post(
        "https://apis.pr-cy.ru/api/v2.1.0/tool-tasks/",
        cookies=cookies,
        headers=headers,
        data=data,
        proxies=await proxy_cache_manager.get_proxy(),
    )

    task_id = response.json().get("data", {}).get("id")

    sleep(3)

    cookies = {}

    headers = {
        "accept": "application/vnd.api+json",
        "accept-language": "ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7",
        "content-type": "application/vnd.api+json",
        "dnt": "1",
        "origin": "https://pr-cy.ru",
        "priority": "u=1, i",
        "referer": "https://pr-cy.ru/",
        "sec-ch-ua": '"Chromium";v="128", "Not;A=Brand";v="24", "Google Chrome";v="128"',
        "sec-ch-ua-mobile": "?0",
        "sec-ch-ua-platform": '"Linux"',
        "sec-fetch-dest": "empty",
        "sec-fetch-mode": "cors",
        "sec-fetch-site": "same-site",
        "user-agent": "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36",
        "x-csr": "1",
    }

    response = requests.get(
        f"https://apis.pr-cy.ru/api/v2.1.0/tool-tasks/{task_id}?filter[since]=0&include=tests",
        cookies=cookies,
        headers=headers,
    )
    print(response.json())

    return '"roskomnadzorDomainForbidden":true' in response.text


async def main():
    data = requests.get(
        "https://raw.githubusercontent.com/megadose/holehe/master/README.md"
    )

    output = {}
    for row in data.text.split("\n"):
        if row.count("|") < 5 or "--------" in row or "Frequent Rate" in row:
            continue

        elements = row.split("|")
        name = elements[1].strip()

        site = elements[2].strip()

        try:
            is_banned = (await is_banned_rf(site)) if name[0] > "d" else False
        except Exception as e:
            is_banned = False
        output[name] = {
            "func": name,
            "active": True,
            "description": elements[2].strip(),
            "method": elements[3].strip(),
        }
        if "✔" in row:
            output[name]["client"] = {
                "request_class": "RequestBaseParamsAsync",
                "use_proxy": True,
            }
        if is_banned:
            output[name]["client"] = {
                "request_class": "RequestBaseParamsAsync",
                "use_proxy": True,
                "proxy_group": "2",
                "proxy_group_fallback": "2",
            }

        print(name, output[name])

    modified_string = re.sub(r"'(func)':\s*'([^']*)'", r'"\1": \2', str(output))
    print(modified_string)


asyncio.run(main())
