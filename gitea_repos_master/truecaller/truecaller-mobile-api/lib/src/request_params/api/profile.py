from typing import Optional

from lib.src.config.app import ConfigApp
from lib.src.logic.faker.faker import Faker
from lib.src.request_params.interfaces.authed import AuthedParams


class ProfileParams(AuthedParams):
    def __init__(
        self,
        phone_number: str,
        first_name: Optional[str] = None,
        last_name: Optional[str] = None,
        email: Optional[str] = None,
        country: Optional[str] = "IN",
        *args,
        **kwargs,
    ):
        first_name = first_name if first_name else Faker.first_name()
        last_name = last_name if last_name else Faker.last_name()
        email = email if email else Faker.email()

        payload = self.__create_payload(
            first_name, last_name, email, phone_number, country
        )

        super().__init__(
            url="https://profile4-noneu.truecaller.com/v4/profile?encoding=json",
            payload=payload,
            *args,
            **kwargs,
        )
        self.method = "POST"
        self.headers = {
            **self.headers,
            "Host": "profile4-noneu.truecaller.com",
            "clientSecret": ConfigApp.CLIENT_SECRET,
        }
        self.query = {
            "encoding": "json",
        }

    def __create_payload(self, first_name, last_name, email, phone_number, country):
        return {
            "firstName": first_name,
            "lastName": last_name,
            "personalData": {
                "about": "",
                "address": {"city": "", "country": country, "street": "", "zipCode": ""},
                "avatarUrl": "",
                "companyName": "",
                "gender": "N",
                "isCredUser": False,
                "jobTitle": "",
                "onlineIds": {
                    "email": email,
                    "facebookId": "",
                    "googleIdToken": "",
                    "twitterId": "",
                    "url": "",
                },
                "phoneNumbers": [int(phone_number)],
                "privacy": "Private",
                "tags": [],
            },
        }
