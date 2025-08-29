import asyncio
import logging
import pathlib
import random
from asyncio import sleep
from typing import Optional

from proxy_manager import ProxyCacheManager
from putils_logic.putils import PUtils
from pydash import get
from requests_logic.base import RequestBaseParamsAsync
from requests_logic.proxy import ProxyManager

from lib.src.logic.device.generator import DeviceGenerator
from lib.src.request_params.api.activate import ActivateParams
from lib.src.request_params.api.profile import ProfileParams
from lib.src.request_params.api.register import RegisterParams
from src.config.countries import Countries
from src.config.settings import PROXY_URL, SMS_SERVICE_TOKEN
from src.logic.sms.smsactive import SMSActivateAPI

_current_file_path = pathlib.Path(__file__).parent.absolute()
proxy_cache_manager = ProxyCacheManager(
    proxy_url=PROXY_URL,
    cache_file=PUtils.bp(_current_file_path, "..", "..", "..", "proxy_cache.json"),
    request_class=RequestBaseParamsAsync,
    rate_update=50,
)

global_cs = {"CURRENT_COUNTRY": 0, "COUNT": 0, "FAILURE_IN_ROW": 0}


class RegisterManager:
    async def register(self) -> Optional[dict]:
        try:
            r = await self._register()
            global_cs["FAILURE_IN_ROW"] = 0
            return r
        except Exception as e:
            logging.error(e)

            global_cs["FAILURE_IN_ROW"] += 1
            if "Аккаунт заблокирован" in str(e) or global_cs["FAILURE_IN_ROW"] % 10 == 0:
                logging.info("Switching country after failure in row or block")
                self.switch_country()
            if "WARNING_LOW_BALANCE" in str(e):
                exit(1)
            if "INTERVAL_CONCURRENT_REQUESTS_ERROR" in str(e):
                await sleep(60 * 10)
        finally:
            global_cs["COUNT"] += 1
            if global_cs["COUNT"] % 50 == 0:
                self.switch_country()
                logging.info("Switching country after 50 calls")

        return None

    def switch_country(self):
        l = len(Countries.ORDER_SELECT)
        global_cs["CURRENT_COUNTRY"] = (global_cs["CURRENT_COUNTRY"] + 1) % l

    async def _register(self):
        country = Countries.ORDER_SELECT[global_cs["CURRENT_COUNTRY"]]
        logging.info(f"Current country: {country}")

        device = DeviceGenerator.generate()

        proxy = await proxy_cache_manager.get_proxy(
            {"proxygroup": "1"},
            fallback_query={"proxygroup": "1"},
            repeat=3,
        )
        logging.info(f"Using proxy: {proxy}")

        sms_api = SMSActivateAPI({"token": SMS_SERVICE_TOKEN})
        phone_info = sms_api.get_truecaller(country_code=country.get("code"))
        tzid = get(phone_info, "tzid")
        phone = get(phone_info, "short_number")

        logging.info(f"OnlineSIM number: {phone}")

        register_params = RegisterParams(
            phone, device=device, proxy=proxy, country=country.get("lower")
        )
        response = await register_params.request()
        response = response.json()
        logging.info(f"Response: {response}")

        request_id = get(response, "requestId")
        method = get(response, "method")
        sleep_time = get(response, "tokenTtl", 0) + 10
        # method = sms | call. Если call, тогда tokenTtl - время до перехода в режим отправки СМС

        if "limit reached" in get(response, "message"):
            logging.error("Banned by IP")
            return

        if sleep_time > 150:
            logging.error(f"Long wait of phone sms: {sleep_time}")
            return

        if method != "sms":
            logging.info(f"Sleep {sleep_time} sec to wait SMS sent")
            await asyncio.sleep(sleep_time)
            register_params = RegisterParams(
                phone,
                device=device,
                sequence_no=2,
                proxy=proxy,
                country=country.get("lower"),
            )
            response = await register_params.request()
            response = response.json()
            logging.info(f"Response: {response}")
            request_id = get(response, "requestId")
            message = get(response, "message", "")

            if "Phone number limit reached" in message:
                logging.error("Ban phone")
                return None

            if not request_id:
                await asyncio.sleep(20)
                # Дополнительно ждем еще 20 сек, чтобы еще раз запросить СМС, т.к. может не отправить с первого раза

                register_params = RegisterParams(
                    phone,
                    device=device,
                    sequence_no=2,
                    proxy=proxy,
                    country=country.get("lower"),
                )
                response = await register_params.request()
                response = response.json()
                logging.info(f"Response: {response}")
                request_id = get(response, "requestId")

        logging.info(f"Register response: {response}")

        sms_code = self.sms_callback(sms_api, tzid)
        activate_params = ActivateParams(
            phone,
            request_id,
            sms_code,
            device=device,
            proxy=proxy,
            country=country.get("lower"),
        )
        response = await activate_params.request()

        try:
            response = response.json()
        except Exception:
            logging.error(f"Parse json ActivateParams error: {response}")
            response = {}

        installation_id = get(response, "installationId")

        if not installation_id:
            raise Exception("Unsuccessful register due to empty installation_id")

        profile_params = ProfileParams(
            phone,
            installation_id=installation_id,
            device=device,
            proxy=proxy,
            country=country.get("upper"),
        )
        await profile_params.request()

        return {
            "token": installation_id,
            "phone": str(phone),
            "device": device.to_dict(),
        }

    def sms_callback(self, sms_api: SMSActivateAPI, tzid) -> str:
        logging.info(f"Wait for SMS from sms service, {tzid}")
        try:
            sms_code = sms_api.get_sms(tzid)
        except Exception as e:
            logging.error(f"TimeoutException: {e}")
            # Временное решение, когда smsactivate отдает status_cancel на все ответы 29.05.2024
            # params = {
            #     "api_key": SMS_SERVICE_TOKEN,
            #     "action": "getListOfActiveActivationsForDesktop",
            #     "order": "createDate",
            #     "orderBy": "desc",
            #     "searchBy": "",
            #     "noStat": "0",
            #     "start": "0",
            #     "length": "10",
            # }
            #
            # for i in range(20):
            #     try:
            #         r = requests.get(
            #             "https://api.sms-activate.org/stubs/handler_api.php",
            #             params=params,
            #         )
            #         logging.info(f"Current status: {r.text}")
            #         data = r.json()
            #         for r in data["array"]:
            #             if r["id"] == str(tzid) and r["code"]:
            #                 logging.info(f"detected SMS code: {r['code']}")
            #
            #                 return r["code"]
            #     except Exception as e:
            #         logging.error(f"Extra request error:  {e}")
            #     time.sleep(5)

            logging.info(f"Using random SMS code")
            sms_code = "".join(
                [random.choice([str(i) for i in range(10)]) for _ in range(6)]
            )
        logging.info(f"SMS code: {sms_code}")
        return sms_code
