"""
Модуль содержит вспомогательные классы, которые обеспечивают читаемость кода.
"""


class Rectangle:
    """Описывает прямоугольник"""

    def __init__(self, height: int, width: int) -> None:
        """Конструктор класса.

        :param height: Высота прямоугольника.
        :param width: Ширина прямоугольника.
        """
        self.height = height
        self.width = width
