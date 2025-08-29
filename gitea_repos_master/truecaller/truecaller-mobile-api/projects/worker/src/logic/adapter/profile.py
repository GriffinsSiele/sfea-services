from isphere_exceptions.success import NoDataEvent
from pydash import filter_, get, map_

from src.logic.adapter.tags import global_tags


class ProfileAdapter:
    @staticmethod
    def cast(data):
        data = get(data, "data.0")
        if not data:
            raise NoDataEvent("Body does not contain profile")

        name = get(data, "name")
        if not name:
            raise NoDataEvent("Response does not contain name of profile")

        if name == "Likely Spam":
            raise NoDataEvent("No useful information returned")

        alternative_name = get(data, "altName")
        score = get(data, "score")
        gender = ProfileAdapter.__cast_gender(get(data, "gender"))
        about = get(data, "about")
        birthday = get(data, "birthday")
        avatar = get(data, "image")
        job_title = get(data, "jobTitle")
        company = get(data, "companyName")

        is_install_app = ProfileAdapter.__cast_boolean(get(data, "imId"))

        phone_number_type = ProfileAdapter.__cast_phone_line(
            get(data, "phones.0.numberType")
        )
        country = get(data, "phones.0.countryCode")
        operator = get(data, "phones.0.carrier")

        extra_phones = map_(get(data, "phones", [])[1:], lambda p: p["e164Format"])

        badges = ", ".join(get(data, "badges", []))
        tags = ProfileAdapter.__cast_tags(get(data, "tags", []))

        comments_count = get(data, "commentsStats.count")
        comments_exists = ProfileAdapter.__cast_boolean(
            get(data, "commentsStats.showComments")
        )

        spam_score = get(data, "spamInfo.spamScore")
        spam_category = get(data, "spamInfo.spamType")

        search_warnings = ", ".join(
            map_(get(data, "searchWarnings", []), lambda x: get(x, "ruleName"))
        )

        address = ProfileAdapter.__cast_address(get(data, "addresses", []))
        links = ProfileAdapter.__cast_links(get(data, "internetAddresses", []))

        return [
            {
                "Name": name,
                "avatar": avatar,
                "alternative_name": alternative_name,
                "phone_number_type": phone_number_type,
                "country_code": country,
                "phone_number_operator": operator,
                "list__extra_phone": extra_phones,
                "badges": badges,
                "score": score,
                "is_install_app": is_install_app,
                "gender": gender,
                "about": about,
                "birthday": birthday,
                "job_title": job_title,
                "company": company,
                "search_warnings": search_warnings,
                "list__address": address,
                **links,
                "tags": tags,
                "spam_score": spam_score,
                "spam_category": spam_category,
                "comments_count": comments_count,
                "comments_exists": comments_exists,
            }
        ]

    @staticmethod
    def __cast_boolean(v):
        return "Да" if v else "Нет"

    @staticmethod
    def __cast_gender(g):
        mapper = {"MALE": "Мужской", "FEMALE": "Женский"}
        return mapper[g] if g in mapper else "Неизвестно"

    @staticmethod
    def __cast_phone_line(v: str):
        mapper = {
            "FIXED_LINE": "Фиксированная линия",
            "MOBILE": "Мобильный",
            "FIXED_LINE_OR_MOBILE": "Фиксированная линия или мобильный",
            "TOLL_FREE": "Бесплатная (горячая) линия",
            "PREMIUM_RATE": "Аудиотекс",
            "SHARED_COST": "Телефон с раздельной платой",
            "VOIP": "VOIP",
            "PERSONAL_NUMBER": "Личный номер",
            "PAGER": "Пейджер",
            "UAN": "UAN",
            "VOICEMAIL": "Голосовая почта",
        }
        return mapper[v] if v in mapper else "Неизвестно"

    @staticmethod
    def __cast_address(addresses):
        output = []
        for address in addresses:
            options = [
                get(address, "countryCode"),
                get(address, "zipCode"),
                get(address, "area"),
                get(address, "city"),
                get(address, "street"),
            ]
            options = filter_(options, lambda x: x)
            if len(options) > 1:
                output.append(", ".join(options))

        return output

    @staticmethod
    def __cast_links(links):
        output = {}
        for link in links:
            key = "list__" + link["service"]
            if key in output:
                output[key].append(link["id"])
            else:
                output[key] = [link["id"]]
        return output

    @staticmethod
    def __cast_tags(tags):
        output = ""
        for tag in tags:
            tag = int(tag)
            output += (global_tags[tag] + "\n") if tag in global_tags else ""
        return output
