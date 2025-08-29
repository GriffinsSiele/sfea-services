import logging

from recaptcha_token.mongo import MongoTokenAPI
from request_logic.proxy import ProxyManager

from settings import MONGO_TOKEN_DB, MONGO_TOKEN_PORT, PROXY_LOGIN, PROXY_PASSWORD, SITE_NAME, COUNT_ACTIONS_UNITL_EXIT
from src.misc.profile import ProfilePicker
from src.selenium.logic import SeleniumLogic
from src.misc.logger import Logger
from src.misc.template import TemplatePicker

Logger().create()

template = TemplatePicker.get(SITE_NAME)

mongoserver = MongoTokenAPI(MONGO_TOKEN_DB, MONGO_TOKEN_PORT)
proxy = ProxyManager({'login': PROXY_LOGIN, 'password': PROXY_PASSWORD}).get_proxy('5')

profile = ProfilePicker.get('1')

sl = SeleniumLogic(template['url'],
                   mongoserver,
                   template['known_tags'],
                   template['scenarios'],
                   template['tokens'],
                   profile=profile,
                   headless=True,
                   proxy=proxy)
sl.prepare()

count = 0
while count < COUNT_ACTIONS_UNITL_EXIT:
    count += 1

    try:
        sl.simulate()
    except Exception as e:
        logging.error(e)
