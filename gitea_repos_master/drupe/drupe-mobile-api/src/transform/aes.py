from Crypto.Cipher import AES

from src.transform.hex import HexConverter


class AESCrypt:
    MODE = AES.MODE_CBC
    BLOCK_SIZE = 16

    def __init__(self, key, iv):
        """
        AES class
        :param key: AES key in hex
        :param iv: AES iv in hex
        :return: binary data
        """
        self.key = HexConverter.unhex(key)
        self.iv = HexConverter.unhex(iv)
        self.cipher = AES.new(self.key, mode=AESCrypt.MODE, iv=self.iv)

    def encrypt(self, message):
        """
        Encrypt AES
        :param message: binary string
        :return: binary encrypted data
        """
        return self.cipher.encrypt(self._pad_data(message))

    def decrypt(self, message):
        """
        Decrypt AES
        :param message: binary string
        :return: binary decrypted data
        """
        return self.cipher.decrypt(message)

    def _pad_data(self, s):
        char = AESCrypt.BLOCK_SIZE - len(s) % AESCrypt.BLOCK_SIZE
        return s + char * bytes(chr(char), 'utf8')
