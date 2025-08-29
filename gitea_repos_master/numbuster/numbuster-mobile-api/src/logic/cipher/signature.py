import hashlib

from src.config.app import ConfigApp


class SignatureManager:
    @staticmethod
    def sign(payload):
        string = payload + ConfigApp.SIGNATURE_SALT
        return hashlib.sha256(string.encode("utf-8")).hexdigest()
