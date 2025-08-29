import logging
from time import sleep

from queue_logic.keydb import KeyDBQueue
from recaptcha_token.mongo import MongoTokenAPI
from request_logic.exceptions import ErrorReturnToQueue, NoDataError, InCorrectData, ErrorNoReturnToQueue, ProxyBlocked
from request_logic.proxy import ProxyManager

from settings import KEYDB_HOST, KEYDB_PASSWORD, PROXY_LOGIN, PROXY_PASSWORD, MONGO_TOKEN_DB, MONGO_TOKEN_PORT
from src.misc.logger import Logger
from src.search.mongo import get_count_v3_safe
from src.search.search import SearchAPI

sitekey, action = '6LcWXc8gAAAAAMpgB0-7TzTELlr8f7T2XiTrexO5', 'submit'

Logger().create()
kdbq = KeyDBQueue(host=KEYDB_HOST, password=KEYDB_PASSWORD, service='autoins_test')
mongo = MongoTokenAPI(MONGO_TOKEN_DB, MONGO_TOKEN_PORT)
proxy = ProxyManager({'login': PROXY_LOGIN, 'password': PROXY_PASSWORD}).get_proxy('5')

search_api = SearchAPI(mongo, sitekey, action, proxy)

while True:
    if not get_count_v3_safe(mongo, sitekey, action):
        sleep(2)
        continue

    input_data = kdbq.check_queue()
    logging.info(f"LPOP {input_data}")

    if input_data:
        try:
            response = search_api.search_json(input_data)
            logging.info(f'Response: {str(response)[:200]}...')
            kdbq.set_answer(input_data, {'status': 'ok', 'code': 200, 'message': 'ok', 'records': response})
        except NoDataError:
            logging.info('Response: nothing found')
            kdbq.set_answer(input_data, {'status': 'ok', 'code': 204, 'message': 'ok', 'records': []})
        except InCorrectData as e:
            logging.error(e)
            kdbq.set_answer(input_data, {'status': 'Error', 'code': 500, 'message': e, 'records': []})
        except ProxyBlocked as e:
            logging.error(e)
            kdbq.return_to_queue(input_data)
            exit(1)
        except ErrorReturnToQueue as e:
            logging.error(e)
            logging.info(f'LPUSH {input_data}')
            kdbq.return_to_queue(input_data)
        except ErrorNoReturnToQueue as e:
            logging.error(e)
    else:
        sleep(1)
