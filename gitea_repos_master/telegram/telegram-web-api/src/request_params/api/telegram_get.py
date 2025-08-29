from telethon import functions

from src.request_params.interfaces.telegram_base import TelegramBaseParams


class TelegramGetParams(TelegramBaseParams):
    def __init__(self, client, entity_id):
        super().__init__(client, functions.users.GetFullUserRequest, entity_id)
