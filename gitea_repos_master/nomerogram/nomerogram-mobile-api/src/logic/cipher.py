import hashlib

from src.config.app import APP_SALT


class SecretCipher:
    @staticmethod
    def get_secret(payload):
        result = ''
        for key in sorted(payload.keys()):
            result += str(payload[key])
        result += APP_SALT
        return hashlib.sha256(result.encode()).hexdigest()
