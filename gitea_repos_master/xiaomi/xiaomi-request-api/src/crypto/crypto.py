import base64

from Crypto.Cipher import AES, PKCS1_v1_5
from Crypto.PublicKey import RSA
from Crypto.Util.Padding import pad, unpad

from src.crypto.exceptions import CryptoAESException, CryptoRSAException


class CryptoAES:
    """Шифрует и расшифровывает данные, используя алгоритм AES."""

    iv = "0102030405060708".encode("utf-8")

    def encrypt(self, key: str, text: str) -> str:
        """Шифрует переданный текст, используя предоставленный ключ AES.

        :param key: Ключ шифрования AES.
        :param text: Текст, который необходимо зашифровать.
        :return: Зашифрованный текст.
        """
        try:
            cipher = AES.new(key.encode("utf-8"), AES.MODE_CBC, self.iv)
            padded_data = pad(text.encode("utf-8"), AES.block_size, style="pkcs7")
            ciphertext = cipher.encrypt(padded_data)
            return base64.b64encode(ciphertext).decode("utf-8")
        except Exception as e:
            raise CryptoAESException(e)

    def decrypt(self, key: str, ciphertext: str) -> str:
        """Расшифровывает переданный зашифрованный текст, используя предоставленный ключ AES.

        :param key: Ключ шифрования AES.
        :param ciphertext: Зашифрованный текст в кодировке Base64, который необходимо расшифровать.
        :return: Расшифрованный текст.
        """
        try:
            cipher = AES.new(key.encode("utf-8"), AES.MODE_CBC, self.iv)
            ciphertext_bytes = base64.b64decode(ciphertext.encode("utf-8"))
            plaintext_bytes = cipher.decrypt(ciphertext_bytes)
            plaintext = unpad(plaintext_bytes, AES.block_size, style="pkcs7").decode(
                "utf-8"
            )
            return plaintext
        except Exception as e:
            raise CryptoAESException(e)


class CryptoRSA:
    """Шифрует и расшифровывает данные, используя алгоритм RSA."""

    @staticmethod
    def encrypt(public_key: str, text: str) -> str:
        """Шифрует переданный текст, используя предоставленный публичный ключ RSA.

        :param public_key: Публичный ключ RSA в формате PEM.
        :param text: Текст, который необходимо зашифровать.
        :return: Зашифрованный текст.
        """
        try:
            pub_key = RSA.import_key(public_key)
            cipher = PKCS1_v1_5.new(pub_key)
            ciphertext = cipher.encrypt(base64.b64encode(text.encode("utf-8")))
            return base64.b64encode(ciphertext).decode("utf-8")
        except Exception as e:
            raise CryptoRSAException(e)

    @staticmethod
    def decrypt(private_key: str, ciphertext: str) -> str:
        """Расшифровывает переданный зашифрованный текст, используя предоставленный приватный ключ RSA.

        :param private_key: Приватный ключ RSA в формате PEM.
        :param ciphertext: Зашифрованный текст в кодировке Base64, который необходимо расшифровать.
        :return: Расшифрованный текст.
        """
        try:
            prv_key = RSA.import_key(private_key.encode("utf-8"))
            cipher = PKCS1_v1_5.new(prv_key)
            ciphertext_bytes = base64.b64decode(ciphertext.encode("utf-8"))
            plaintext_bytes = cipher.decrypt(ciphertext_bytes, None)
            plaintext = plaintext_bytes.decode("utf-8")
            return plaintext
        except Exception as e:
            raise CryptoRSAException(e)
