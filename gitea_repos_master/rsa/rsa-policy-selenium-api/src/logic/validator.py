import re


class Validator:
    @staticmethod
    def _is_string(value):
        if not value:
            return False, 'Пустое значение'
        if not isinstance(value, str):
            return False, 'Ожидается строка'
        return True, 'ok'

    @staticmethod
    def _is_valid_by_regex(value, regex):
        if not re.match(regex, value):
            return False, 'Поле не соответствует регулярному выражению'
        return True, 'ok'

    @staticmethod
    def validate_vin(value):
        is_ok, error = Validator._is_string(value)
        if not is_ok:
            return is_ok, error

        allowed_symbols = '\dABCDEFGHJKLMNPRSTUVWXYZ'
        regex = '^[' + allowed_symbols + ']{17}$'

        return Validator._is_valid_by_regex(value.upper(), regex)

    @staticmethod
    def validate_gos_number(value):
        is_ok, error = Validator._is_string(value)
        if not is_ok:
            return is_ok, error

        allowed_symbols = 'АВЕКМНОРСТУХABEKMHOPCTYX'
        regex = '^[' + allowed_symbols + ']\d{3}(?<!000)[' + allowed_symbols + ']{2}\d{2,3}$'
        return Validator._is_valid_by_regex(value.upper(), regex)

    @staticmethod
    def validate_body_number(value):
        # Reference: https://b2bapi.avtocod.ru/docs/reference/vehicle-identifiers
        is_ok, error = Validator._is_string(value)
        if not is_ok:
            return is_ok, error

        regex = '^[A-Z\d]{2,}(\-|\s|)[A-Z\d]{2,9}$'
        return Validator._is_valid_by_regex(value.upper(), regex)

    @staticmethod
    def validate_chassis_number(value):
        is_ok, error = Validator._is_string(value)
        if not is_ok:
            return is_ok, error

        regex = '^[A-Z\d]{2,}(\-|\s|)[A-Z\d]{2,9}$'
        return Validator._is_valid_by_regex(value.upper(), regex)
