import enum
import os

from src.config.custom_types import BodyConfig, HeaderConfig
from src.functions.recognize_functions import RecognizeFunctions
from src.functions.standartization_functions import StandardizationFunctions


class SentryConfig:
    url: str = os.getenv("SENTRY_URL", "")
    mode: str = "dev"


class ImageConfig:
    img_min_width: int = 2000
    img_min_height: int = 150
    policy_min_width: int = 1000
    policy_min_height: int = 150
    pix_offset_min: int = 57
    pix_offset_max: int = 100
    img_extension: str = ".jpg"


class RecognizerConfig:
    tesseract_path: str = "//bin/tesseract"
    standard_recognize = RecognizeFunctions.recognize
    override_columns_rules: dict = {"position": 1, "value": "№"}
    min_number_width: int = 100
    side_length: int = 74


class PolicyConfig:
    min_table_border: int = 35
    max_table_border: int = 180


class ColorsConfig:
    black: int = 0
    grey: int = 128
    white: int = 255
    border_width: int = 30


class HeadersValue(enum.Enum):
    number: HeaderConfig = ("№", RecognizeFunctions.recognize_number)
    kbm: HeaderConfig = ("КБМ по договору ОСАГО", RecognizeFunctions.recognize_rus)
    insurance_premium: HeaderConfig = (
        "Страховая премия",
        RecognizeFunctions.recognize_rus,
    )
    contract_status: HeaderConfig = (
        "Статус договора ОСАГО",
        RecognizeFunctions.recognize_rus,
    )
    serial_number: HeaderConfig = (
        "Серия и номер договора ОСАГО",
        RecognizeFunctions.recognize_rus,
    )
    insurance_company: HeaderConfig = (
        "Наименование страховой организации",
        RecognizeFunctions.recognize_rus,
    )
    using_purpose: HeaderConfig = (
        "Цель использования транспортного средства",
        RecognizeFunctions.recognize_rus,
    )
    use_region: HeaderConfig = (
        "Транспортное средство используется в регионе",
        RecognizeFunctions.recognize_rus,
    )
    trailer_use: HeaderConfig = (
        "Управление транспортным средством с прицепом",
        RecognizeFunctions.recognize_rus,
    )
    car_owner: HeaderConfig = (
        "Сведения о собственнике транспортного средства",
        RecognizeFunctions.owner_recognize,
    )
    policyholder_inform: HeaderConfig = (
        "Сведения о страхователе транспортного средства",
        RecognizeFunctions.owner_recognize,
    )
    duration_period: HeaderConfig = (
        "Срок действия и период использования транспортного средства договора осаго",
        RecognizeFunctions.recognize_rus,
    )
    moving_in: HeaderConfig = (
        "Транспортное средство следует к месту регистрации или к месту проведения технического осмотра",
        RecognizeFunctions.recognize_rus,
    )
    contract_restrictions: HeaderConfig = (
        "Договор ОСАГО с ограничениями/без ограничений лиц, допущенных к управлению транспортным средством",
        RecognizeFunctions.recognize_rus,
    )


class InsideHeadersValue(enum.Enum):
    vin: HeaderConfig = ("VIN", RecognizeFunctions.recognize_vin)
    corpus_number: HeaderConfig = ("Номер кузова", RecognizeFunctions.recognize_vin)
    chassis_number: HeaderConfig = ("Номер шасси", RecognizeFunctions.recognize_vin)
    engine_power: HeaderConfig = (
        "Мощность двигателя для категории В, л.с.",
        RecognizeFunctions.recognize_rus,
    )
    registration_plate: HeaderConfig = (
        "Государственный регистрационный знак",
        RecognizeFunctions.recognize_plate,
    )
    car_weight: HeaderConfig = (
        "Максимальная разрешенная масса для категории С, кг",
        RecognizeFunctions.recognize_rus,
    )
    car_model: HeaderConfig = (
        'Марка и модель транспортного средства (категория "X")',
        RecognizeFunctions.auto_recognize,
    )


class PolicyHeaderValue(enum.Enum):
    policy_serial: HeaderConfig = (
        "Серия полиса",
        RecognizeFunctions.recognize_rus,
    )
    policy_number: HeaderConfig = (
        "Номер полиса",
        RecognizeFunctions.recognize_rus,
    )
    change_date: HeaderConfig = (
        "Дата изменения статуса полиса",
        RecognizeFunctions.recognize_rus,
    )
    insurance_company: HeaderConfig = (
        "Наименование страховой организации",
        RecognizeFunctions.recognize_rus,
    )
    policy_status: HeaderConfig = (
        "Статус полиса",
        RecognizeFunctions.recognize_rus,
    )


class BodiesValue(enum.Enum):
    kbm: BodyConfig = ({}, StandardizationFunctions.std_kbm)
    number: BodyConfig = ({}, StandardizationFunctions.str_return)
    car_owner: BodyConfig = ({}, StandardizationFunctions.std_owner)
    use_region: BodyConfig = ({}, StandardizationFunctions.str_return)
    serial_number: BodyConfig = ({}, StandardizationFunctions.std_serial_number)
    insurance_premium: BodyConfig = ({}, StandardizationFunctions.std_premium)
    policyholder_inform: BodyConfig = ({}, StandardizationFunctions.std_owner)
    insurance_company: BodyConfig = ({}, StandardizationFunctions.std_company)
    trailer_use: BodyConfig = ({1: "Да", 2: "Нет"}, StandardizationFunctions.str_std)
    moving_in: BodyConfig = (
        {1: "Сведения отсутствуют", 2: "Нет"},
        StandardizationFunctions.str_std,
    )
    duration_period: BodyConfig = (
        {
            1: "Период использования ТС не активен на запрашиваемую дату",
            2: "Период использования ТС активен на запрашиваемую дату",
        },
        StandardizationFunctions.str_std,
    )
    contract_restrictions: BodyConfig = (
        {
            1: "Ограничен список лиц, допущенных к управлению (допущено: Х чел.)",
            2: "Не ограничен список лиц, допущенных к управлению",
        },
        StandardizationFunctions.std_restrictions,
    )
    contract_status: BodyConfig = (
        {1: "Прекратил действие", 2: "Действует", 3: "Не начался срок страхования"},
        StandardizationFunctions.str_std,
    )
    using_purpose: BodyConfig = (
        {
            1: "Личная",
            2: "Учебная езда",
            3: "Такси",
            4: "Перевозка опасных и легко воспламеняющихся грузов",
            5: "Прокат/краткосрочная аренда",
            6: "Регулярные пассажирские перевозки/перевозки пассажиров по заказам",
            7: "Дорожные и специальные транспортные средства",
            8: "Экстренные и коммунальные службы",
            9: "Прочее",
        },
        StandardizationFunctions.str_std,
    )


class InsideBodiesValue(enum.Enum):
    vin: BodyConfig = ({}, StandardizationFunctions.std_vin)
    car_model: BodyConfig = ({}, StandardizationFunctions.std_auto)
    car_weight: BodyConfig = ({}, StandardizationFunctions.str_return)
    engine_power: BodyConfig = ({}, StandardizationFunctions.std_power)
    corpus_number: BodyConfig = ({}, StandardizationFunctions.std_corpus)
    chassis_number: BodyConfig = ({}, StandardizationFunctions.std_corpus)
    registration_plate: BodyConfig = (
        {1: "Сведения отсутствуют"},
        StandardizationFunctions.std_plate,
    )


class PolicyBodiesValue(enum.Enum):
    policy_serial: BodyConfig = ({}, StandardizationFunctions.std_serial)
    policy_number: BodyConfig = ({}, StandardizationFunctions.std_number)
    change_date: BodyConfig = ({}, StandardizationFunctions.std_date)
    insurance_company: BodyConfig = ({}, StandardizationFunctions.str_return)
    policy_status: BodyConfig = (
        {
            1: "Выдан страхователю",
            2: "Не подлежит использованию по причине замены полиса при изменении условий договора ОСАГО или его досрочного прекращения",
        },
        StandardizationFunctions.str_std,
    )


class AssociateConfig:
    similar_limit: float = 0.7
    headers_value = HeadersValue
    inside_headers_value = InsideHeadersValue
    policy_headers_value = PolicyHeaderValue
    bodies_value = BodiesValue
    inside_bodies_value = InsideBodiesValue
    policy_bodies_value = PolicyBodiesValue
