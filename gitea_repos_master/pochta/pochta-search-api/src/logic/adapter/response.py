from isphere_exceptions.success import NoDataEvent
from pydash import get


class ResponseAdapter:

    @staticmethod
    def cast(response):
        human_info = get(response, "human")
        if not human_info:
            raise NoDataEvent()

        address = get(human_info, "address")
        index = get(human_info, "index")
        name = get(human_info, "name")
        post_restante_address = get(human_info, "posteRestanteAddress")

        if post_restante_address == "до востребования":
            post_restante_address = None

        if not address or not index:
            raise NoDataEvent()

        return [
            {
                "Result": "Найден",
                "ResultCode": "FOUND",
                "Name": name,
                "Address": address,
                "Index": index,
                "PosteRestanteAddress": post_restante_address,
            }
        ]
