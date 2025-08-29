import logging

from queue_logic.keydb import KeyDBQueue
from request_logic.exceptions import ProxyLocked, UnknownError, SeleniumBrokenProcess, NoDataError, InCorrectData, \
    ProxyBlocked
from settings import KEYDB_HOST, KEYDB_PASSWORD
from src.logic.session_manager import SessionManager
from src.misc.proxy_manager import ProxyConfigManager
from src.misc.logger import Logger

Logger().create()
kdbq = KeyDBQueue(host=KEYDB_HOST, password=KEYDB_PASSWORD, service='autoins_test')

proxy = ProxyConfigManager.update_proxy()
sm = SessionManager(headless=True, proxy=proxy)

while True:
    sm.prepare_selenium()

    input_data = kdbq.check_queue()
    logging.info(f"LPOP {input_data}")
    if input_data:
        try:
            response = sm.search(input_data)
            logging.info(f'Response: {str(response)[:200]}...')
            kdbq.set_answer(input_data, {'status': 'ok', 'code': 200, 'message': 'ok', 'records': response})
            sm.generate_token_on_start()
        except (ProxyLocked, SeleniumBrokenProcess, UnknownError) as e:
            logging.error(e)
            logging.info(f'LPUSH {input_data}')
            kdbq.return_to_queue(input_data)
        except NoDataError:
            logging.info('Nothing found')
            kdbq.set_answer(input_data, {'status': 'ok', 'code': 204, 'message': 'ok', 'records': []})
            sm.generate_token_on_start()
        except InCorrectData as e:
            logging.error(e)
            kdbq.set_answer(input_data, {'status': 'Error', 'code': 500, 'message': e, 'records': []})
        except ProxyBlocked as e:
            kdbq.return_to_queue(input_data)
            logging.error(f'Shutdown due to error: {e}')
            exit(1)

    else:
        sm.simulate()
