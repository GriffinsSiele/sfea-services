import logging

from isphere_exceptions.success import NoDataEvent
from pydash import filter_, get, map_, uniq


class ResponseAdapter:
    @staticmethod
    def cast(response):
        if not response:
            raise NoDataEvent("No output")

        name = get(response, "name")
        description = get(response, "description")
        user_definition = get(response, "userDefinition")

        websites = map_(
            get(response, "websites", []), lambda l: get(response, "websitesUrl")
        )
        avatar = get(response, "photoUrl")
        reviews = ResponseAdapter._parse_reviews(get(response, "reviews", []))
        address = ResponseAdapter._parse_address(get(response, "addresses", []))

        rating = get(response, "avgRating")
        rating = round(rating, 2) if rating is not None else rating

        facebook = get(response, "facebookID.id")
        facebook = facebook if not facebook else "https://facebook.com/" + facebook

        twitter = get(response, "twitterScreenName.id")
        twitter = twitter if not twitter else "https://twitter.com/" + twitter

        vkontakte = get(response, "vkID.id")
        vkontakte = vkontakte if not vkontakte else "https://vk.com/id" + vkontakte

        foursquare = get(response, "foursquareID.id")
        foursquare = (
            foursquare if not foursquare else "https://ru.foursquare.com/u/" + foursquare
        )

        pinterest = get(response, "pinterestID.id")
        pinterest = (
            pinterest if not pinterest else f"https://ru.pinterest.com/{pinterest}/"
        )

        linkedin = get(response, "linkedinID.id")
        linkedin = (
            linkedin
            if not linkedin
            else "http://www.linkedin.com/profile/view?id=" + linkedin
        )

        linkedin_url = get(response, "linkedinPubProfileUrl.id")
        linkedin = linkedin_url if linkedin_url else linkedin

        coordinates = (
            [get(response, "lat"), get(response, "lng")] if get(response, "lat") else None
        )

        url = get(response, "url")
        if url:
            websites.append(url)

        is_spam = ResponseAdapter._cast_boolean(get(response, "spamScore", 0) > 10)

        schedule = ResponseAdapter._cast_schedule(response)
        categories = ResponseAdapter._cast_categories(get(response, "categories", []))
        priority = get(response, "priority")

        google_maps = get(response, "googlePlacesId")
        google_maps = (
            google_maps
            if not google_maps
            else "https://www.google.com/maps/place/?q=place_id:" + google_maps
        )

        instagram_id = get(response, "instagramID.id")

        is_install_app = ResponseAdapter._cast_boolean(get(response, "installedApp"))

        emails = ResponseAdapter._cast_emails(get(response, "emails", []))
        price_level = ResponseAdapter._cast_price_level(get(response, "priceRange"))

        active_during_period = ResponseAdapter._cast_boolean(
            get(response, "activeDuringPeriod")
        )

        is_own_photo = ResponseAdapter._cast_boolean(
            get(response, "photoChosenFromUserProfile"), without_no=True
        )

        birthday = ResponseAdapter._cast_birthday(get(response, "birthday"))

        ResponseAdapter._check_extra_fields(response)
        return [
            {
                "name": name,
                "description": description,
                "user_definition": user_definition,
                "avatar": avatar,
                "is_own_photo": is_own_photo,
                "birthday": birthday,
                "list__email": emails,
                "facebook": facebook,
                "twitter": twitter,
                "vkontakte": vkontakte,
                "linkedin": linkedin,
                "foursquare": foursquare,
                "pinterest": pinterest,
                "instagram_id": instagram_id,
                "list__website": ResponseAdapter._uniq(websites),
                "list__address": address,
                "google_maps": google_maps,
                "coordinates": coordinates,
                "schedule": schedule,
                "categories": categories,
                "reviews": reviews,
                "rating": rating,
                "is_spam": is_spam,
                "price_level": price_level,
                "is_install_app": is_install_app,
                "active_during_period": active_during_period,
                "priority": priority,
            }
        ]

    @staticmethod
    def _cast_boolean(v, without_no=False):
        return "Да" if v else (None if without_no else "Нет")

    @staticmethod
    def _uniq(data):
        return filter_(uniq(data), lambda x: x)

    @staticmethod
    def _parse_address(address):
        return uniq(map_(address, lambda a: get(a, "street")))

    @staticmethod
    def _parse_reviews(reviews):
        if not reviews:
            return None

        return ";\n".join(map_(reviews, lambda r: get(r, "excerpt", "")))

    @staticmethod
    def _cast_schedule(item):
        if not get(item, "openingHours"):
            return None

        schedule_translate = {
            "monday": "Пн",
            "tuesday": "Вт",
            "wednesday": "Ср",
            "thursday": "Чт",
            "friday": "Пт",
            "saturday": "Сб",
            "sunday": "Вс",
        }

        schedule = []
        for day, day_translated in schedule_translate.items():
            time = None
            if day in get(item, "openingHours", {}):
                time = ", ".join(get(item, f"openingHours.{day}", []))

            schedule.append(f"{day_translated}: {time if time else '-'}")

        return ",\n".join(schedule)

    @staticmethod
    def _cast_categories(categories):
        if not categories:
            return None
        return ", ".join(
            ResponseAdapter._uniq(map_(categories, lambda x: get(x, "name")))
        )

    @staticmethod
    def _cast_emails(emails):
        if not emails:
            return None
        return ResponseAdapter._uniq(map_(emails, lambda e: get(e, "email")))

    @staticmethod
    def _check_extra_fields(response):
        known_fields = [
            "name",
            "description",
            "websites",
            "photoUrl",
            "reviews",
            "addresses",
            "avgRating",
            "facebookID",
            "twitterScreenName",
            "vkID",
            "lat",
            "lng",
            "url",
            "spamScore",
            "openingHours",
            "categories",
            "priority",
            "googlePlusID",  # Google+ not working
            "googlePlacesId",
            "instagramID",
            "huaweiPlacesId",  # IDK what is field
            "installedApp",
            "emails",
            "priceRange",
            "activeDuringPeriod",
            "photoChosenFromUserProfile",
            "linkedinID",
            "birthday",
            "linkedinPubProfileUrl",
            "type",  # IDK what is field
            "userDefinition",
            "foursquareID",
            "pinterestID",
            "facebookPlacesId",  # skipped
        ]

        for key, value in response.items():
            if key not in known_fields:
                logging.info(f"Extra field detected: [{key}]: {value}")

    @staticmethod
    def _cast_price_level(level):
        if not level:
            return None

        translate = {
            0: "Бесплатное",
            1: "Недорогое",
            2: "Умеренное",
            3: "Дорогое",
            4: "Очень дорогое",
            14: "Очень дорогое",
        }
        if level not in translate:
            logging.error(f"priceRange [{level}] has unexpected value")
            return None

        return translate[level]

    @staticmethod
    def _cast_birthday(v):
        if not v:
            return None
        year = get(v, "formattedYear", "")
        month = str(get(v, "formattedMonth", "")).zfill(2)
        day = str(get(v, "formattedDay", "")).zfill(2)
        return f"{day}.{month}.{year}"
