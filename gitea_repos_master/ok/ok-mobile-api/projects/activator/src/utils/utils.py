import html
from urllib.parse import parse_qs, urlparse


def extract_query(url_raw):
    url = html.unescape(url_raw)
    parsed_url = urlparse(url)
    return parse_qs(parsed_url.query)
