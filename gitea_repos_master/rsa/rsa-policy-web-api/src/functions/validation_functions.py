import datetime
import logging
import time


class ValidateFunctions:
    @staticmethod
    def validate_float(value, fields):
        validate_dict = {}
        float_list = value.split(",")[0].split(".")

        if len(float_list) > 2:
            float_list = float_list[:2]

        for i in range(len(float_list)):
            part = float_list[i]
            if not part[-1].isdigit():
                float_list[i] = part[:-1]
        float_value = f"{float_list[0]}.{float_list[1]}"
        validate_dict[fields[0]] = float(float_value)
        return validate_dict

    @staticmethod
    def validate_insurance_premium(value, fields):
        validate_dict = {}
        split_string = value.split(" ")
        total_string = split_string[0]
        total_list = total_string.split(",")[0].split(".")

        if len(total_list) > 2:
            total_list = total_list[:2]

        for i in range(len(total_list)):
            part = total_list[i]
            if not part[-1].isdigit():
                total_list[i] = part[:-1]

        total = f"{total_list[0]}.{total_list[1]}"
        validate_dict[fields[0]] = float(total)
        return validate_dict

    @staticmethod
    def validate_contract_restrictions(value, fields):
        validate_dict = {}
        split_string = value.split("(")
        limited = split_string[0].strip()
        validate_dict[fields[0]] = limited
        if len(split_string) > 1:
            drivers_string = split_string[1][:-1]
            drivers = drivers_string.split(" ")[1]
            validate_dict[fields[1]] = int(drivers)
        return validate_dict

    @staticmethod
    def validate_person_organisation(value, fields):
        validate_dict = {}

        if "ИНН" in value:
            validate_dict[fields[2]] = value
        else:
            split_string = value.split(" ")
            name = " ".join(split_string[:-1])
            validate_dict[fields[0]] = name
            birth_date_str = split_string[-1]
            try:
                birth_date = time.mktime(
                    datetime.datetime.strptime(birth_date_str, "%d.%m.%Y").timetuple()
                )
                if birth_date:
                    validate_dict[fields[1]] = birth_date_str
            except Exception:
                logging.warning(
                    f'! Couldn\'t convert the string to datetime format: "{birth_date_str}"'
                )
        return validate_dict

    @staticmethod
    def validate_car_model(value, fields):
        validate_dict = {}
        split_string = value.split("(")
        model = split_string[0].strip()
        category_string = split_string[1][:-1]
        category = category_string.split("'")[1]
        validate_dict[fields[0]] = model
        validate_dict[fields[1]] = category
        return validate_dict
