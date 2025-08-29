from telethon import functions

from src.request_params.interfaces.telegram_base import TelegramBaseParams


class TelegramDeleteParams(TelegramBaseParams):
    def __init__(self, client, entity_ids):
        function_call = functions.contacts.DeleteContactsRequest
        ids = [entity_ids] if not isinstance(entity_ids, list) else entity_ids
        super().__init__(client, function_call, id=ids)
