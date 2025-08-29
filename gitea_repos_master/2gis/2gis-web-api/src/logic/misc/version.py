import logging
from datetime import datetime

from src.config.app import ConfigApp


class Version2GISManager:
    @staticmethod
    def parse_from_auth(payload):
        d = datetime.fromisoformat(payload.get("commitIsoDate"))
        return d.strftime("%Y-%m-%d-%H")

    @staticmethod
    def update_by_payload(payload):
        new_version = Version2GISManager.parse_from_auth(payload)
        if new_version == ConfigApp.APP_VERSION:
            return False

        logging.info(f"Detected new version of APP: {new_version}")
        ConfigApp.APP_VERSION = new_version
        return True
