import logging

from numpy import ndarray

from src.config.configuration import ImageConfig
from src.config.custom_types import (
    BodyPosition,
    FiltredValues,
    ImagePosition,
    InsideTable,
    InsideValues,
    LineCoord,
    ParsingInside,
    PreprocessImages,
    ReplacementInside,
)
from src.functions.loging_exceptions import logging_exceptions
from src.logic.preprocessors.body_preprocessor import BodyPreprocessor
from src.logic.preprocessors.header_preprocessor import HeaderPreprocessor
from src.logic.preprocessors.policy_preprocessor import PolicyPreprocessor

OFFSET_MIN = ImageConfig.pix_offset_min
OFFSET_MAX = ImageConfig.pix_offset_max


class ImagePreprocessor:
    required_keys = [
        "header_values",
        "body_values",
        "inside_header_values",
        "inside_body_values",
    ]
    offset_min = OFFSET_MIN
    offset_max = OFFSET_MAX

    def __init__(self, img_head: ndarray, img_body: ndarray, img_policy: ndarray = None):

        self.header_preprocessor = HeaderPreprocessor(img_head)
        self.body_preprocessor = BodyPreprocessor(img_body)
        self.policy_flag = False
        if isinstance(img_policy, ndarray):
            self.policy_preprocessor = PolicyPreprocessor(img_policy)
            self.policy_flag = True

    def __key_del(
        self, raw_header: ImagePosition, raw_body: BodyPosition, delete_keys: InsideTable
    ) -> ParsingInside:
        for key in delete_keys:
            if key == delete_keys[0]:
                del raw_header[key]
            del raw_body[key]
        return raw_header, raw_body

    def __key_replacement(
        self, keys: LineCoord, raw_header: ImagePosition, raw_body: BodyPosition
    ) -> ParsingInside:
        header_values = {}
        body_values = {}
        for i in range(len(keys)):
            header_values[i + 1] = raw_header[keys[i]]
            body_values[i + 1] = raw_body[keys[i]]
        return header_values, body_values

    def __join_inside(
        self, raw_header: ImagePosition, raw_body: BodyPosition, inside_colum: int
    ) -> ReplacementInside:
        inside_header_key = list(raw_body.keys())[inside_colum - 1]
        inside_body_key = list(raw_body.keys())[inside_colum]
        inside_header_values: InsideValues = raw_body[
            inside_header_key
        ]  # внуренние заголовки
        inside_body_values: InsideValues = raw_body[
            inside_body_key
        ]  # внутренние значения

        for i in range(len(inside_header_values)):
            value_head = inside_header_values[i]
            value_body = inside_body_values[i]
            inside_header_values[i] = {
                j + 1: value_head[j] for j in range(len(value_head))
            }
            inside_body_values[i] = {j + 1: value_body[j] for j in range(len(value_body))}
        delete_keys = (inside_header_key, inside_body_key)
        raw_header, raw_body = self.__key_del(raw_header, raw_body, delete_keys)
        keys = list(raw_header.keys())
        header_values, body_values = self.__key_replacement(keys, raw_header, raw_body)
        return header_values, body_values, inside_header_values, inside_body_values

    def __filter_values(
        self, raw_header_values: ImagePosition, raw_body_values: BodyPosition
    ) -> FiltredValues:
        keys = list(raw_body_values.keys())
        headers = list(raw_header_values.keys())
        inside_column: int
        for i in range(len(keys)):
            for j in range(len(headers)):
                if keys[i] == headers[j]:
                    del headers[j]
                    break
                else:
                    difference = abs(keys[i] - headers[j])
                    if difference <= self.offset_min:
                        min_key = min(keys[i], headers[j])
                        raw_body_values[min_key] = raw_body_values.pop(keys[i])
                        raw_header_values[min_key] = raw_header_values.pop(headers[j])
                        del headers[j]
                        break
                    elif difference >= self.offset_max:
                        inside_column = i
                        break
        raw_body_values = dict(sorted(raw_body_values.items()))
        raw_header_values = dict(sorted(raw_header_values.items()))
        return raw_header_values, raw_body_values, inside_column

    def image_preprocess(self) -> PreprocessImages:

        preprocess_images: PreprocessImages = {"main_images": {}, "policy_images": {}}

        logging.info("Header preprocessing")
        raw_header_values = self.header_preprocessor.parse_header()
        logging.info("Body preprocessing")
        raw_body_values = self.body_preprocessor.parse_body()
        logging.info("Extract outside field and inside column")
        raw_header_values, raw_body_values, inside_column = self.__filter_values(
            raw_header_values, raw_body_values
        )
        logging.info("Separate outside and inside fields")
        main_images_values = self.__join_inside(
            raw_header_values, raw_body_values, inside_column
        )
        preprocess_images["main_images"].update(
            dict(zip(self.required_keys, main_images_values))
        )

        if self.policy_flag:
            logging.info("Policy preprocessing")
            try:
                policy_key_value = self.policy_preprocessor.parse_policy()
                preprocess_images["policy_images"].update(
                    {"policy_key_value": policy_key_value}
                )
            except Exception:
                logging.error("!!! Error in the policy preprocessing")
                logging.error(f"{logging_exceptions()}")

        return preprocess_images
