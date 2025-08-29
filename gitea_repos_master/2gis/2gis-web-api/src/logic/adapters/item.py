from pydash import filter_, get, map_, uniq


class ItemAdapter:
    @staticmethod
    def cast_item(item):
        if not get(item, "address") and not get(item, "address_name"):
            return None

        _id = get(item, "id")

        address = get(item, "address_name")
        name = get(item, "name")
        name_ex = get(item, "name_ex.short_name")

        org_name = get(item, "org.name")
        org_name = None if org_name == name else org_name

        caption = get(item, "caption")
        caption = None if caption == name or caption == name_ex else caption

        address_comment = get(item, "address_comment")
        post_code = get(item, "address.postcode")
        building_name = get(item, "address.building_name")
        floors = get(item, "floors.ground_count")

        region = ", ".join(uniq(map_(get(item, "adm_div"), lambda x: get(x, "name"))))
        coordinates = (
            [get(item, "point.lat"), get(item, "point.lon")]
            if get(item, "point.lat")
            else None
        )

        ads_article = get(item, "ads.article")
        ads_text = get(item, "ads.text")
        has_ads = "Есть" if ads_text else None

        images = ItemAdapter._cast_image_preview(item)

        rating_general = get(item, "reviews.general_rating")
        rating_count = get(item, "reviews.general_review_count")

        updated_at = get(item, "dates.updated_at")
        is_deleted = "Да" if get(item, "dates.deleted_at") else None

        rubrics = ", ".join(
            filter_(
                map_(get(item, "rubrics"), lambda e: get(e, "name")),
                lambda x: '"' + get(x, "name", "") + '"',
            )
        )

        abilities = ItemAdapter._cast_abilities(item)
        contacts = ItemAdapter._cast_contacts(item)
        schedule = ItemAdapter._cast_schedule(item)
        url = ItemAdapter._cast_url(item)

        return {
            "_id": _id,
            "url": url,
            "name": name,
            "name_ex": name_ex,
            "org_name": org_name,
            "caption": caption,
            "region": region,
            "address": address,
            "post_code": post_code,
            "address_comment": address_comment,
            "building_name": building_name,
            "floors": floors,
            "coordinates": coordinates,
            "rubrics": rubrics,
            "abilities": abilities,
            "schedule": schedule,
            **contacts,
            "rating_count": rating_count,
            "rating_general": rating_general,
            "updated_at": updated_at,
            "is_deleted": is_deleted,
            "has_commercial": has_ads,
            "article_commercial": ads_article,
            "text_commercial": ads_text,
            "images": images,
        }

    @staticmethod
    def _cast_schedule(item):
        if not get(item, "schedule"):
            return None

        schedule_translate = {
            "Mon": "Пн",
            "Tue": "Вт",
            "Wed": "Ср",
            "Thu": "Чт",
            "Fri": "Пт",
            "Sat": "Сб",
            "Sun": "Вс",
        }

        schedule = []
        for day, day_translated in schedule_translate.items():
            schedule_o = f"{day_translated}: "

            if day in get(item, "schedule", {}):
                start = get(item, f"schedule.{day}.working_hours.0.from", "")
                end = get(item, f"schedule.{day}.working_hours.0.to")
                schedule_o += f"{start} - {end}"
            else:
                schedule_o += "-"

            schedule.append(schedule_o)

        return ",\n".join(schedule)

    @staticmethod
    def _cast_contacts(item):
        def cast_contact(x):
            type_ = get(x, "type")
            return {
                "type": type_,
                "value": get(x, "url") if type_ == "website" else get(x, "value"),
                "comment": get(x, "comment"),
            }

        result = []
        for group in get(item, "contact_groups", []):
            for contact in get(group, "contacts", []):
                result.append(cast_contact(contact))

        return {
            "list__vkontakte": ItemAdapter._get_contact(result, "vkontakte"),
            "list__website": ItemAdapter._get_contact(result, "website"),
            "list__fax": ItemAdapter._get_contact(result, "fax"),
            "list__facebook": ItemAdapter._get_contact(result, "facebook"),
            "list__twitter": ItemAdapter._get_contact(result, "twitter"),
            "list__email": ItemAdapter._get_contact(result, "email"),
            "list__youtube": ItemAdapter._get_contact(result, "youtube"),
            "list__whatsapp": ItemAdapter._parse_whatsapp(
                ItemAdapter._get_contact(result, "whatsapp")
            ),
            "list__viber": ItemAdapter._get_contact(result, "viber"),
            "list__telegram": ItemAdapter._get_contact(result, "telegram"),
            "list__phone": ItemAdapter._get_contact(result, "phone"),
        }

    @staticmethod
    def _get_contact(contacts, name):
        return map_(
            filter_(contacts, lambda r: get(r, "type") == name),
            lambda r: get(r, "value"),
        )

    @staticmethod
    def _parse_whatsapp(whatsapps):
        # Remove query params
        return [w[: w.find("?", 0)] for w in whatsapps]

    @staticmethod
    def _cast_abilities(item):
        text = ""
        for attr in get(item, "attribute_groups", []):
            text += get(attr, "name", "Общее") + ": "
            text += ", ".join(
                map_(
                    get(attr, "attributes"),
                    lambda x: '"' + get(x, "name", "Не указано") + '"',
                )
            )
            text += ";\n"
        return text

    @staticmethod
    def _cast_image_preview(item):
        image = get(item, "ads.options.images.0.url")
        if not image:
            return None
        return f"{image}346x240?api-version=2.0"

    @staticmethod
    def _cast_url(item):
        id_short = get(item, "id", "").split("_")[0]
        if not id_short:
            return None

        return f"https://2gis.ru/firm/{id_short}/"
