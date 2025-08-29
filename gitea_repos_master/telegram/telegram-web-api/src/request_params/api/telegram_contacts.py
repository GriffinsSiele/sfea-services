from telethon import functions

from src.request_params.interfaces.telegram_base import TelegramBaseParams


class TelegramContactsParams(TelegramBaseParams):
    def __init__(self, client):
        function_call = functions.contacts.GetContactsRequest
        super().__init__(client, function_call, hash=0)
