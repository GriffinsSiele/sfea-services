import random
import string
from urllib.parse import urlparse

random.seed()

ascii_and_digits = string.ascii_uppercase + string.digits


def random_string(length: int) -> str:
    return "".join(random.choices(ascii_and_digits, k=length))


def get_domain_url(url: str) -> str:
    parsed_url = urlparse(url)
    return f"{parsed_url.scheme}://{parsed_url.netloc}"
