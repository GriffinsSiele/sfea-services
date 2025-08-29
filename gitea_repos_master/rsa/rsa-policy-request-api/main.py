from pprint import pprint

from recaptcha_token.mongo import MongoTokenAPI
from request_logic.proxy import ProxyManager

from settings import PROXY_LOGIN, PROXY_PASSWORD, MONGO_TOKEN_DB, MONGO_TOKEN_PORT
from src.search.search import SearchAPI

sitekey, action = '6LcWXc8gAAAAAMpgB0-7TzTELlr8f7T2XiTrexO5', 'submit'
mongo = MongoTokenAPI(MONGO_TOKEN_DB, MONGO_TOKEN_PORT)
proxy = ProxyManager({'login': PROXY_LOGIN, 'password': PROXY_PASSWORD}).get_proxy('5')

payload = {'type': 'gosNumber', 'value': 'К731КВ32'}

pprint(SearchAPI(mongo, sitekey, action, proxy).search(payload))
