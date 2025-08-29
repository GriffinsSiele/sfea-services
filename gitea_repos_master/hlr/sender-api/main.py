from pprint import pprint
from queue_logic.keydb import KeyDBQueue
import time
import os
import logging
from settings import KEYDB_HOST, KEYDB_PASSWORD, APPLICATION, REDSMS_LOGIN, REDSMS_API_KEY, SMSPILOT_API_KEY, SMSPILOT_CALLBACK
from src.bdpn import BDPN
from src.providers.smspilot import SMSPilot
from src.providers.redsms import RedSMS
from pydash import get

logging.basicConfig(
    format="%(asctime)s - [%(levelname)s] - (%(filename)s).%(funcName)s(%(lineno)d) - %(message)s",
    datefmt="%d-%m-%Y %H:%M:%S.%03d",
    level=logging.INFO,
)
smspilot = SMSPilot(callback_address=SMSPILOT_CALLBACK, api_key=SMSPILOT_API_KEY)
redsms = RedSMS(login=REDSMS_LOGIN, api_key=REDSMS_API_KEY)

if __name__ == '__main__':
    keydb_queue = KeyDBQueue(host=KEYDB_HOST, password=KEYDB_PASSWORD, service=APPLICATION)
    logging.info('app started')
    while True:
        try:
            phone = keydb_queue.check_queue()
        except:
            continue
        if phone:
            try:
                operator = BDPN.get_operator(phone)
                provider = smspilot if operator == 'ВымпелКом' else redsms
                result = provider.send_request(phone)
                logging.info(f'{phone} sended to {provider} provider')
                if result:
                    keydb_queue.set_answer(phone, result)
                    logging.error(f'{phone} got error answer from hlr provider {get(result,"message")}')
            except Exception as e:
                logging.error(e)
                keydb_queue.return_to_queue(phone)
        time.sleep(.1)
