import json
import logging

from pydash import get, map_

from settings import PROXY_LOGIN, PROXY_PASSWORD
from src.exceptions.exceptions import AccountLocked, UnknownError, NoDataError, CaptchaDetected
from src.misc.captcha import CaptchaDecode
from src.misc.proxy import ProxyManager
from src.request_params.api.captcha import CaptchaParams
from src.request_params.api.search import SearchParams
from src.request_params.api.tags import TagsParams


class Search:
    def __init__(self, device_id, token, aes_key, with_proxy=False):
        self.device_id = device_id
        self.token = token
        self.aes_key = aes_key

        self.proxy = ProxyManager.get_proxy(PROXY_LOGIN, PROXY_PASSWORD) if with_proxy else None

        self.call_methods = {'phone': self.search_phone_number, 'tags': self.search_tags}
        self.is_solved_captcha = None

    def search_phone_number(self, phone_number):
        self.phone_number = phone_number
        self.call_method = 'phone'

        sp = SearchParams(self.phone_number, self.device_id, self.token, self.aes_key, self.proxy)
        response = sp.request()

        return self._validate_response(response, self._parse_fields_phone)

    def search_tags(self, phone_number):
        self.phone_number = phone_number
        self.call_method = 'tags'

        tp = TagsParams(self.phone_number, self.device_id, self.token, self.aes_key, self.proxy)
        response = tp.request()

        return self._validate_response(response, self._parse_fields_tags)

    def _validate_response(self, response, parse_fields=None):
        parse_fields = parse_fields if parse_fields else self._parse_fields_phone

        if isinstance(response, str):
            response = json.loads(response)

        try:
            response = self._parse_response_status(response)
            return parse_fields(response)
        except CaptchaDetected:
            logging.info('Captcha is detected')

            if isinstance(self.is_solved_captcha, bool):
                raise CaptchaDetected('Повторный запуск капчи')

            self.is_solved_captcha = self._captcha_decode(response)
            if not self.is_solved_captcha:
                raise CaptchaDetected('Превышен лимит попыток решения капчи')

            return self.call_methods[self.call_method](self.phone_number)

    def _parse_response_status(self, response):
        error_code = get(response, 'meta.errorCode')
        error_message = get(response, 'meta.errorMessage', '')

        if error_code == '403001' and 'Authentication failed' in error_message:
            raise UnknownError('Неверные параметры в запросе. Вероятно неверный токен')

        if error_code == '403003' and 'Authentication failed' in error_message:
            raise AccountLocked('Неверные параметры в запросе. Запрос заблокирован сервером')

        if error_code == '403025' and 'Authentication failed' in error_message:
            raise UnknownError('Неверные параметры в запросе. Вероятно неверный AES ключ')

        if (error_code in ['404009', '404010', '403010']) and 'No result found' in error_message:
            raise NoDataError('Пользователь не найден')

        if error_code == '403004' and 'User validation' in error_message:
            raise CaptchaDetected('Обнаружена капча')

        return response

    def _captcha_decode(self, response):
        for i in range(3):
            validation_code = CaptchaDecode.decode_response(response)
            cp = CaptchaParams(validation_code, self.device_id, self.token, self.aes_key, self.proxy)
            resp = cp.request()

            error_code = get(json.loads(resp), 'meta.errorCode')
            if error_code == '403004':
                continue
            else:
                logging.info(f'Captcha solved with {i + 1} try')
                return True
        return False

    def _parse_fields_phone(self, response):
        name = get(response, 'result.profile.name', '')
        surname = get(response, 'result.profile.surname', '')

        user_name = None if not name and not surname else f"{name} {surname}"
        country_code = get(response, 'result.profile.countryCode')
        country = get(response, 'result.profile.country')
        country_name = f'{country if country else ""} {country_code if country_code else ""}'

        return {
            "user_name": user_name,
            "name": name,
            "surname": surname,
            "phoneNumber": self.phone_number,
            "country": country_name,
            "tagCount": get(response, 'result.profile.tagCount'),
            "comments": map_(get(response, 'result.comments.comments', []), 'body'),
            "displayName": get(response, 'result.profile.displayName'),
            "profileImage": get(response, 'result.profile.profileImage'),
            "email": get(response, 'result.profile.email'),
            "is_spam": get(response, 'result.spamInfo.degree') == "high",
            'remain_count': get(response, 'result.subscriptionInfo.usage.search.remainingCount')
        }

    def _parse_fields_tags(self, response):
        return response
