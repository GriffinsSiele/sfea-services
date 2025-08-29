from typing import Self, Sequence

import cv2
import numpy

from src.captcha import CaptchaServiceException
from src.captcha.captcha_shapes import Rectangle

top_left = Sequence[int]
bottom_right = Sequence[int]


class SliderCaptchaSolver:
    """Решает капчу со слайдером"""

    def __init__(self, target_img: bytes | str, template_img: bytes | str) -> None:
        """Конструктор класса. В качестве аргументов принимает целевое изображение
        на котором необходимо найти недостающий фрагмент и шаблон - недостающий
        фрагмент изображения.

        :param target_img: Целевое изображение (background).
        :param template_img: Недостающие фрагмент изображения (slider).
        """
        self.target_img = self._load_image(target_img)
        self.template_img = self._load_image(template_img)
        self.target_img_resolution: Rectangle | None = None
        self.template_img_resolution: Rectangle | None = None
        self.matching_cords: tuple[top_left, bottom_right] | None = None

    @staticmethod
    def _load_image(image: bytes | str) -> cv2.typing.MatLike:
        """Преобразует переданное изображение в формат MatLike
        и возвращает в качестве результата.

        :param image: Изображение в формате массива байт или путь к изображению на диске.
        :return: Изображение в формат MatLike.
        """
        try:
            if isinstance(image, str):
                return cv2.imread(image)
            if isinstance(image, bytes):
                np_arr = numpy.frombuffer(image, numpy.uint8)
                return cv2.imdecode(np_arr, cv2.IMREAD_COLOR)
        except Exception as e:
            raise CaptchaServiceException(e)
        raise CaptchaServiceException("Unsupported image format")

    def template_match(self) -> Self:
        """Выполняет поиск совпадения по шаблону.

        :return: Экземпляр класса CaptchaService.
        """
        target = cv2.cvtColor(self.target_img, cv2.COLOR_BGR2RGB)
        template = cv2.cvtColor(self.template_img, cv2.COLOR_BGR2RGB)

        self.target_img_resolution = Rectangle(*target.shape[:2])

        self.template_img_resolution = Rectangle(*template.shape[:2])
        result = cv2.matchTemplate(target, template, cv2.TM_CCOEFF_NORMED)
        min_val, max_val, min_loc, max_loc = cv2.minMaxLoc(result)

        # top_left = max_loc
        bottom_right = (
            max_loc[0] + self.template_img_resolution.width,
            max_loc[1] + self.template_img_resolution.height,
        )
        self.matching_cords = (max_loc, bottom_right)
        return self

    def result(self, target_width: int | None = None, gap: int = 0) -> int:
        """Возвращает результат решения капчи. В некоторых случаях размеры изображения
        на странице сайта отличаются от исходного изображения (масштабирование).
        Для решения данной проблемы передайте ширину отображаемого изображения (target_width)
        и решение капчи будет возвращено с учетом указанной ширины. Если изображения одинаковы,
        не передавайте данный аргумент.
        Если при перемещении слайдера изображения немного не совпадают (в пределах нескольких пикселей)
        воспользуйтесь параметром gap для точного позиционирования решения.

        :param target_width: Ширина изображения на сайте (None, если одинакова).
        :param gap: Для точного позиционирования решения.
        :return: Расстояние, на которое необходимо сместить слайдер.
        """
        if not self.matching_cords:
            raise CaptchaServiceException("Captcha not solved")
        if not self.target_img_resolution:
            raise CaptchaServiceException("Internal captcha solver error")
        result = self.matching_cords[0][0] + gap
        if target_width:
            return target_width * result // self.target_img_resolution.width
        return result

    def draw_result(self) -> None:
        """Отображает наглядно результат решения капчи. Для этого выводит целевое (фоновое) изображение
        и рисует прямоугольник вокруг найденного шаблона цветом (0, 255, 0) и толщиной 2 пикселя.

        :return: None
        """
        cv2.rectangle(self.target_img, *self.matching_cords, (0, 255, 0), 2)
        cv2.imshow("Matched Result", self.target_img)
        cv2.waitKey(0)
        cv2.destroyAllWindows()
