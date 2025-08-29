from src.config.configuration import HeadersValue, InsideHeadersValue, PolicyHeaderValue
from src.functions.validation_functions import ValidateFunctions

min_byte_size = 40000

min_byte_policy = 10000

allowed_types = ["image/jpeg", "image/png", "image/gif"]

unusual_validation = {
    HeadersValue.kbm.value[0]: (ValidateFunctions.validate_float, ("Kbm",)),
    InsideHeadersValue.car_model.value[0]: (
        ValidateFunctions.validate_car_model,
        ("Model", "Category"),
    ),
    InsideHeadersValue.engine_power.value[0]: (
        ValidateFunctions.validate_float,
        ("Power",),
    ),
    HeadersValue.insurance_premium.value[0]: (
        ValidateFunctions.validate_insurance_premium,
        ("Total",),
    ),
    HeadersValue.contract_restrictions.value[0]: (
        ValidateFunctions.validate_contract_restrictions,
        ("Limited", "Drivers"),
    ),
    HeadersValue.car_owner.value[0]: (
        ValidateFunctions.validate_person_organisation,
        ("Owner", "OwnerBirthDate", "OwnerINN"),
    ),
    HeadersValue.policyholder_inform.value[0]: (
        ValidateFunctions.validate_person_organisation,
        ("Insurant", "InsurantBirthDate", "InsurantINN"),
    ),
}

usual_validation = {
    InsideHeadersValue.vin.value[0]: "VIN",
    HeadersValue.moving_in.value[0]: "Transit",
    HeadersValue.use_region.value[0]: "Region",
    HeadersValue.trailer_use.value[0]: "Trailer",
    HeadersValue.using_purpose.value[0]: "Purpose",
    HeadersValue.duration_period.value[0]: "Active",
    HeadersValue.insurance_company.value[0]: "Company",
    HeadersValue.serial_number.value[0]: "PolicyNumber",
    InsideHeadersValue.corpus_number.value[0]: "BodyNum",
    HeadersValue.contract_status.value[0]: "AgreementStatus",
    InsideHeadersValue.chassis_number.value[0]: "ChassisNum",
    InsideHeadersValue.registration_plate.value[0]: "RegNum",
    InsideHeadersValue.car_weight.value[0]: "CarWeight",
}


policy_usual_validation = {
    PolicyHeaderValue.policy_serial.value[0]: "Serial",
    PolicyHeaderValue.policy_number.value[0]: "Number",
    PolicyHeaderValue.change_date.value[0]: "PolicyDate",
    PolicyHeaderValue.insurance_company.value[0]: "Company",
    PolicyHeaderValue.policy_status.value[0]: "PolicyStatus",
}
