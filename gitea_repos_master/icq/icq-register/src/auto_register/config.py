from settings import SELENIUM_BROWSER_DRIVER_PATH


class AutoRegisterConfig:
    SELENIUM_DRIVER_PATH = SELENIUM_BROWSER_DRIVER_PATH

    DUMP_PATH_TEMPLATE = 'data/register_{}.yml'
    AVERAGE_TIMEOUT_MULTIPLIER = 5
    USER_AGENT_TEMPLATE = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/{} (KHTML, like Gecko) Safari/{}'