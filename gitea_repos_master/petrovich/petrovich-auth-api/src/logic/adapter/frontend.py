import re
from urllib import parse

from isphere_exceptions.source import SourceParseError


class FrontendAdapter:

    @staticmethod
    def parse(response):
        urls = re.findall('src="(.*?nonce.*?)">', response.text)
        if not urls:
            raise SourceParseError("Источник не отдал ссылку для генерации сессии")

        url = urls[0]

        cookies = dict(response.cookies)
        params = parse.parse_qs(parse.urlsplit(url).query)

        return cookies, params
