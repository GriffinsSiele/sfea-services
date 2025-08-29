import logging

from pydash import get, filter_

from settings import PROXY_LOGIN, PROXY_PASSWORD
from src.call.control_numbers import ControlNumberManager
from src.exceptions.exceptions import UnknownError, NoDataError, LimitError, AccountLocked, AccountBlocked
from src.misc.proxy import ProxyManager
from src.request_params.api.search import SearchParams
from src.request_params.api.user_info import UserInfoParams


class SearchUser:
    def __init__(self, token, with_isphere_proxy=False, custom_proxy={}):
        self.token = token
        self.user_id = token.split(':')[-1]
        self.proxy = ProxyManager.get_proxy(
            PROXY_LOGIN, PROXY_PASSWORD) if with_isphere_proxy else None
        self.proxy = custom_proxy if custom_proxy else self.proxy
        logging.info(f'Proxy: {self.proxy}')

    def search(self, phone_number, _with_check_empty=True):
        params = SearchParams(self.token, phone_number, proxy=self.proxy)
        return self._cast_response(params.request(), _with_check_empty)

    def _cast_response(self, response, _with_check_empty=True):
        try:
            data = response.json()
        except Exception as e:
            logging.error(e)

            if '404 Not Found</title>' in response.text:
                raise LimitError(f'Лимит превышен, сервер не обработал запрос')

            raise UnknownError(f'Ответ сервера не json: [{response.text}]')

        return self._adapter(data, _with_check_empty)

    def _adapter(self, response, _with_check_empty=True):
        if get(response, 'status.reason') == 'Invalid token':
            raise AccountBlocked(
                'Невалидный токен. Возможно, произошла еще одна авторизация из-за которой токен стал неактуальным'
            )

        persons = get(response, 'results.persons', [])

        if get(response, 'status.reason') == 'Ratelimit':
            raise LimitError(f'Лимит превышен: {response["status"]}')

        user_friend = get(response, 'results.data.0', {})
        if user_friend:
            user = {**user_friend['anketa'], 'sn': user_friend['sn']}
            user_id = user_friend['sn']

        else:
            if not persons:
                raise NoDataError('В ответе нет ни одного пользователя')

            persons = filter_(persons, lambda p: p['sn'] != self.user_id)

            if len(persons) == 0:
                if _with_check_empty:
                    return self.check_status_empty()
                else:
                    raise AccountLocked('Заблокирован на 24 часа')

            user = persons[0]
            user_id = user['sn']

        if user_id:
            user_info = self.user_info(user_id)
            return user_info if user_info else user

        return user

    def check_status_empty(self):
        phone_info_control = ControlNumberManager.get_random()
        response = self.search(phone_info_control['phone'],
                               _with_check_empty=False)

        if get(response, 'friendly') == get(phone_info_control, 'name'):
            raise NoDataError('Пользователь не найден, хотя аккаунт живой')

        raise UnknownError('Неизвестная ошибка??')

    def user_info(self, user_id):
        uip = UserInfoParams(self.token, user_id, self.proxy)
        response = uip.request()

        try:
            user_info = get(response.json(), 'results')
        except Exception as e:
            logging.error(e)
            return None

        avatar_url = f'https://ub.icq.net/api/v78/files/avatar/get?targetSn={user_id}&size=1024&r=0'
        user_info['avatar'] = avatar_url if get(user_info,
                                                'avatarId') else None

        return user_info
