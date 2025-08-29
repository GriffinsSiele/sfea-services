from os import getenv

temp = getenv("IMPLICITLY_WAIT")
IMPLICITLY_WAIT = int(temp) if temp else 3

temp = getenv("EXPLICIT_WAIT")
EXPLICIT_WAIT = int(temp) if temp else 3

temp = getenv("EXPLICIT_WAIT_FOR_LINK")
EXPLICIT_WAIT_FOR_LINK = int(temp) if temp else 3

temp = getenv("MAX_PAGE_LOAD_TIMEOUT")
MAX_PAGE_LOAD_TIMEOUT = int(temp) if temp else 20

temp = getenv("BROWSER_VERSION")
BROWSER_VERSION = int(temp) if temp else None
