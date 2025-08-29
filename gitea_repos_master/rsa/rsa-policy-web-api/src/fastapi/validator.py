import logging
from copy import deepcopy

from fastapi import HTTPException
from src.config.custom_types import (
    JsonList,
    ValidateList,
    ValidationError,
    ValidationMain,
    ValidationPolicy,
)
from src.config.validation_config import (
    allowed_types,
    min_byte_policy,
    min_byte_size,
    policy_usual_validation,
    unusual_validation,
    usual_validation,
)
from src.fastapi.adapters import ResponseAdapter
from src.fastapi.schemas import InputData
from src.functions.loging_exceptions import logging_exceptions


class Validator:
    UNUSUAL_VALIDATION = unusual_validation
    USUAL_VALIDATION = usual_validation
    POLICY_USUAL_VALIDATION = policy_usual_validation
    MIN_BYTE_SIZE = min_byte_size
    MIN_BYTE_POLICY = min_byte_policy
    ALLOWED_TYPES = allowed_types

    @classmethod
    def main_validation(cls, associate_result: JsonList) -> ValidationMain:
        validation_list: ValidationMain = []
        for main_result in associate_result:
            validation_main = {}
            for key, value in main_result.items():
                if key == "№":
                    continue
                elif key in cls.UNUSUAL_VALIDATION:
                    func = cls.UNUSUAL_VALIDATION[key][0]
                    fields = cls.UNUSUAL_VALIDATION[key][1]
                    try:
                        validation_main.update(func(value, fields))
                    except Exception:
                        logging.error(f'!!! Validation error: "{logging_exceptions()}"')
                        raise HTTPException(
                            status_code=422,
                            detail=ResponseAdapter.validation_error(
                                "!!! Error in the validation process, check logs"
                            ),
                        )
                elif key in cls.USUAL_VALIDATION:
                    validation_key = cls.USUAL_VALIDATION[key]
                    validation_main[validation_key] = value
                else:
                    logging.warning(
                        f'! Unknown key for validation, check image and data: "{key}:{value}"'
                    )
            validation_list.append(validation_main)
        return validation_list

    @classmethod
    def main_error_validation(cls, main_error) -> ValidationError:
        error_list: ValidationError = deepcopy(main_error)
        if error_list:
            for i in range(len(error_list)):
                if error_list[i] in cls.USUAL_VALIDATION:
                    validation_value = cls.USUAL_VALIDATION[error_list[i]]
                    error_list[i] = validation_value
                elif error_list[i] in cls.UNUSUAL_VALIDATION:
                    validation_tuple = cls.UNUSUAL_VALIDATION[error_list[i]][-1]
                    if len(validation_tuple) > 1:
                        validation_value = "/".join(validation_tuple)
                    else:
                        validation_value = validation_tuple[0]
                    error_list[i] = validation_value
                else:
                    logging.warning(
                        f'!!! Unknown key for validation, check image and data: "{error_list[i]}"'
                    )
        return error_list

    @classmethod
    def policy_error_validation(cls, policy_error) -> ValidationError:
        error_list: ValidationError = deepcopy(policy_error)
        if error_list:
            for i in range(len(error_list)):
                if error_list[i] in cls.POLICY_USUAL_VALIDATION:
                    validation_value = cls.POLICY_USUAL_VALIDATION[error_list[i]]
                    error_list[i] = validation_value
                else:
                    logging.warning(
                        f'!!! Unknown key for validation, check image and data: "{error_list[i]}"'
                    )
        return error_list

    @classmethod
    def policy_validation(cls, policy_associate_result: JsonList) -> ValidationPolicy:
        validation_policy: ValidationPolicy = {}
        if policy_associate_result:
            policy_result = policy_associate_result[0]
            for key, value in policy_result.items():
                if key in cls.POLICY_USUAL_VALIDATION:
                    validation_key = cls.POLICY_USUAL_VALIDATION[key]
                    validation_policy[validation_key] = value
                else:
                    logging.warning(
                        f'! Unknown key for validation, check image and data: "{key}:{value}"'
                    )
        return validation_policy

    @classmethod
    def image_validation(cls, input_data: InputData) -> ValidateList:
        upload_images = []
        check_images = [
            input_data.header_file,
            input_data.body_file,
            input_data.policy_file,
        ]
        if input_data.meta_info:
            logging.info(f"Meta info: {input_data.meta_info}")
        for i in range(len(check_images)):
            if check_images[i]:
                logging.info(
                    f"Checking the type and size of the input image {check_images[i].filename}"
                )
                min_byte = cls.MIN_BYTE_POLICY if i == 2 else cls.MIN_BYTE_SIZE
                if check_images[i].size < min_byte:
                    logging.error(
                        f'!!! Image file "{check_images[i].filename}" is less than {min_byte // 1e3} KB: Size={check_images[i].size} Byte'
                    )
                    if i == 2:
                        check_images[i] = None
                        continue

                    else:
                        raise HTTPException(
                            status_code=415,
                            detail=ResponseAdapter.media_error(
                                f'!!! File "{check_images[i].filename}" too less'
                            ),
                        )

                if check_images[i].content_type not in cls.ALLOWED_TYPES:
                    logging.error(
                        f'!!! Image file "{check_images[i].filename}" has an invalid file type "{check_images[i].content_type}" '
                    )
                    if i == 2:
                        check_images[i] = None
                    else:
                        raise HTTPException(
                            status_code=415,
                            detail=ResponseAdapter.media_error("Invalid file type"),
                        )
                logging.info(
                    f"Checking completed successfully for {check_images[i].filename}"
                )
                upload_images.append(check_images[i])
        return upload_images
