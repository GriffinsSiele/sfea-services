import logging

import cv2
import numpy

from fastapi import APIRouter, Depends, HTTPException, Response, UploadFile
from src.config.custom_types import ResponseAnswer, UploadImage
from src.fastapi.adapters import ResponseAdapter
from src.fastapi.validator import InputData, Validator
from src.functions.loging_exceptions import logging_exceptions
from src.logic.handler.rsa import RSA

rsa_router = APIRouter(tags=["Распознавание полисов РСА"])


def read_upload_image(upload_image: UploadFile) -> UploadImage:
    img_name = upload_image.filename
    img_str = upload_image.file.read()
    nparray = numpy.frombuffer(img_str, numpy.uint8)
    img_np = cv2.imdecode(nparray, cv2.IMREAD_COLOR)  # RGB
    img_shape = img_np.shape[:-1]
    return img_name, img_np, img_shape


@rsa_router.get("/status")
def status() -> dict:
    """Проверка состояния сервиса."""
    return ResponseAdapter.success(["Ok"])


@rsa_router.post("/recognize_rsa", description="Распознавание изображений сайта РСА")
def upload_file(response: Response, input_data: InputData = Depends()):
    status_code = 200
    response_answer: ResponseAnswer = {}
    images = Validator.image_validation(input_data)
    logging.info("Upload Image")
    upload_images = list(map(read_upload_image, images))
    recognizer = RSA(*upload_images)
    logging.info("Start image recognition")
    try:
        images_associate_result = recognizer.recognize_images()
        main_data, main_error, policy_data, policy_error = list(
            images_associate_result.values()
        )
        response_answer["main_table"] = Validator.main_validation(main_data)
        response_answer["main_error"] = Validator.main_error_validation(main_error)

        if policy_data:
            answer_key = "policy_table"
            error_key = "policy_error"
            if "error" in policy_data[0]:
                response_answer[answer_key] = policy_data[0]
                logging.error(
                    "!Recognition Policy Image Error, Main Images Recognition Complete!"
                )
                raise HTTPException(
                    status_code=500,
                    detail=ResponseAdapter.internal_error(response_answer),
                )
            response_answer[answer_key] = Validator.policy_validation(policy_data)
            response_answer[error_key] = Validator.policy_error_validation(policy_error)

    except HTTPException as e:
        raise e
    except Exception as e:
        logging.error(f'!!! Internal server error: "{logging_exceptions()}"')
        raise HTTPException(
            status_code=500,
            detail=ResponseAdapter.internal_error(
                "!!! Internal server error, check logs"
            ),
        )

    logging.info("!Recognition complete!")
    answer = ResponseAdapter.success(response_answer)
    response.status_code = status_code

    return answer
