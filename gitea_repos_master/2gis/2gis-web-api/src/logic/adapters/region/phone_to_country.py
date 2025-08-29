import re
from enum import Enum


class Country(Enum):
    RU = "RU"
    KZ = "KZ"


class CountryLocate:
    mapper = {Country.RU: r"^\+?(73|74|78|79)", Country.KZ: r"^\+?(76|77)"}

    @staticmethod
    def locate(phone):
        for country, regex in CountryLocate.mapper.items():
            if re.search(regex, phone):
                return country

        return None
