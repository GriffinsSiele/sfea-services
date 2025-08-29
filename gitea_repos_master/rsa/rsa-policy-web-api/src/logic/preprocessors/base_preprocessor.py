import logging

import cv2
import numpy as np
from numpy import ndarray

from fastapi import HTTPException
from src.config.configuration import ColorsConfig
from src.config.custom_types import ImagePosition, RectCoord
from src.fastapi.adapters import ResponseAdapter
from src.logic.preprocessors.rect_cropper import RectCropper

GREY = ColorsConfig.grey
BLACK = ColorsConfig.black
WHITE = ColorsConfig.white
BORDER = ColorsConfig.border_width


class BasePreprocessor:
    def __init__(self):
        self.rect_cropper = RectCropper()

    @staticmethod
    def binaring(img: ndarray) -> ndarray:
        return cv2.threshold(img, BLACK, WHITE, cv2.THRESH_BINARY + cv2.THRESH_OTSU)[1]

    def _find_black_border(self, bin_img):

        try:
            black_coord = np.argwhere(bin_img == 0)
            border_coord_y = [min(black_coord[:, 0]), max(black_coord[:, 0]) + 1]
            border_coord_x = [min(black_coord[:, 1]), max(black_coord[:, 1]) + 1]

        except Exception as e:
            logging.error("!!! Non-standard image, check logs and image")
            raise HTTPException(
                status_code=415,
                detail=ResponseAdapter.media_error(
                    "Non-standard image, check logs and image"
                ),
            )
        return border_coord_y, border_coord_x

    def rect_preprocessing(
        self, bin_img: ndarray, grey_img, rect_coord: RectCoord
    ) -> ImagePosition:
        recognize_imgs: ImagePosition = {}
        for rect in rect_coord:
            # Обрезка исходного изображения в пределах прямоугольника с текстом, смещенное на 1 пиксель по диагонали
            crop_img = bin_img[rect[0][0] + 1 : rect[0][1], rect[1][0] + 1 : rect[1][1]]

            if isinstance(grey_img, ndarray):
                crop_grey = grey_img[
                    rect[0][0] + 1 : rect[0][1], rect[1][0] + 1 : rect[1][1]
                ]

            # Изображение текста, в пределах чёрных пикселей
            crop_text_y, crop_text_x = self._find_black_border(crop_img)

            if isinstance(grey_img, ndarray):
                recognize_img = crop_grey[
                    crop_text_y[0] : crop_text_y[1], crop_text_x[0] : crop_text_x[1]
                ]
            else:
                recognize_img = crop_img[
                    crop_text_y[0] : crop_text_y[1], crop_text_x[0] : crop_text_x[1]
                ]

            # расширение изображения белыми зонами
            recognize_img = cv2.copyMakeBorder(
                recognize_img,
                BORDER,
                BORDER,
                BORDER,
                BORDER,
                borderType=cv2.BORDER_CONSTANT,
                value=255,
            )
            key = rect[1][0]
            if key in recognize_imgs.keys():
                if isinstance(recognize_imgs[key], list):
                    recognize_imgs[key].append(recognize_img)
                else:
                    recognize_imgs[key] = [recognize_imgs.pop(key), recognize_img]
            else:
                recognize_imgs[key] = recognize_img

        return recognize_imgs
