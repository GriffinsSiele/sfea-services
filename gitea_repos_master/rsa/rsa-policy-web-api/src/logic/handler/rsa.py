import logging

from fastapi import HTTPException
from src.config.configuration import ImageConfig
from src.config.custom_types import (
    AssociateResult,
    BodyPosition,
    ImagePosition,
    InsideValues,
    JsonList,
    PreprocessImages,
    RecognizeResult,
    UploadImage,
    UploadPolicyImage,
)
from src.fastapi.adapters import ResponseAdapter
from src.logic.preprocessors.image_preprocessor import ImagePreprocessor
from src.logic.recognizers.inside_recognizer import InsideRecognizer
from src.logic.recognizers.outside_recognizer import OutsideRecognizer
from src.logic.recognizers.policy_recognizer import PolicyRecognizer

EXTENSION = ImageConfig.img_extension
IMG_MIN_WIDTH = ImageConfig.img_min_width
IMG_MIN_HEIGHT = ImageConfig.img_min_height
POLICY_MIN_WIDTH = ImageConfig.policy_min_width
POLICY_MIN_HEIGHT = ImageConfig.policy_min_height


class RSA:

    def __init__(
        self,
        img_head: UploadImage,
        img_body: UploadImage,
        img_policy: UploadPolicyImage = None,
    ):

        logging.info("Checking the integrity of the input images")
        status = self.__check_images(img_head, img_body, img_policy)
        logging.info("Checking completed successfully")
        preprocess_images = [img_head[1], img_body[1]]

        self.policy_flag = False
        if img_policy:
            self.policy_flag = True
            preprocess_images.append(img_policy[1])
            self.policy_recognizer = PolicyRecognizer()

        self.image_preprocessor = ImagePreprocessor(*preprocess_images)
        self.outside_recognizer = OutsideRecognizer()
        self.inside_recognizer = InsideRecognizer()

    def __check_images(
        self, img_head: UploadImage, img_body: UploadImage, img_policy: UploadPolicyImage
    ) -> bool:

        img_objects = {"img_head": img_head, "img_body": img_body}
        if img_policy:
            img_objects["img_policy"] = img_policy

        for key, img_object in img_objects.items():
            img_name = img_object[0]
            img_shape = img_object[2]
            height, width = img_shape
            logging.info(f"Size for {img_name}: {width}x{height}")
            min_width, min_height = (
                (POLICY_MIN_WIDTH, POLICY_MIN_HEIGHT)
                if key == "img_policy"
                else (IMG_MIN_WIDTH, IMG_MIN_HEIGHT)
            )
            if (width < min_width) or (height < min_height):
                logging.error(
                    f'!!! Image "{img_name}" is less than the limit parameters, check it'
                )
                raise HTTPException(
                    status_code=415,
                    detail=ResponseAdapter.media_error(
                        "The image dimensions do not correspond to the minimum"
                    ),
                )

        return True

    def __preprocess_images(self) -> PreprocessImages:
        preprocess_images = self.image_preprocessor.image_preprocess()
        return preprocess_images

    def __recognize_main_images(
        self,
        header_values: ImagePosition,
        body_values: BodyPosition,
        inside_header_values: InsideValues,
        inside_body_values: InsideValues,
    ) -> RecognizeResult:
        recognize_result = {}
        outside_recognize_result = self.outside_recognizer.outside_recognize(
            header_values, body_values
        )
        recognize_result.update(outside_recognize_result)
        inside_recognize_result = self.inside_recognizer.inside_recognize(
            inside_header_values, inside_body_values
        )
        recognize_result.update(inside_recognize_result)
        return recognize_result

    def __convert_main_result(self, recognition_result: RecognizeResult):
        json_list = []
        error_list = []
        key_list = list(recognition_result.keys())
        inside_len = len(recognition_result[key_list[0]])
        for i in range(inside_len):
            json_dict = {}
            for key, value in recognition_result.items():
                if value[i] not in ("", "!"):
                    json_dict[key] = value[i]
                elif value[i] == "!":
                    error_list.append(key)
            json_list.append(json_dict)
        return json_list, error_list

    def __recognize_policy_images(self, policy_images: PreprocessImages) -> JsonList:
        policy_data = []
        if self.policy_flag:
            if policy_images:
                logging.info("Policy image recognition")
                policy_recognize_result = self.policy_recognizer.policy_recognize(
                    policy_images["policy_key_value"]
                )
            else:
                policy_recognize_result = {
                    "error": "Error in the policy image preprocessing. Check logs and policy input image"
                }
            policy_data.append(policy_recognize_result)
        return policy_data

    def __convert_policy_result(self, recognition_result: JsonList):
        json_list = []
        error_list = []
        for i in range(len(recognition_result)):
            json_dict = {}
            for key, value in recognition_result[i].items():
                if value not in ("", "!"):
                    json_dict[key] = value
                elif value == "!":
                    error_list.append(key)
            json_list.append(json_dict)
        return json_list, error_list

    def recognize_images(self) -> AssociateResult:

        data_keys = ["main_data", "main_error", "policy_data", "policy_error"]
        logging.info("Image Preprocessing")
        preprocess_images = self.__preprocess_images()
        logging.info("Main image recognition")
        main_images, policy_images = list(preprocess_images.values())
        recognition_result = self.__recognize_main_images(*list(main_images.values()))
        main_list, main_error = self.__convert_main_result(recognition_result)
        policy_result = self.__recognize_policy_images(policy_images)
        policy_list, policy_error = self.__convert_policy_result(policy_result)
        images_associate_result = dict(
            zip(data_keys, [main_list, main_error, policy_list, policy_error])
        )
        return images_associate_result
