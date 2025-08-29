from src.utils.env import parse_dotenv

_PROXY_LOGIN, _PROXY_PASSWORD = parse_dotenv(
)

PROXY_LOGIN = _PROXY_LOGIN
PROXY_PASSWORD = _PROXY_PASSWORD
