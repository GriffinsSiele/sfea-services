from Crypto.Cipher import PKCS1_OAEP
from Crypto.Hash import SHA256
from Crypto.PublicKey import RSA

from src.transform.base64 import Base64


class RSACipher:
    RSA_PUBLIC_KEY = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAu/pxdwOH/oio0fuMk1HsYS6v1bNnpZwQgsSWoxQ1XTNTuu/gf8ZYadsUi2gPUXwGYRofeQcMNKGr7S5KEwD4CENxx0pnpsalHbDCr+R0MdrOfmpXTpG2v7uA+3t44dE/G1k5dZ3Sg/bb7WMvrNLAeTv3WvYoqspM2qmJJBOf92Gji/Epl68CsUR8qY0hJgS0DMsafZ6akW1z49ZgG9K5hsmSnJfcFKGIYyRlGai0iV8QpevkSht/31iyAO8d33/V5ExS4TnrVwHJt0PV2TVqzFiiPjXorr97TAiCDCRZwMRIaTFsuDcXB16TJiPJOOIE9Lcvpp2QfBSbbNGKQ609SwIDAQAB'

    def __init__(self, key=None):
        key = RSACipher.RSA_PUBLIC_KEY if key is None else key
        self.cipher = PKCS1_OAEP.new(
            key=RSA.importKey(Base64.decode(key)),
            hashAlgo=SHA256,
        )

    def encrypt(self, message):
        return self.cipher.encrypt(message)
