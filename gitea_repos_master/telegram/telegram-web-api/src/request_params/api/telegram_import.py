from telethon import functions
from telethon.tl.types import InputPhoneContact

from src.logic.generator.username import UsernameGenerator
from src.request_params.interfaces.telegram_base import TelegramBaseParams


class TelegramImportParams(TelegramBaseParams):
    def __init__(self, client, phone_number, first_name=None, last_name=None):
        first_name, last_name = (
            UsernameGenerator().generate_by_phone(phone_number)
            if not first_name or not last_name
            else (first_name, last_name)
        )
        contact = InputPhoneContact(
            client_id=0, phone=phone_number, first_name=first_name, last_name=last_name
        )
        function_call = functions.contacts.ImportContactsRequest

        super().__init__(client, function_call, [contact])
