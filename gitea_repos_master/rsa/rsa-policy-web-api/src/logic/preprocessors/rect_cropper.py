import logging

import numpy as np
from numpy import ndarray

from fastapi import HTTPException
from src.config.custom_types import LineCoord, RectCoord
from src.fastapi.adapters import ResponseAdapter


class RectCropper:
    def __init__(self):
        self.bin_img: ndarray

    def __find_columns(self) -> LineCoord:
        horizontal_row = self.bin_img[1]  # вторая строка пикселей в изображении
        # координаты по оси X, где расположены вертикальные линии
        x_columns = [i for i in range(len(horizontal_row)) if horizontal_row[i] == 0]
        if not x_columns:
            logging.error("!!! No vertical lines found, check the images")
            raise HTTPException(
                status_code=415,
                detail=ResponseAdapter.media_error(
                    "No vertical lines found, check the images"
                ),
            )
        return x_columns

    def __find_rows(self, x0: int, x1: int) -> LineCoord:
        y_rows = []
        # веритикальный столбец (координаты Y) на 1 пиксель правее от обнаруженных вертикальных линий
        vertical_column = self.bin_img[:, x0 + 1]
        if 0 in vertical_column:
            # Координаты по оси Y, где в vertical_column присутствуют чёрные пиксели
            y_black = np.where(vertical_column == 0)[0]

            for y in y_black:
                # Строка, в которой предположительно находится горизонтальная линия, без первого(узлового пикселя)
                check_row = self.bin_img[y, 1:][x0:x1]
                uniq_values = np.unique(check_row)
                if len(uniq_values) == 1 and uniq_values[-1] == 0:
                    y_rows.append(y)
        return y_rows

    def find_rect(self, bin_img: ndarray, external_borders=False) -> RectCoord:
        self.bin_img = bin_img
        y_min = 0
        y_max = len(bin_img)
        rect_coord = []
        if external_borders:
            x_columns = [0, len(bin_img[0])]
        else:
            x_columns = self.__find_columns()
        for i in range(len(x_columns) - 1):
            y_min_local = y_min
            y_max_local = y_max
            x0 = x_columns[i]
            x1 = x_columns[i + 1]
            y_rows = self.__find_rows(x0, x1)

            if y_rows:
                for y in y_rows:
                    y_max_local = y
                    rect_coord.append([(y_min_local + 1, y_max_local), (x0, x1)])
                    y_min_local = y_max_local
                    y_max_local = y_max
            else:
                rect_coord.append([(y_min_local + 1, y_max_local), (x0, x1)])
        return rect_coord
