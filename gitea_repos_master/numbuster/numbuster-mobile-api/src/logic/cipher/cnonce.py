import random
import string


class CNonceGenerator:
    @staticmethod
    def get_random():
        alphabet = string.ascii_lowercase + string.ascii_uppercase + string.digits
        cnonce_len = random.randint(0, 19) + 30
        return "".join([random.choice(alphabet) for _ in range(cnonce_len)])
