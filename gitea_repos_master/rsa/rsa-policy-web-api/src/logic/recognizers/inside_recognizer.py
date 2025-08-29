from collections import defaultdict

from src.config.configuration import AssociateConfig
from src.config.custom_types import (
    InsideAssociate,
    InsideCombining,
    InsideValues,
    RecognizeResult,
)
from src.logic.recognizers.image_recognizer import ImageRecognizer
from src.logic.recognizers.recognize_associate import RecognizeAssociate

INSIDE_HEADERS_VALUE = AssociateConfig.inside_headers_value


class InsideRecognizer:

    def __init__(self):
        self.inside_headers_value = INSIDE_HEADERS_VALUE
        self.image_recognizer = ImageRecognizer()
        self.recognize_associate = RecognizeAssociate()

    def __recognize_insideheader(
        self, inside_header_values: InsideValues
    ) -> InsideAssociate:
        recognize_insideheader = self.image_recognizer.recognize_inside_header(
            inside_header_values
        )
        associate_insideheader = self.recognize_associate.insidehead_associate(
            recognize_insideheader
        )
        return associate_insideheader

    def __recognize_insidebody(
        self, inside_body_values: InsideValues, associate_insheader: InsideAssociate
    ) -> InsideAssociate:
        recognize_insidebody = self.image_recognizer.recognize_inside_body(
            inside_body_values, associate_insheader
        )
        associate_insidebody = self.recognize_associate.insidebody_associate(
            recognize_insidebody, associate_insheader
        )
        return associate_insidebody

    def __combining_fields(
        self,
        associate_insideheader: InsideAssociate,
        associate_insidebody: InsideAssociate,
    ) -> InsideCombining:

        inside_data = []
        for i in range(len(associate_insideheader)):
            inside_fields = {}
            for key, name in associate_insideheader[i].items():
                inside_fields[name] = associate_insidebody[i][key]
            inside_data.append(inside_fields)
        return inside_data

    def __combining_data(self, inside_data: InsideCombining) -> RecognizeResult:

        combine_dict = defaultdict(list)
        for i in range(len(inside_data)):

            if i == 0:
                for key, value in inside_data[i].items():
                    combine_dict[key].append(value)
            else:
                key_list = list(combine_dict.keys())
                for key, value in inside_data[i].items():
                    if key in key_list:
                        combine_dict[key].append(value)
                        key_list.remove(key)
                    else:
                        combine_dict[key] = ["" for _ in range(i)]
                        combine_dict[key].append(value)
                if key_list:
                    for key in key_list:
                        combine_dict[key].append("")

        inside_dict = dict(combine_dict)

        return inside_dict

    def inside_recognize(
        self, inside_header_values: InsideValues, inside_body_values: InsideValues
    ) -> RecognizeResult:

        inside_recognize_result = {}
        associate_insideheader = self.__recognize_insideheader(inside_header_values)
        associate_insidebody = self.__recognize_insidebody(
            inside_body_values, associate_insideheader
        )
        inside_data = self.__combining_fields(
            associate_insideheader, associate_insidebody
        )
        inside_dict = self.__combining_data(inside_data)
        recognize_result = {
            self.inside_headers_value[key].value[0]: value
            for key, value in inside_dict.items()
        }
        inside_recognize_result.update(recognize_result)
        return inside_recognize_result
