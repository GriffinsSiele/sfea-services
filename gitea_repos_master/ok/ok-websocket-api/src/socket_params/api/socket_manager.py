import logging

from isphere_exceptions.proxy import ProxyBlocked
from isphere_exceptions.session import SessionCaptchaDetected
from pydash import get

from src.config.app import ConfigApp
from src.logic.adapters.message import MessageAdapter
from src.logic.adapters.response import ResponseAdapter
from src.logic.ok.captcha import CaptchaSolver
from src.socket_params.interfaces.socket import Socket
from src.socket_params.interfaces.socket_messages import MESSAGE, SocketMessages


class SocketManager:
    def __init__(self, chat_link=None, chat_id=None, proxy=None):
        self.chat_link = chat_link
        self.chat_id = chat_id
        self.proxy = proxy

        try:
            self.socket = Socket(proxy=self.proxy)
        except ConnectionRefusedError as e:
            raise ProxyBlocked(f"Blocked proxy in socket: {e}")

    def init_chat_filling(self):
        # Отправили токен ссылку чата для подключения
        payload = SocketMessages.payload(MESSAGE.TOKEN, self.chat_link)
        response = self.socket.send(payload)
        token = get(response, "payload.token")
        logging.info(f"Step 1. Handshake: {token}")

        # Отправили юзер агента аналогичные в запросе получения ссылки
        payload = SocketMessages.payload(MESSAGE.USER_AGENT)
        self.socket.send(payload)
        logging.info("Step 2. User agent")

        payload = SocketMessages.payload(MESSAGE.SETTINGS, token)
        response = self.socket.send(payload)
        group_id = get(response, "payload.chats.0.groupChatInfo.groupId")
        logging.info(f"Step 3. Getting current chat group_id: {group_id}")

        # Отправили еще раз юзер агента и токен с 1ого запроса
        payload = SocketMessages.payload(MESSAGE.USER_CONTACTS, group_id, "GROUP")
        self.socket.send(payload)
        logging.info("Step 4. User agent and group_id connect")

        # Получили список чатов - должен быть 1 с поддержкой
        payload = SocketMessages.payload(MESSAGE.FETCH_CHATS, self.chat_id)
        self.socket.send(payload)
        logging.info("Step 5. Getting chat list")

        # Получили сообщения чата
        payload = SocketMessages.payload(MESSAGE.FETCH_CHATS_MESSAGES, self.chat_id)
        r = self.socket.send(payload)
        options = MessageAdapter.options_for_attaches(r)
        logging.info("Step 6. Getting chat messages of chat")

        # Выбрали кнопку удаления профиля
        self.send_message(
            ResponseAdapter.get_similar_option(options, "Хочу удалить свою страницу")
        )
        r = self.socket.receive()
        logging.info(f'Step 7. Click on "Delete page" option - {r}')

        # Получили сообщения чата
        payload = SocketMessages.payload(MESSAGE.FETCH_CHATS_MESSAGES, self.chat_id)
        r = self.socket.send(payload)
        options = MessageAdapter.options_for_attaches(r)
        logging.info("Step 8. Getting chat messages of chat")

        # Выбрали кнопку восстановления доступа
        self.send_message(
            ResponseAdapter.get_similar_option(options, "Восстановить профиль")
        )
        r = self.socket.receive()
        logging.info(f'Step 9. Click on "Recover profile" option - {r}')

    def start_search(self, phone_number):
        # Выбрали кнопку восстановления доступа
        self.send_message(phone_number)
        r = self.socket.receive()
        logging.info(f'Step 10. Sending payload "{phone_number}" - {r}')

        return self.solve_captcha(r)

    def send_message(self, message):
        payload = SocketMessages.payload(MESSAGE.ACTION_TYPE, self.chat_id)
        self.socket.send(payload)

        payload = SocketMessages.payload(MESSAGE.ACTION_CHAT_TYPING, self.chat_id)
        self.socket.send(payload)

        payload = SocketMessages.payload(
            MESSAGE.PICK_ACTION_BUTTON, self.chat_id, message
        )
        return self.socket.send(payload)

    def next_search(self):
        self.send_message("Сомневаюсь")
        r = self.socket.receive()
        logging.info(f"Step 10+. Extending guessing with new profiles - {r}")
        return r

    def close(self):
        try:
            self.socket.close()
        except Exception as e:
            logging.warning(e)

    def solve_captcha(self, response):
        task_id = None
        for _ in range(ConfigApp.COUNT_SOLVE_CAPTCHA):
            if not MessageAdapter.has_captcha(response):
                CaptchaSolver.report(task_id, True)
                return response

            CaptchaSolver.report(task_id, False)
            captcha_url = MessageAdapter.captcha_url(response)
            logging.info(f"Captcha detected: {captcha_url}")
            solution, task_id = CaptchaSolver.solve(captcha_url)

            self.send_message(solution)
            response = self.socket.receive()

        raise SessionCaptchaDetected(
            f"Не решена капча с {ConfigApp.COUNT_SOLVE_CAPTCHA} попыток"
        )
