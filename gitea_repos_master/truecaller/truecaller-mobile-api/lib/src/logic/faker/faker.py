import random
import string

from lib.src.logic.faker.first_names import first_names
from lib.src.logic.faker.last_names import last_names


class Faker:
    @staticmethod
    def first_name() -> str:
        return random.choice(first_names)

    @staticmethod
    def last_name() -> str:
        return random.choice(last_names)

    @staticmethod
    def email() -> str:
        alphabet = string.ascii_letters
        domains = ["gmail.com", "yahoo.com", "hotmail.com", "aol.com", "yandex.ru"]
        return (
            "".join(random.choices(alphabet, k=random.randrange(20, 25)))
            + "@"
            + random.choice(domains)
        )
