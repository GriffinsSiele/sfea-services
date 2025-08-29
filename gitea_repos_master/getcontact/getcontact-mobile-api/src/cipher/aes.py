import codecs
import hashlib
import hmac
from Crypto.Cipher import AES

from src.cipher.base64 import Base64
from src.config.app import ConfigApp


class CipherAES:
    def __init__(self, aes_key):
        self.aes_key = aes_key
        self.BS = 16

        aes_key_hex = codecs.decode(self.aes_key, "hex")
        self.cipher_aes = AES.new(aes_key_hex, AES.MODE_ECB)

    def decrypt_AES(self, data):
        return self.unpad_data(self.cipher_aes.decrypt(data).decode())

    def encrypt_AES(self, data):
        return self.cipher_aes.encrypt(self.pad_data(data))

    def encrypt_AES_b64(self, data):
        return Base64.encode(self.encrypt_AES(data)).decode()

    def decrypt_AES_b64(self, data):
        return self.decrypt_AES(Base64.decode(data))

    def pad_data(self, s):
        v = self.BS - len(s) % self.BS
        return bytes(s + (v) * chr(v), "utf8")

    def unpad_data(self, s):
        return s[0:-ord(s[-1])]

    def create_signature(self, payload, timestamp):
        message = bytes(self.format_message_to_hmac(payload, timestamp), "utf8")
        secret = bytes(ConfigApp.HMAC_KEY, "utf8")
        signature = Base64.encode(hmac.new(secret, msg=message, digestmod=hashlib.sha256).digest())
        return signature.decode()

    def format_message_to_hmac(self, msg, timestamp):
        return f"{timestamp}-{msg}"
