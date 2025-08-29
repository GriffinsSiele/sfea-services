import hashlib

from faker import Faker


class EmailHashGenerator:
    faker = Faker()

    @staticmethod
    def generate():
        email = EmailHashGenerator.faker.email()
        return email, hashlib.sha256(email.encode('utf-8')).hexdigest()
