from queue_logic.answer import KeyDBAnswer
from src.chemas import HLRStatus, Types


def prepare_data(playload):
    def add_subfield(code):
        if code in HLRStatus.OPERATOR_ERRORS:
            data.add_field(field_name="HLRSubStatus",
                           value=HLRStatus.OPERATOR_ERRORS[code],
                           description='Дополнение',
                           field_type='string')

    data = KeyDBAnswer()
    try:
        if playload:
            to, status, error_code = playload
            data.dst['code'] = 200
            data.add_record()
            data.add_field(field_name="PhoneNumber", description="Номер телефона", field_type="string", value=to)
            if error_code == 15:
                status = Types.DELIVERED
                add_subfield(error_code)
            if status in HLRStatus.IN_NETWORK:
                data.add_field(field_name="HLRStatus", value='Доступен', description="Статус", field_type="string")
            elif status in HLRStatus.OUT_NETWORK:
                data.add_field(field_name="HLRStatus", value='Не доступен', description="Статус", field_type="string")
                add_subfield(error_code)
            else:
                data.add_field(field_name="HLRStatus",
                               value='Ошибка, неправильные параметры запроса',
                               description="Статус",
                               field_type="string")
                data.dst['code'] = 500

            data.finish_record()
    except:
        pass
    return data.get_dst()