from src.config.configuration import AssociateConfig
from src.config.custom_types import (
    BodyPosition,
    HeadResult,
    ImagePosition,
    RecognizeBody,
    RecognizeHeader,
    RecognizeResult,
)
from src.logic.recognizers.image_recognizer import ImageRecognizer
from src.logic.recognizers.recognize_associate import RecognizeAssociate

HEADERS_VALUE = AssociateConfig.headers_value


class OutsideRecognizer:

    def __init__(self):
        self.headers_value = HEADERS_VALUE
        self.image_recognizer = ImageRecognizer()
        self.recognize_associate = RecognizeAssociate()

    def __recognize_header(self, header_values: ImagePosition) -> HeadResult:
        recognize_header = self.image_recognizer.recognize_header(header_values)
        associate_header, no_associate_header = self.recognize_associate.head_associate(
            recognize_header
        )
        return associate_header, no_associate_header

    def __recognize_body(
        self, body_values: BodyPosition, associate_header: RecognizeHeader
    ) -> RecognizeBody:
        recognize_body = self.image_recognizer.recognize_body(
            body_values, associate_header
        )
        associate_body = self.recognize_associate.body_associate(
            recognize_body, associate_header
        )
        return associate_body

    def outside_recognize(
        self, header_values: ImagePosition, body_values: BodyPosition
    ) -> RecognizeResult:
        outside_recognize_result = {}
        associate_header, no_associate_header = self.__recognize_header(header_values)
        associate_body = self.__recognize_body(body_values, associate_header)
        recognize_result = {
            self.headers_value[associate_header[key]].value[0]: value
            for key, value in associate_body.items()
        }
        if no_associate_header:
            header_without_value: RecognizeResult = {
                self.headers_value[key].value[0]: ["" for _ in associate_body[1]]
                for key in no_associate_header
            }
            recognize_result.update(header_without_value)
        outside_recognize_result.update(recognize_result)

        return outside_recognize_result
