# Run this script to set domain address for callback on RuCaptcha.
# .env file must contain 'CALLBACK_URL' and 'RUCAPTCHA_API_KEY' variables.
#
# Packages requirements:
# -- python-dotenv
# -- aiohttp


import asyncio
import os
import sys
from json import loads
from typing import Any
from urllib.parse import urlparse

from aiohttp import ClientSession, client_exceptions
from dotenv import load_dotenv

load_dotenv(override=True)


def validate_env_args() -> tuple[str, str]:
    addr = os.getenv("CALLBACK_URL")
    if not addr:
        print(".env variable CALLBACK_URL is not set or empty")
        sys.exit(3)
    api_key = os.getenv("RUCAPTCHA_API_KEY")
    if not api_key:
        print(".env variable RUCAPTCHA_API_KEY is not set or empty")
        sys.exit(3)
    return urlparse(addr).netloc, api_key


def validate_response(response: Any):
    response_data = response.get("request")
    if not response_data.startswith("ERROR"):
        return response_data
    if response_data == "ERROR_IP_ADDRES":
        print(
            "ERROR: Request must be sent from the same IP address to which you want to receive callback."
        )
        sys.exit(3)
    print(response.get("error_text"))
    sys.exit(3)


async def get(params: dict[str, Any]):
    try:
        async with ClientSession() as session:
            async with session.get(
                url="https://api.rucaptcha.com/res.php",
                params=params,
                verify_ssl=False,
                timeout=10,
            ) as response:
                payload = await response.read()
                return loads(payload.decode())
    except client_exceptions.ClientError:
        print("Connection failed.")
        sys.exit(3)


async def set_domain(args: tuple[str, str]) -> dict[str, Any]:
    params = {
        "action": "add_pingback",
        "addr": args[0],
        "key": args[-1],
        "json": 1,
    }
    return await get(params=params)


async def main():
    args = validate_env_args()
    response = await set_domain(args)
    response_data = validate_response(response=response)
    print(response_data)


if __name__ == "__main__":
    asyncio.run(main())
