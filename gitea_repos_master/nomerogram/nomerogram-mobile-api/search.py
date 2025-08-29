from pprint import pprint

from src.logic.call import SearchAPI
from src.logic.keydb import KeyDBAdapter
from src.misc.logger import Logger

Logger().create()

device_id = '12615c25210d818ec1ec9dc7495f520b'
carplate = 'к030ра97'
cookies = {'ring': '75033fb%2B3tyg75fr9uXxNsuV1fovA0a5'}

api = SearchAPI(device_id, cookies, with_random_proxy=True)
response = api.search(carplate)
pprint(KeyDBAdapter.toKeyDB(response))
