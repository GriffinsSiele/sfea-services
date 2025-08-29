import logging
from collections import defaultdict

import cv2
from numpy import ndarray

from src.config.configuration import PolicyConfig
from src.config.custom_types import PolicyPosition
from src.logic.preprocessors.base_preprocessor import BasePreprocessor

MIN_BORDER = PolicyConfig.min_table_border
MAX_BORDER = PolicyConfig.max_table_border


class PolicyPreprocessor(BasePreprocessor):
    min_border = MIN_BORDER
    max_border = MAX_BORDER

    def __init__(self, img_policy: ndarray, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.bgr_policy = img_policy

    def parse_policy(self) -> PolicyPosition:

        policy_key_value: PolicyPosition = defaultdict(list)

        logging.info("Convert policyimage to gray/bin")

        img_grey = cv2.cvtColor(self.bgr_policy, cv2.COLOR_RGB2GRAY)
        bin_img = self.binaring(img_grey)[self.min_border : self.max_border]

        img_grey = img_grey[self.min_border : self.max_border]

        logging.info("Find policyimage table coord")
        table_coord_y, table_coord_x = self._find_black_border(bin_img)
        table_coord_y[0] += 1  # Смещение на 1 пиксель вниз по Y

        bin_img = bin_img[
            table_coord_y[0] : table_coord_y[1], table_coord_x[0] : table_coord_x[1]
        ]
        img_grey = img_grey[
            table_coord_y[0] : table_coord_y[1], table_coord_x[0] : table_coord_x[1]
        ]

        logging.info("Find header and body of policy table")
        policy_coord = self.rect_cropper.find_rect(bin_img, external_borders=True)

        for coord in policy_coord:

            y_min, y_max = coord[0]
            x_min, x_max = coord[1]

            logging.info("Find inside rect of policy table")
            box_coord = self.rect_cropper.find_rect(bin_img[y_min:y_max, x_min:x_max])

            logging.info("Extract inside rect images from table")
            recognize_imgs = self.rect_preprocessing(
                bin_img[y_min:y_max, x_min:x_max],
                img_grey[y_min:y_max, x_min:x_max],
                box_coord,
            )

            for key, value in recognize_imgs.items():
                policy_key_value[key].append(value)

        policy_key_value = dict(policy_key_value)

        return policy_key_value
