from isphere_exceptions.success import NoDataEvent
from pydash import get


class ResponseAdapter:
    @staticmethod
    def cast(response):
        cas_id = get(response, "casId")
        if not cas_id:
            raise NoDataEvent()

        first_name = get(response, "firstName")
        middle_name = get(response, "middleName")
        last_name = get(response, "lastName")

        is_partner = ResponseAdapter.cast_boolean(bool(get(response, "partnerCard")))
        partner_link = (
            f"https://agencies.domclick.ru/agent/{cas_id}"
            if get(response, "partnerCard")
            else None
        )

        avatar = get(response, "partnerCard.photoUrl")
        client_review = get(response, "partnerCard.clientReview")
        registered_at = get(response, "partnerCard.registeredAt")
        deals_count = get(response, "partnerCard.dealsCount")
        client_comments_count = get(response, "partnerCard.clientCommentsCount")

        return [
            {
                "first_name": first_name,
                "middle_name": middle_name,
                "last_name": last_name,
                "user_id": int(cas_id),
                "is_registered": ResponseAdapter.cast_boolean(bool(cas_id)),
                "is_partner": is_partner,
                "avatar": avatar,
                "partner_link": partner_link,
                "client_review": client_review,
                "registered_at": registered_at,
                "deals_count": deals_count,
                "client_comments_count": client_comments_count,
            }
        ]

    @staticmethod
    def cast_boolean(v):
        return "Да" if v else "Нет" if v is False else v
