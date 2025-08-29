import copy
import logging

from jellyfish import jaro_winkler_similarity

from src.config.configuration import AssociateConfig
from src.config.custom_types import (
    HeadResult,
    InsideAssociate,
    InsideValues,
    PolicyAsociateBody,
    RecognizeBody,
    RecognizeHeader,
    SimilarityKey,
    UnrecognizedHeader,
)

HEADERS_VALUE = AssociateConfig.headers_value
INSHEADERS_VALUE = AssociateConfig.inside_headers_value
POLICY_HEADER_VALUE = AssociateConfig.policy_headers_value
INSBODY_VALUE = AssociateConfig.inside_bodies_value
BODIES_VALUE = AssociateConfig.bodies_value
POLICY_BODY_VALUE = AssociateConfig.policy_bodies_value
SIMILAR_LIMIT = AssociateConfig.similar_limit


class RecognizeAssociate:
    def __init__(self):
        self.bodies_value = BODIES_VALUE
        self.headers_value = HEADERS_VALUE
        self.similar_limit = SIMILAR_LIMIT
        self.insheaders_value = INSHEADERS_VALUE
        self.insbodies_value = INSBODY_VALUE
        self.policy_headers_value = POLICY_HEADER_VALUE
        self.policy_bodies_value = POLICY_BODY_VALUE

    def __similarity_value(self, r_value: str, value: str) -> float:
        return jaro_winkler_similarity(r_value, value)

    def __check_similarity(
        self, verification_dictionary: dict, check_value: str
    ) -> SimilarityKey:
        max_metric = 0.0
        max_key = None
        for verification_key, verification_value in verification_dictionary.items():
            similar_metric: float = self.__similarity_value(
                verification_value, check_value
            )
            if similar_metric == 1:
                max_metric = similar_metric
                max_key = verification_key
                break
            else:
                if similar_metric > max_metric and similar_metric > self.similar_limit:
                    max_metric = similar_metric
                    max_key = verification_key
        return max_key

    def head_associate(self, recognize_head: RecognizeHeader) -> HeadResult:

        associate_header: RecognizeHeader = {}
        no_associate_header: UnrecognizedHeader = {}
        check_head = dict(copy.deepcopy(recognize_head))

        for head in self.headers_value:
            key_name: str = head.name
            value = str(head.value[0])
            if check_head:
                max_key: SimilarityKey = self.__check_similarity(
                    verification_dictionary=check_head, check_value=value
                )
                if max_key:
                    associate_header[max_key] = key_name
                    del check_head[max_key]
                else:
                    logging.warning(f'! No match was found for outside header "{value}"')
                    no_associate_header[key_name] = ""
            else:
                logging.warning(f'! No match was found for outside header "{value}"')
                no_associate_header[key_name] = ""
        if check_head:
            logging.warning(
                f'! The following keys could not be associated: "{list(recognize_head.keys())}"'
            )
        return associate_header, no_associate_header

    def body_associate(
        self, body_recognize: RecognizeBody, associate_header: RecognizeHeader
    ) -> RecognizeBody:
        associate_body = dict(copy.deepcopy(body_recognize))
        for dict_key, name in associate_header.items():
            r_bodies = body_recognize[dict_key]
            for i in range(len(r_bodies)):
                r_body = r_bodies[i]
                std_body = self.bodies_value[name].value[0]
                std_function = self.bodies_value[name].value[1]
                if std_body:
                    max_key: SimilarityKey = self.__check_similarity(
                        verification_dictionary=std_body, check_value=r_body
                    )
                    if max_key:
                        standardized_body = std_function(r_body, std_body[max_key])
                        associate_body[dict_key][i] = standardized_body

                    else:
                        associate_body[dict_key][i] = r_body
                else:

                    standardized_body = std_function(r_body, "")
                    associate_body[dict_key][i] = standardized_body
        return associate_body

    def insidehead_associate(self, recognize_inshead: InsideValues) -> InsideAssociate:
        associate_inshead = list(copy.deepcopy(recognize_inshead))
        for i in range(len(associate_inshead)):
            check_inshead = associate_inshead[i]
            for ins_head in self.insheaders_value:
                name: str = ins_head.name
                value = str(ins_head.value[0])
                max_key = self.__check_similarity(check_inshead, value)
                if max_key:
                    check_inshead[max_key] = name
                else:
                    logging.warning(
                        f'! No match was found for inside head "{value}" for policy №{i} '
                    )
            associate_inshead[i] = check_inshead
        return associate_inshead

    def insidebody_associate(
        self, recognize_insbody: InsideValues, associate_inshead: InsideAssociate
    ) -> InsideAssociate:
        associate_insbody = list(copy.deepcopy(recognize_insbody))
        for i in range(len(associate_insbody)):
            check_insbody = associate_insbody[i]
            check_inshead = associate_inshead[i]
            for dict_key, name in check_inshead.items():
                r_insbody = check_insbody[dict_key]
                std_body = self.insbodies_value[name].value[0]
                std_function = self.insbodies_value[name].value[1]
                if std_body:
                    max_key: SimilarityKey = self.__check_similarity(
                        verification_dictionary=std_body, check_value=r_insbody
                    )
                    if max_key:
                        standardized_insbody = std_function(r_insbody, std_body[max_key])
                        check_insbody[dict_key] = standardized_insbody
                    else:
                        standardized_insbody = std_function(r_insbody, "")
                        check_insbody[dict_key] = standardized_insbody
                else:
                    standardized_insbody = std_function(r_insbody, "")
                    check_insbody[dict_key] = standardized_insbody
            associate_insbody[i] = check_insbody
        return associate_insbody

    def policy_head_associate(self, recognize_head: RecognizeHeader) -> HeadResult:

        associate_header: RecognizeHeader = {}
        no_associate_header: UnrecognizedHeader = {}
        check_head = dict(copy.deepcopy(recognize_head))
        for head in self.policy_headers_value:
            key_name: str = head.name
            value = str(head.value[0])
            if check_head:
                max_key: SimilarityKey = self.__check_similarity(
                    verification_dictionary=check_head, check_value=value
                )
                if max_key:
                    associate_header[max_key] = key_name
                    del check_head[max_key]
                else:
                    logging.warning(f'! No match was found for policy header "{value}"')
                    no_associate_header[key_name] = ""
            else:
                logging.warning(f'! No match was found for policy header "{value}"')
                no_associate_header[key_name] = ""
        if check_head:
            logging.warning(
                f'! The following keys could not be associated: "{list(recognize_head.keys())}"'
            )
        return associate_header, no_associate_header

    def policy_body_associate(
        self, recognize_body, policy_associate_header
    ) -> PolicyAsociateBody:

        associate_body = dict(copy.deepcopy(recognize_body))
        for dict_key, name in policy_associate_header.items():
            r_body = recognize_body[dict_key]

            std_body = self.policy_bodies_value[name].value[0]
            std_function = self.policy_bodies_value[name].value[1]
            if std_body:
                max_key: SimilarityKey = self.__check_similarity(
                    verification_dictionary=std_body, check_value=r_body
                )
                if max_key:
                    standardized_body = std_function(r_body, std_body[max_key])
                    associate_body[dict_key] = standardized_body

                else:
                    associate_body[dict_key] = r_body
            else:

                standardized_body = std_function(r_body, "")
                associate_body[dict_key] = standardized_body

        return associate_body
