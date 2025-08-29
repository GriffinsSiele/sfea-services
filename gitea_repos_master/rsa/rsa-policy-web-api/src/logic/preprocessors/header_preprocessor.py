import logging

import cv2
from numpy import ndarray

from src.config.custom_types import ImagePosition
from src.logic.preprocessors.base_preprocessor import BasePreprocessor


class HeaderPreprocessor(BasePreprocessor):
    def __init__(self, img_header: ndarray, *args, **kwargs):
        super().__init__(*args, **kwargs)

        self.bgr_header = img_header

    def parse_header(self) -> ImagePosition:
        logging.info("Convert headerimage to bin")
        img_grey = cv2.cvtColor(self.bgr_header[1:-1], cv2.COLOR_RGB2GRAY)
        bin_img = self.binaring(img_grey)
        logging.info("Find rect coord inside header")
        box_coord = self.rect_cropper.find_rect(bin_img)
        logging.info("Crop-preprocess header values")
        recognize_imgs = self.rect_preprocessing(bin_img, None, box_coord)
        header_values = recognize_imgs
        return header_values
