import logging

import requests


class TelegramMonitoring:
    def __init__(self, token, chat_id):
        self.token = token
        self.chat_id = chat_id

    def send(self, message):
        logging.info(f"Sending message: {message}")

        if not self.token:
            logging.info("No telegram token. Skip sending message.")
            return

        query = {"chat_id": self.chat_id, "text": message}
        url = f"https://api.telegram.org/bot{self.token}/sendMessage"

        response = requests.get(url, params=query)
        logging.info(f"Response telegram: {response}. {response.text}")
