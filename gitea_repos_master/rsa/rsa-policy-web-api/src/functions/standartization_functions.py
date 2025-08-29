import logging
import re
import time
from datetime import datetime

from src.config.standard_config import StandardisationConfig


class StandardizationFunctions:
    RUSSIAN_CHANGE_NAMES = StandardisationConfig.russian_change_names
    CATEGORY_CHANGE_SYMBOLS = StandardisationConfig.category_change_symbols

    OSAGO_LETTER_DOUBLE = StandardisationConfig.osago_letter_double
    OSAGO_LETTER_TRIPLE = StandardisationConfig.osago_letter_triple
    OWNER_CHANGE_SYMBOLS = StandardisationConfig.owner_change_symbols
    OWNER_BLOCKED_SYMBOLS = StandardisationConfig.owner_blocked_symbols

    VIN_CHANGE_SYMBOLS = StandardisationConfig.vin_change_symbols
    VIN_BLOCKED_SYMBOLS = StandardisationConfig.vin_blocked_symbols

    PLATE_BLOCKED_SYMBOLS = StandardisationConfig.plate_blocked_symbols
    PLATE_CHAR_DIGIT = StandardisationConfig.plate_char_digit
    PLATE_DIGIT_CHAR = StandardisationConfig.plate_digit_char
    PLATE_ENG_RUS = StandardisationConfig.plate_eng_rus
    PLATE_REGIONS = StandardisationConfig.plate_regions

    letter_templ = re.compile(r"\D+")
    serial_template = re.compile(r"\D{3}")
    digit_templ = re.compile(r"\d+")
    float_templ = re.compile(r"(\d+)\.?(\d*)")

    @classmethod
    def str_return(cls, string: str, max_value: str) -> str:
        return string

    @classmethod
    def str_std(cls, string: str, max_value: str) -> str:
        return max_value

    @classmethod
    def std_kbm(cls, string: str, max_value: str) -> str:
        float_string = string.strip()
        return_string = ""
        for symbol in float_string:
            if symbol == "." or symbol.isdigit():
                return_string += symbol
            else:
                return_string = "!"
                logging.warning(f'! Failed to recognize correctly KBM: "{string}"')
                break

        return return_string

    @classmethod
    # Серия полиса ОСАГО
    def std_serial(cls, string: str, max_value: str) -> str:
        return_string = ""
        letters = string.upper()
        if any(letter in letters for letter in cls.OSAGO_LETTER_TRIPLE):
            letters = letters[0] * 3
        else:
            for letter in letters:
                if letter in cls.OSAGO_LETTER_DOUBLE:
                    letters = f"{'A' * 2}{letter}"
                    break
                else:
                    letters = "!"
                    logging.warning(
                        f'! Failed to recognize correctly serial of OSAGO: "{string}"'
                    )
        return_string = letters
        return return_string

    @classmethod
    # Номер полиса ОСАГО
    def std_number(cls, string: str, max_value: str) -> str:
        return_string = ""
        digits_list = cls.digit_templ.findall(string)
        digits = digits_list[0] if digits_list else ""

        if len(digits) == 10:
            return_string = f"{digits}"
        else:
            letters = "!"
            logging.warning(
                f'! Failed to recognize correctly number of OSAGO: "{string}"'
            )
        return return_string

    @classmethod
    # Серия и номер ОСАГО
    def std_serial_number(cls, string: str, max_value: str) -> str:
        return_string = ""
        digits_list = cls.digit_templ.findall(string)
        digits = digits_list[0] if digits_list else ""
        letters = cls.serial_template.findall(string)[0].upper()
        if any(letter in letters for letter in cls.OSAGO_LETTER_TRIPLE):
            letters = letters[0] * 3
        else:
            for letter in letters:
                if letter in cls.OSAGO_LETTER_DOUBLE:
                    letters = f"{'A' * 2}{letter}"
                    break
        if (len(letters) == 3) and (len(digits) == 10):
            return_string = f"{letters} {digits}"
        else:
            logging.warning(f'! Failed to recognize correctly OSAGO: "{string}"')
            return_string = "!"
        return return_string

    @classmethod
    # Наименование страховой организации
    def std_company(cls, string: str, max_value: str) -> str:
        company_name = cls.letter_templ.findall(string)[-1]
        if "<" in company_name:
            company_name = company_name.replace("<", "С")
            company_name = company_name.strip()
        organization_type = cls.digit_templ.findall(string)
        if organization_type:
            company_type = organization_type[0]
            if company_type == "000":
                company_type = "ООО"
            company_name = f"{company_type} {company_name}"
        return company_name

    @classmethod
    # Дата изменения статуса полиса
    def std_date(cls, string: str, max_value: str) -> str:
        return_string = ""
        date_segment = string.split(".")
        if len(date_segment) == 3:
            date, month, year = date_segment

            if len(date) == 2 and len(month) == 2 and len(year) == 4:
                try:
                    datetime_date = time.mktime(
                        datetime.strptime(string, "%d.%m.%Y").timetuple()
                    )
                    if datetime_date:
                        return_string = string
                except Exception:
                    logging.warning(
                        f'! Failed to convert correctly to datetime: "{string}"'
                    )
        if not return_string:
            logging.warning(f'! Failed to recognize correctly date: "{string}"')
            return_string = "!"
        return return_string

    @classmethod
    # Собственник транспортного средства
    def std_owner(cls, owner_string: str, max_value: str) -> str:
        return_string = ""
        symbol_to_char = cls.OWNER_CHANGE_SYMBOLS
        blocked_chars = cls.OWNER_BLOCKED_SYMBOLS

        name = cls.letter_templ.findall(owner_string)[0].strip().split(" ")[:3]
        numbers = cls.digit_templ.findall(owner_string)
        digits = [number for number in numbers if len(number) > 1]
        if len(digits) == 0:
            logging.warning(
                f'! Failed to recognize correctly birthday or INN: "{owner_string}"'
            )
            return_string = "!"

        elif len(digits) == 1:
            inn = digits[0]
            if len(inn) == 10:
                return_string = f"Юридическое лицо, ИНН {inn}"
            else:
                logging.warning(
                    f'! Failed to recognize correctly birthday or INN: "{owner_string}"'
                )
                return_string = "!"

        elif len(digits) > 1:
            if len(name) >= 2:
                std_name = []
                for word in name:
                    first_char = word[0]
                    if not first_char.isalpha():
                        try:
                            first_char = symbol_to_char[first_char]
                        except KeyError:
                            logging.warning(
                                f"! Failed to recognize correctly name: \"{name} {'.'.join(digits)}\""
                            )
                            return_string = "!"
                            return return_string

                    if first_char in blocked_chars:
                        logging.warning(
                            f"! Failed to recognize correctly owner field: \"{name} {'.'.join(digits)}\""
                        )
                        return_string = "!"
                        return return_string
                    std_name.append(f"{first_char.upper()}{'*' * 4}")
                return_string = f"{' '.join(std_name)} {'.'.join(digits)}"
            else:
                logging.warning(
                    f"! Failed to recognize correctly owner field: \"{name} {'.'.join(digits)}\""
                )
                return_string = "!"
        return return_string

    @classmethod
    def std_restrictions(cls, string: str, max_value: str) -> str:
        person_count = cls.digit_templ.findall(string)
        if person_count:
            string = f"Ограничен список лиц, допущенных к управлению (допущено: {person_count[-1]} чел.)"
        return string

    @classmethod
    def std_power(cls, string: str, max_value: str) -> str:
        numbers = cls.digit_templ.findall(string)
        if len(numbers) == 2:
            return_string = ".".join(numbers)
        else:
            logging.warning(f'! Failed to recognize correctly engine power: "{string}"')
            return_string = "!"
        return return_string

    @classmethod
    def std_vin(cls, string: str, max_value: str) -> str:
        return_string = ""
        char_to_digit = cls.VIN_CHANGE_SYMBOLS
        blocked_symbols = cls.VIN_BLOCKED_SYMBOLS
        vin_string = string.strip()
        vin_list = list(vin_string)
        if len(vin_list) > 3:
            for i in range(len(vin_list)):
                if vin_list[i] in blocked_symbols:
                    vin_list[i] = ""
                elif vin_list[i] in char_to_digit:
                    vin_list[i] = char_to_digit[vin_list[i].upper()]
            filter_vin = "".join(vin_list)
            prefix = filter_vin[:8]
            suffix = filter_vin[-3:]
            vin_flag = True
            if suffix.isdigit():
                for char in prefix:
                    if (not char.isalpha()) and (not char.isdigit()):
                        logging.warning(
                            f'! Failed to recognize correctly VIN or corpus number, bad prefix: "{string}"'
                        )
                        vin_flag = False
                        return_string = "!"
            else:
                logging.warning(
                    f'! Failed to recognize correctly VIN or corpus number, bad postfix: "{string}"'
                )
                vin_flag = False
                return_string = "!"

            if vin_flag:
                return_string = f"{prefix.upper()}{'*' * 6}{suffix}"
        else:
            logging.warning(
                f'! Failed to recognize correctly VIN or corpus number: "{string}"'
            )
            return_string = "!"
        return return_string

    @classmethod
    def std_corpus(cls, string: str, max_value: str) -> str:
        alphabet = False
        for letter in string:
            if letter.isalpha():
                alphabet = True
                break
        if alphabet:
            corpus_number = cls.std_vin(string, max_value)
        else:
            digits = cls.digit_templ.findall(string)
            if len(digits) == 1:
                corpus_number = f"{digits[0]}"
            elif len(digits) == 2:
                corpus_number = f"{digits[0]}{'*' * 6}{digits[1]}"
            else:
                logging.warning(f'! Unknown corpus number format: "{string}"')
                corpus_number = "!"
        return corpus_number

    @classmethod
    def std_premium(cls, string: str, max_value: str) -> str:
        return_string = "!"
        find_values = cls.float_templ.findall(string)
        if len(find_values) == 1:
            premium_values = find_values[0]
            if len(premium_values) == 2:
                if premium_values[0]:
                    rubles_value = premium_values[0]
                    if premium_values[1] and len(rubles_value) > 2:
                        return_string = ".".join(premium_values) + " руб."
                        return return_string
                    elif not premium_values[1] and len(rubles_value) > 4:
                        return_string = (
                            rubles_value[:3] + "." + rubles_value[3:] + " руб."
                        )
                        return return_string

        logging.warning(f'! Failed to recognize correctly insurance premium: "{string}"')
        return return_string

    @classmethod
    def std_auto(cls, auto_string: str, max_value: str) -> str:
        auto_name = "!"
        change_names = cls.RUSSIAN_CHANGE_NAMES
        change_categories = cls.CATEGORY_CHANGE_SYMBOLS
        if auto_string:
            for bad_name in change_names:
                if bad_name in auto_string:
                    auto_string = auto_string.replace(bad_name, change_names[bad_name])
            string_list = auto_string.split(" ")
            auto_category = string_list[-1][-3]
            if auto_category in change_categories:
                auto_category = change_categories[auto_category]
            string_list[-1] = f"(категория '{auto_category}')"
            auto_name = " ".join(string_list)
        else:
            logging.warning(
                f'! Failed to recognize correctly make and model of the car: "{auto_string}"'
            )
        return auto_name

    @staticmethod
    def __check_string(string, blocked_symbols):

        char_list = list(string)
        for symbol in blocked_symbols:
            if symbol in char_list:
                char_list.remove(symbol)
        return char_list

    @staticmethod
    def __test_charlist(char_list: list[str]) -> bool:
        test_flag = True
        if len(char_list) > 15:
            test_flag = False

        if len(char_list) < 7:
            test_flag = False
            logging.warning(
                f'! The number of characters is not enough for car plate number: "{char_list}"'
            )

        elif len(char_list) >= 10 and len(char_list) < 15:
            test_flag = False
            logging.warning(
                f'! The number of characters is excessive for car plate number: "{char_list}"'
            )

        return test_flag

    @staticmethod
    def __taxi_plate_check(char_list, char_to_digit, digit_to_char, eng_to_rus):
        region_digits = []
        plate_chars = []
        for i in range(len(char_list)):
            symbol = char_list[i]
            if i < 2:
                if char_list[i].isdigit():
                    plate_chars.append(digit_to_char[symbol])
                elif char_list[i].isalpha():
                    plate_chars.append(eng_to_rus[symbol.upper()])
            else:
                digit = symbol
                if digit.isalpha():
                    digit = char_to_digit[symbol.upper()]
                if i <= 4:
                    plate_chars.append(digit)
                elif i > 4:
                    region_digits.append(digit)

        return plate_chars, region_digits

    @staticmethod
    def __civil_plate_check(char_list, char_to_digit, digit_to_char, eng_to_rus):
        region_digits = []
        plate_chars = []
        for i in range(len(char_list)):
            symbol = char_list[i]
            if i == 0:
                if symbol.isdigit():
                    plate_chars.append(digit_to_char[symbol])
                elif symbol.isalpha():
                    plate_chars.append(eng_to_rus[symbol.upper()])
            elif i in [1, 2, 3]:
                if symbol.isalpha():
                    plate_chars.append(char_to_digit[symbol.upper()])
                elif symbol.isdigit:
                    plate_chars.append(symbol)
            elif i in [4, 5]:
                if symbol.isdigit():
                    plate_chars.append(digit_to_char[symbol])
                elif symbol.isalpha():
                    plate_chars.append(eng_to_rus[symbol.upper()])
            elif i > 5:
                digit = symbol
                if digit.isalpha():
                    digit = char_to_digit[symbol.upper()]
                region_digits.append(digit)
        return plate_chars, region_digits

    @classmethod
    def std_plate(cls, string: str, max_value: str) -> str:
        return_string = "Сведения отсутствуют"

        blocked_symbols = cls.PLATE_BLOCKED_SYMBOLS
        char_to_digit = cls.PLATE_CHAR_DIGIT
        digit_to_char = cls.PLATE_DIGIT_CHAR
        region_dict = cls.PLATE_REGIONS
        eng_to_rus = cls.PLATE_ENG_RUS
        char_list = cls.__check_string(string, blocked_symbols)
        test_flag = cls.__test_charlist(char_list)
        if test_flag:
            try:
                if len(char_list) == 7:
                    plate_chars, region_digits = cls.__taxi_plate_check(
                        char_list, char_to_digit, digit_to_char, eng_to_rus
                    )
                elif len(char_list) > 7 and len(char_list) < 10:
                    plate_chars, region_digits = cls.__civil_plate_check(
                        char_list, char_to_digit, digit_to_char, eng_to_rus
                    )
            except KeyError:
                logging.warning(
                    f"! Failed to recognize correctly car plate number:  \"{''.join(char_list)}\""
                )
                return_string = "!"
                return return_string
            region_number = "".join(region_digits)
            if region_number in region_dict:
                return_string = "".join(plate_chars) + region_number
            else:
                logging.warning(
                    f"! Failed to recognize correctly car plate number:  \"{''.join(char_list)}\""
                )
                return_string = "!"

        return return_string
