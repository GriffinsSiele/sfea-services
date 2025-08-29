from isphere_exceptions.success import NoDataEvent
from pydash import find, get, pick


class ResponseAdapter:
    @staticmethod
    def cast_many(response, phones):
        response = get(response, "result", [])

        output = {}
        for index, phone in enumerate(phones):
            response_phone = find(response, lambda p: get(p, "index") == index)
            output[phone] = ResponseAdapter.cast_one(response_phone)

        return output

    @staticmethod
    def cast(response):
        return [ResponseAdapter.cast_one(get(response, "result.0"))]

    @staticmethod
    def cast_one(response):
        success = get(response, "success")
        if not response or not success:
            raise NoDataEvent("No user")

        spam = get(response, "spam")
        spam = "Да" if spam else "Нет"

        emails = get(response, "emails")
        emails = emails if emails else None

        return {
            **pick(response, "full_name", "company_name", "job_title"),
            "list__email": emails,
            "spam": spam,
        }
