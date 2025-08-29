import copy

import numpy as np

from src.config.configuration import AssociateConfig, RecognizerConfig
from src.config.custom_types import (
    BodyPosition,
    ImagePosition,
    InsideAssociate,
    InsideValues,
    PolicyRecognizeBody,
    PolicyRecognizeHeader,
    RecognizeBody,
    RecognizeHeader,
)


class ImageRecognizer:
    def __init__(self):
        self.spec_pos, self.spec_val = RecognizerConfig.override_columns_rules.values()
        self.std_recognize = RecognizerConfig.standard_recognize
        self.min_number_width = RecognizerConfig.min_number_width
        self.side_length = RecognizerConfig.side_length

    def recognize_header(self, header_values: ImagePosition) -> RecognizeHeader:

        rec_header = {}

        column_width = header_values[self.spec_pos].shape[1]
        for key, value in header_values.items():
            if column_width > self.min_number_width:
                key += 1
            rec_header[key] = self.std_recognize(value, "rus")
        rec_header[self.spec_pos] = self.spec_val  # принудительная вставка спец.значения
        return rec_header

    def recognize_body(
        self, body_values: BodyPosition, associate_header: RecognizeHeader
    ) -> RecognizeBody:
        rec_body = {}
        column_width = body_values[self.spec_pos][0].shape[1]
        if column_width > self.min_number_width:
            new_body_values = {key + 1: values for key, values in body_values.items()}
            new_body_values[self.spec_pos] = [
                np.empty([self.side_length, self.side_length])
                for _ in range(len(new_body_values[self.spec_pos + 1]))
            ]
            body_values = new_body_values

        for key, name in associate_header.items():
            recognize_function = AssociateConfig.headers_value[name].value[1]
            rec_body[key] = [
                recognize_function(body_values[key], i)
                for i in range(len(body_values[key]))
            ]

        return rec_body

    def recognize_inside_header(self, inside_header_values: InsideValues) -> InsideValues:
        recognize_inshead = list(copy.deepcopy(inside_header_values))
        for i in range(len(recognize_inshead)):
            for key, value in recognize_inshead[i].items():
                recognize_inshead[i][key] = self.std_recognize(value, "rus+eng")
        return recognize_inshead

    def recognize_inside_body(
        self, inside_body_values: InsideValues, associate_insidehead: InsideAssociate
    ) -> InsideValues:
        recognize_insidebody = list(copy.deepcopy(inside_body_values))
        for i in range(len(recognize_insidebody)):
            inside_header = associate_insidehead[i]
            inside_body = inside_body_values[i]
            for key, name in inside_header.items():
                recognize_functions = AssociateConfig.inside_headers_value[name].value[1]
                inside_body[key] = recognize_functions([inside_body[key]], 0)
            recognize_insidebody[i] = inside_body
        return recognize_insidebody

    def recognize_policy_header(self, policy_header) -> PolicyRecognizeHeader:
        rec_header = {
            i: self.std_recognize(policy_header[i - 1], "rus")
            for i in range(1, len(policy_header) + 1)
        }
        return rec_header

    def recognize_policy_body(
        self, policy_body, policy_associate_header
    ) -> PolicyRecognizeBody:
        rec_body = {}
        for key, name in policy_associate_header.items():
            recognize_function = AssociateConfig.policy_headers_value[name].value[1]
            rec_body[key] = recognize_function(policy_body, key - 1)
        return rec_body
