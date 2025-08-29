from pydash import filter_, get, map_

from src.config.app import SearchConfig


class ItemAdapter:
    @staticmethod
    def cast_item(item):
        _id = get(item, "id")

        if not _id:
            return None

        address = get(item, "address")
        address_comment = get(item, "additionalAddress")
        has_verified_owner = ItemAdapter._cast_boolean(
            get(item, "businessProperties.has_verified_owner")
        )

        categories = filter_(
            map_(
                get(item, "categories", []),
                lambda i: get(i, "name", "").capitalize(),
            ),
            lambda x: x,
        )

        seonames = filter_(
            [item.get("seoname")]
            + map_(
                get(item, "categories", []),
                lambda i: get(i, "seoname", ""),
            ),
            lambda x: x,
        )
        coordinates = (
            [get(item, "coordinates.1"), get(item, "coordinates.0")]
            if get(item, "coordinates.0")
            else None
        )
        country = get(item, "country")
        description = get(item, "description")
        description = description if description != address else None
        full_address = get(item, "fullAddress")
        full_address = full_address if full_address != address else None
        panorama = get(item, "panorama.preview")
        contacts = ItemAdapter._cast_contacts(item)
        images = ItemAdapter._cast_images(get(item, "photos.items", []))
        count_images = get(item, "photos.count")
        post_code = get(item, "postalCode")
        rating_general = round(get(item, "ratingData.ratingValue", 0), 2)
        rating_count = get(item, "ratingData.ratingCount")
        review_count = get(item, "ratingData.reviewCount")
        city = get(item, "region.names.nominative")
        name = get(item, "title")
        short_title = get(item, "shortTitle")
        short_title = short_title if short_title != name else None
        type = get(item, "type")
        schedule = get(item, "workingTimeText")
        url = ItemAdapter._cast_url(item)

        abilities = ItemAdapter._cast_abilities(item)
        links = ItemAdapter._cast_links(get(item, "socialLinks", []))
        business_images = ItemAdapter._cast_image(
            get(item, "businessImages.logo.urlTemplate")
        )
        load = ItemAdapter._cast_load(get(item, "histogramData", {}))

        has_stories = ItemAdapter._cast_boolean(get(item, "hasStories"))

        neuro_review = get(item, "neurosummaryData.review_text")
        stops = ItemAdapter._parse_stops(get(item, "stops", []))
        metro = ItemAdapter._parse_stops(get(item, "metro", []))
        urls = get(item, "urls", [])

        ads_article = get(item, "advert.promo.title")
        ads_text = get(item, "advert.promo.text")
        has_ads = "Есть" if ads_text else None
        ads_logo = get(item, "advert.logo")
        ads_url = get(item, "advert.promo.url")
        ads_banner = get(item, "advert.promo.banner")

        aspects = ItemAdapter._cast_aspects(get(item, "aspects", []))

        return {
            "_id": _id,
            "url_yandex": url,
            "name": name,
            "short_title": short_title,
            "description": description,
            "country": country,
            "city": city,
            "address": address,
            "address_comment": address_comment,
            "full_address": full_address,
            "post_code": post_code,
            "coordinates": coordinates,
            "list__categories": categories,
            "list__seoname": seonames,
            "abilities": abilities,
            "type": type,
            "schedule": schedule,
            "list__phone": contacts,
            **links,
            "list__url": urls,
            "panorama": panorama,
            "list__images": images,
            "count_images": count_images,
            "rating_general": rating_general,
            "rating_count": rating_count,
            "review_count": review_count,
            "neuro_review": neuro_review,
            "aspects": aspects,
            "stops": stops,
            "metro": metro,
            "load": load,
            "has_verified_owner": has_verified_owner,
            "has_stories": has_stories,
            "business_images": business_images,
            "has_commercial": has_ads,
            "commercial_article": ads_article,
            "commercial_text": ads_text,
            "commercial_logo": ads_logo,
            "commercial_url": ads_url,
            "commercial_banner": ads_banner,
        }

    @staticmethod
    def _cast_boolean(v):
        mapper = {True: "Да", False: "Нет", None: None}
        return mapper.get(v)

    @staticmethod
    def _cast_last_breadcrumb(item):
        return get(item, "breadcrumbs", [])[-1]

    @staticmethod
    def _cast_url(item):
        return get(ItemAdapter._cast_last_breadcrumb(item), "url")

    @staticmethod
    def _cast_contacts(item):
        return map_(get(item, "phones", []), lambda i: get(i, "value", get(i, "number")))

    @staticmethod
    def _cast_links(links):
        significant_link = [
            "vkontakte",
            "instagram",
            "facebook",
            "twitter",
            "youtube",
            "whatsapp",
            "viber",
            "telegram",
            "yandex_zen",
            "ok",
        ]
        result = {}
        for link in links:
            link_type = (
                "other_url" if link["type"] not in significant_link else link["type"]
            )
            key = "list__" + link_type
            if key in result:
                result[key].append(link["href"])
            else:
                result[key] = [link["href"]]
        return result

    @staticmethod
    def _cast_image(image):
        return image.replace("%s", "XXXL") if image else None

    @staticmethod
    def _cast_images(images):
        return filter_(
            map_(images, lambda i: ItemAdapter._cast_image(get(i, "urlTemplate", ""))),
            lambda i: i,
        )[: SearchConfig.MAX_PHOTOS_IN_ITEM]

    @staticmethod
    def _parse_stops(stops):
        if not stops:
            return None

        def cast_distance(d):
            return d["distance"].replace("\xa0", " ")

        return "\n".join(
            map_(stops, lambda stop: f'"{stop["name"]}" ({cast_distance(stop)});')
        )

    @staticmethod
    def _cast_abilities(item):
        features = get(item, "features", [])
        if not features:
            return None

        def cast_text(value, type):
            if type == "text":
                return value
            if type == "bool":
                return "Да" if value else "Нет"
            if type == "enum":
                return ", ".join(map_(value, lambda v: get(v, "name", "")))
            return str(value)

        text = ""
        for attr in features:
            feature_id = get(attr, "id")
            if feature_id == "media_order_tmpl":
                continue
            text += get(attr, "name", "Общее").capitalize() + ": "
            text += cast_text(get(attr, "value"), get(attr, "type"))

            text += ";\n"
        return text

    @staticmethod
    def _cast_load(load):
        if not load:
            return None

        def max_time(arr):
            s, text = sum(arr), []
            for time, v in enumerate(arr):
                if v:
                    text.append(f"{(time + 1):02d}:00 - {round(v / s * 100, 1)}%")
            return ", ".join(text)

        schedule_translate = {
            "monday": "Пн",
            "tuesday": "Вт",
            "wednesday": "Ср",
            "thursday": "Чт",
            "friday": "Пт",
            "saturday": "Сб",
            "sunday": "Вс",
        }

        text = ""
        for day_raw, day_translate in schedule_translate.items():
            text += f"{day_translate}: {max_time(get(load, day_raw))};\n"
        return text

    @staticmethod
    def _cast_aspects(aspects):
        if not aspects:
            return None

        text = ""
        for aspect in aspects:
            total_count = aspect.get("count", 0) - aspect.get("neutral", 0)
            rating = round(
                aspect.get("positive", 0) / total_count * 100, 1 if total_count else 0
            )
            text += f'{aspect.get("text")}: {rating}%;\n'
        return text
