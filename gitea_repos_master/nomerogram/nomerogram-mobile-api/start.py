import logging
from time import sleep

from queue_logic.keydb import KeyDBQueue
from request_logic.exceptions import NoDataError, InCorrectData, ErrorReturnToQueue

from settings import KEYDB_HOST, KEYDB_PASSWORD, MONGO_PORT, MONGO_SERVER, MONGO_DB, MONGO_COLLECTION
from src.logic.call import SearchAPI
from src.logic.keydb import KeyDBAdapter
from src.misc.logger import Logger
from src.misc.mongo import MongoSessions

Logger().create()

kdbq = KeyDBQueue(host=KEYDB_HOST, password=KEYDB_PASSWORD, service='nomerogram_test')
mongo = MongoSessions(MONGO_SERVER, MONGO_PORT, 'nomerogram', MONGO_DB, MONGO_COLLECTION)

while mongo.count_active() > 0:
    input_data = kdbq.check_queue()

    if not input_data:
        sleep(0.1)
        continue

    logging.info(f"LPOP {input_data}")
    session = mongo.get_session()
    logging.info(f'Using session: {session}')
    try:
        api = SearchAPI(session['TOKEN'], session['session'], with_random_proxy=True)
        response = api.search(input_data)
        logging.info(f'Response: {str(response)[:200]}')
        response = KeyDBAdapter.toKeyDB(response)
        kdbq.set_answer(input_data, {'status': 'ok', 'code': 200, 'message': 'ok', 'records': response})
        mongo.session_success(session)
    except NoDataError:
        logging.info('Nothing found')
        kdbq.set_answer(input_data, {'status': 'ok', 'code': 204, 'message': 'ok', 'records': []})
    except InCorrectData as e:
        logging.error(e)
        kdbq.set_answer(input_data, {'status': 'Error', 'code': 500, 'message': str(e), 'records': []})
    except ErrorReturnToQueue as e:
        logging.error(e)
        kdbq.return_to_queue(input_data)
