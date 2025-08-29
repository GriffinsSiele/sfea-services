from src.request_params.interfaces.telegram_base import TelegramBaseParams


class TelegramDownloadParams(TelegramBaseParams):
    def __init__(self, client, photo_id):
        super().__init__(client, "download_media", photo_id, thumb=2)
