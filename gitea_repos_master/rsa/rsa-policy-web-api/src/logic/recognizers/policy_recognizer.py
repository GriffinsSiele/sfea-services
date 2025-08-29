from src.config.configuration import AssociateConfig
from src.config.custom_types import (
    HeadResult,
    PolicyAsociateBody,
    PolicyPosition,
    RecognizePolicy,
)
from src.logic.recognizers.image_recognizer import ImageRecognizer
from src.logic.recognizers.recognize_associate import RecognizeAssociate

POLICY_HEADERS_VALUE = AssociateConfig.policy_headers_value


class PolicyRecognizer:

    def __init__(self):
        self.policy_headers_value = POLICY_HEADERS_VALUE
        self.image_recognizer = ImageRecognizer()
        self.recognize_associate = RecognizeAssociate()

    def __recognize_header(self, policy_header) -> HeadResult:
        policy_recognize_header = self.image_recognizer.recognize_policy_header(
            policy_header
        )
        associate_header, no_associate_header = (
            self.recognize_associate.policy_head_associate(policy_recognize_header)
        )
        return associate_header, no_associate_header

    def __recognize_body(
        self, policy_body, policy_associate_header
    ) -> PolicyAsociateBody:
        recognize_body = self.image_recognizer.recognize_policy_body(
            policy_body, policy_associate_header
        )
        associate_body = self.recognize_associate.policy_body_associate(
            recognize_body, policy_associate_header
        )
        return associate_body

    def policy_recognize(self, policy_key_value: PolicyPosition) -> RecognizePolicy:
        policy_recognize_result = {}
        policy_data = list(policy_key_value.values())
        policy_header, policy_body = zip(*policy_data)
        policy_associate_header, no_associate_header = self.__recognize_header(
            policy_header
        )
        policy_associate_body = self.__recognize_body(
            policy_body, policy_associate_header
        )
        recognize_result = {
            self.policy_headers_value[value].value[0]: policy_associate_body[key]
            for key, value in policy_associate_header.items()
        }
        if no_associate_header:
            header_without_value = {
                self.policy_headers_value[key].value[0]: "" for key in no_associate_header
            }
            recognize_result.update(header_without_value)
        policy_recognize_result.update(recognize_result)
        return policy_recognize_result
