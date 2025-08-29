from enum import Enum

from pydash import map_

from src.logic.validator import Validator


class SearchFields(Enum):
    VIN = 'vin'
    GOS_NUMBER = 'gosNumber'
    BODY_NUMBER = 'bodyNumber'
    CHASSIS_NUMBER = 'chassisNumber'

    @classmethod
    def list(cls):
        return map_(cls, lambda c: c.value)


class SeleniumState(Enum):
    CREATED = 'CREATEAD'
    READY = 'READY'
    IN_PROGRESS = 'IN_PROGRESS'
    BAN = 'BAN'
    UNKNOWN = 'UNKNOWN'


field_to_id = {
    SearchFields.VIN: 'vin',
    SearchFields.GOS_NUMBER: 'licensePlate',
    SearchFields.BODY_NUMBER: 'bodyNumber',
    SearchFields.CHASSIS_NUMBER: 'chassisNumber'
}

field_to_validator = {
    SearchFields.VIN: Validator.validate_vin,
    SearchFields.GOS_NUMBER: Validator.validate_gos_number,
    SearchFields.BODY_NUMBER: Validator.validate_body_number,
    SearchFields.CHASSIS_NUMBER: Validator.validate_chassis_number
}
