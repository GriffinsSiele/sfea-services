import logging
from collections import defaultdict

import cv2
from numpy import ndarray

from src.config.custom_types import BodyPosition, FindPolicies, ImagePosition
from src.logic.preprocessors.base_preprocessor import BasePreprocessor


class BodyPreprocessor(BasePreprocessor):
    def __init__(self, img_body: ndarray, *args, **kwargs):
        super().__init__(*args, **kwargs)

        self.bgr_body = img_body

    def _find_policy(self, bin_img: ndarray, img_grey: ndarray) -> FindPolicies:
        policy_list_bin = []
        policy_list_grey = []
        policy_coord = self.rect_cropper.find_rect(bin_img, external_borders=True)
        for coord in policy_coord:
            # захватить чёрную полосу
            policy_list_bin.append(
                bin_img[coord[0][0] : coord[0][1] + 1, coord[1][0] : coord[1][1]]
            )
            policy_list_grey.append(
                img_grey[coord[0][0] : coord[0][1] + 1, coord[1][0] : coord[1][1]]
            )
        return policy_list_bin, policy_list_grey

    def parse_body(self) -> BodyPosition:

        logging.info("Convert bodyimage to gray/bin")
        raw_body_values: BodyPosition = defaultdict(list)
        img_grey = cv2.cvtColor(self.bgr_body, cv2.COLOR_RGB2GRAY)
        bin_img = self.binaring(img_grey)
        logging.info("Find policies rect in bin and grey images")
        policy_list_bin, policy_list_grey = self._find_policy(bin_img, img_grey)
        for i in range(len(policy_list_bin)):
            logging.info("Find rect coord inside policy")
            box_coord = self.rect_cropper.find_rect(policy_list_bin[i])
            logging.info("Crop-preprocess body values")
            recognize_imgs: ImagePosition = self.rect_preprocessing(
                policy_list_bin[i], policy_list_grey[i], box_coord
            )
            for key, value in recognize_imgs.items():
                raw_body_values[key].append(value)
        raw_body_values = dict(raw_body_values)

        return raw_body_values
