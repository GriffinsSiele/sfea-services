import os
import random

from putils_logic.putils import PUtils

from src.utils.metaclasses import Singleton


class UsernameGenerator(metaclass=Singleton):
    def __init__(self):
        with open(PUtils.bp(os.path.abspath(__file__), "..", "username.txt")) as f:
            data = f.readlines()
        self.variants = [v.strip() for v in data]

    def generate_name(self):
        return random.choice(self.variants)

    def generate_fullname(self):
        has_surname = random.choice([0, 1, 1])  # неравномерное распределение, веса
        first_name = self.generate_name()
        last_name = self.generate_name() if has_surname else ""
        return first_name, last_name

    def generate_by_phone(self, phone=None):
        # очень редко (2%) используем телефон как имя пользователя
        is_phone_username = phone and random.choice([0] * 49 + [1])
        return (phone, "") if is_phone_username else self.generate_fullname()
