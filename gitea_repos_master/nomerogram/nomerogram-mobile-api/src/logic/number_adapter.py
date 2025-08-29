import re

from pydash import map_
from request_logic.exceptions import InCorrectData


class CarplateAdapter:
    @staticmethod
    def number_to_server(number):
        allowed_letters = ''.join(CarplateAdapter.map.values())
        regex = '^[' + allowed_letters + ']\d{3}(?<!000)[' + allowed_letters + ']{2}\d{2,3}$'

        number_parsed = ''.join(
            map_(number.lower(), lambda c: CarplateAdapter.map[c] if c in CarplateAdapter.map else c))

        if re.match(regex, number_parsed):
            return number_parsed

        raise InCorrectData(f'Not matched regex number: {number}')

    # russian to english
    map = {
        'а': 'a',
        'в': 'b',
        'е': 'e',
        'к': 'k',
        'м': 'm',
        'н': 'h',
        'о': 'o',
        'р': 'p',
        'с': 'c',
        'т': 't',
        'у': 'y',
        'х': 'x'
    }
