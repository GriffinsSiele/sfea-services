from src.request_params.interfaces.telegram_base import TelegramBaseParams


class TelegramPhotoParams(TelegramBaseParams):
    def __init__(self, client, entity_id):
        super().__init__(client, "get_profile_photos", entity_id)
