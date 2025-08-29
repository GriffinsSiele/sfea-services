from datetime import datetime
from typing import Any, Dict, List, Literal, Optional, TypedDict

from pydash import filter_, get, map_
from typing_extensions import NotRequired

StrBoolean = Literal["Да", "Нет", None]
Gender = Literal["Мужской", "Женский", "Неизвестно"]


class ResponseItem(TypedDict):
    url_profile: NotRequired[str]
    name: NotRequired[str]
    first_name: NotRequired[str]
    last_name: NotRequired[str]
    shortname: NotRequired[str]
    bio: NotRequired[str]
    age: NotRequired[int]
    birthday: NotRequired[str]
    birthday_set: StrBoolean
    gender: Gender
    locale: NotRequired[int]
    relationship: NotRequired[str]
    location_city: NotRequired[str]
    location_country: NotRequired[str]
    location_of_birth_city: NotRequired[str]
    location_of_birth_country: NotRequired[str]


class ProfileAdapter:
    @staticmethod
    def cast(profile: Any) -> List[ResponseItem]:
        access_level_age = get(profile, "access_levels.AGE_VISIBILITY")
        access_level_feed = get(profile, "access_levels.FEED_VISIBILITY")
        access_level_now = get(profile, "access_levels.ON_SITE_NOW_VISIBILITY")
        access_level_video = get(profile, "access_levels.VIDEO_VISIBILITY")

        accessible = ProfileAdapter._cast_boolean(get(profile, "accessible"))
        age = get(profile, "age")
        allows_anonym_access = ProfileAdapter._cast_boolean(
            get(profile, "allows_anonym_access")
        )
        allows_messaging_only_for_friends = ProfileAdapter._cast_boolean(
            get(profile, "allows_messaging_only_for_friends")
        )
        bio = get(profile, "bio")
        birthday = get(profile, "birthday")

        birthday_set = ProfileAdapter._cast_boolean(get(profile, "birthdaySet"))
        blocked = ProfileAdapter._cast_boolean(get(profile, "blocked"))

        counter_applications = get(profile, "counters.applications")
        counter_friends = get(profile, "counters.friends")
        counter_groups = get(profile, "counters.groups")
        counter_photo_albums = get(profile, "counters.photoAlbums")
        counter_photos_personal = get(profile, "counters.photosPersonal")
        counter_products = get(profile, "counters.products")
        counter_subscribers = get(profile, "counters.subscribers")

        first_name = get(profile, "first_name")
        last_name = get(profile, "last_name")

        shortname = get(profile, "shortname")

        gender = ProfileAdapter._cast_gender(get(profile, "gender"))
        communities = ProfileAdapter._cast_communities(get(profile, "communities", []))

        groups = ProfileAdapter._cast_groups(get(profile, "groups", []))

        invited_by_friend = ProfileAdapter._cast_boolean(
            get(profile, "invited_by_friend")
        )
        is_merchant = ProfileAdapter._cast_boolean(get(profile, "is_merchant"))
        is_new_user = ProfileAdapter._cast_boolean(get(profile, "is_new_user"))

        last_online = get(profile, "last_online")
        locale = get(profile, "locale")

        location_city = get(profile, "location.city")
        location_country = get(profile, "location.countryName")

        location_of_birth_city = get(profile, "location_of_birth.city")
        location_of_birth_country = get(profile, "location_of_birth.countryName")

        name = get(profile, "name")

        photos = ProfileAdapter._cast_photos(get(profile, "photos", []))
        avatar = get(profile, "pic_max")

        if avatar in photos:
            photos.remove(avatar)

        premium = ProfileAdapter._cast_boolean(get(profile, "premium"))
        private = ProfileAdapter._cast_boolean(get(profile, "private"))

        relatives = ProfileAdapter._cast_relatives(get(profile, "relatives", []))
        relationship = get(profile, "relationship.message")

        registered_date = get(profile, "registered_date")
        url_profile = get(profile, "url_profile")

        profile = {
            "url_profile": url_profile,
            "name": name,
            "first_name": first_name,
            "last_name": last_name,
            "shortname": shortname,
            "bio": bio,
            "age": age,
            "birthday": birthday,
            "birthday_set": birthday_set,
            "gender": gender,
            "locale": locale,
            "relationship": relationship,
            "location_city": location_city,
            "location_country": location_country,
            "location_of_birth_city": location_of_birth_city,
            "location_of_birth_country": location_of_birth_country,
            "registered_date": registered_date,
            "last_online": last_online,
            "counter_applications": counter_applications,
            "counter_friends": counter_friends,
            "counter_groups": counter_groups,
            "counter_photo_albums": counter_photo_albums,
            "counter_photos_personal": counter_photos_personal,
            "counter_products": counter_products,
            "counter_subscribers": counter_subscribers,
            "premium": premium,
            "private": private,
            "blocked": blocked,
            "is_merchant": is_merchant,
            "is_new_user": is_new_user,
            "invited_by_friend": invited_by_friend,
            "access_level_age": access_level_age,
            "access_level_feed": access_level_feed,
            "access_level_now": access_level_now,
            "access_level_video": access_level_video,
            "accessible": accessible,
            "allows_anonym_access": allows_anonym_access,
            "allows_messaging_only_for_friends": allows_messaging_only_for_friends,
            "avatar": avatar,
            "list__photos": photos,
            "Type": "profile",
        }

        return [profile] + communities + groups + relatives

    @staticmethod
    def _cast_boolean(v: Optional[bool]) -> StrBoolean:
        mapper: Dict[bool | None, StrBoolean] = {True: "Да", False: "Нет", None: None}
        return mapper.get(v)

    @staticmethod
    def _cast_gender(v: str) -> Gender | str:
        match v:
            case "male":
                return "Мужской"
            case "female":
                return "Женский"
            case None:
                return "Неизвестно"
            case _:
                return v

    @staticmethod
    def _cast_groups(groups):
        output = []
        for group in groups:
            created = get(group, "created_ms")
            created = str(datetime.utcfromtimestamp(created / 1000)) if created else None

            private = ProfileAdapter._cast_boolean(get(group, "private"))
            premium = ProfileAdapter._cast_boolean(get(group, "premium"))

            output.append(
                {
                    "groups_name": get(group, "name"),
                    "groups_description": get(group, "description"),
                    "groups_created": created,
                    "groups_avatar": get(group, "picAvatar"),
                    "groups_members": get(group, "members_count"),
                    "groups_private": private,
                    "groups_premium": premium,
                    "groups_category": get(group, "category"),
                    "Type": "group",
                }
            )
        return output

    @staticmethod
    def _cast_communities(groups):
        output = []
        translate_type = {
            "FACULTY": "Факультет",
            "WORKPLACE": "Карьера",
            "UNIVERSITY": "Университет",
            "COLLEAGE": "Колледж",
            "ARMY": "Служба",
            "SCHOOL": "Школа",
            "UNKNOWN": "Доп. заведения",
        }

        def field(g, n, default=None):
            v = get(g, n)
            return v if v else default

        for group in groups:
            communities_type_text = field(group, "category", "UNKNOWN")
            communities_type = translate_type.get(
                communities_type_text, translate_type["UNKNOWN"]
            )

            name = field(group, "name", "Не указано")
            abbr = field(group, "abbreviation")

            country = field(group, "country")
            city = field(group, "city")
            address = field(group, "address")

            year_from = field(group, "year_from")
            year_to = field(group, "year_to", "текущее время")

            output.append(
                {
                    "communities_type": communities_type,
                    "communities_name": name,
                    "communities_abbreviation": abbr,
                    "communities_country": country,
                    "communities_city": city,
                    "communities_address": address,
                    "communities_year_from": year_from,
                    "communities_year_to": year_to,
                    "Type": "period",
                }
            )

        return output

    @staticmethod
    def _cast_photos(photos):
        return filter_(map_(photos, lambda x: get(x, "pic_max")), lambda x: x)

    @staticmethod
    def _cast_relationships(relationships, relative_gender):
        match relative_gender:
            case "Мужской":
                relative_index = 0
            case "Женский":
                relative_index = 1
            case _:
                relative_index = None

        translate_base = {
            "LOVE": ["Влюблена", "Влюблен"],
            "COLLEGUE": ["Колледж"] * 2,
            "CLOSE_FRIEND": ["Близкий друг", "Близкая подруга"],
            "CLASSMATE": ["Одноклассник", "Одноклассница"],
            "CURSEMATE": ["Одногруппник", "Одногруппница"],
            "COMPANION_IN_ARMS": ["Товарищ по оружию"] * 2,
            "SPOUSE": ["Супруг", "Супруга"],
            None: ["Иной"] * 2,
        }

        translate_relative = {
            "PARENT": ["Папа", "Мама"],
            "CHILD": ["Сын", "Дочь"],
            "UNCLEAUNT": ["Дядя", "Тетя"],
            "NEPHEW": ["Племянник", "Племянница"],
            "GRANDPARENT": ["Дедушка", "Бабушка"],
            "GRANDCHILD": ["Внук", "Внучка"],
            "CHILDINLAW": ["Зять", "Невестка"],
            "PARENTINLAW": ["Тесть/Свекор", "Теща/Свекровь"],
            "GODPARENT": ["Крестный", "Крестная"],
            "GODCHILD": ["Крестник", "Крестница"],
            "SPOUSE": translate_base["SPOUSE"],
            "BROTHERSISTER": ["Брат", "Сестра"],
            None: ["Родственник", "Родственница"],
        }

        def to_text(translate, index):
            return "/".join(translate) if index is None else translate[index]

        text = []
        for relationship in relationships:
            subtype, type = get(relationship, "subtype_id"), get(relationship, "type_id")

            if subtype in translate_relative and type == "RELATIVE":
                translate = translate_relative[subtype]
                text.append(to_text(translate, relative_index))

            elif type in translate_base:
                translate = translate_base[type]
                text.append(to_text(translate, relative_index))

        return ", ".join(text)

    @staticmethod
    def _cast_relatives(relatives):
        output = []
        for profile in relatives:
            name = get(profile, "name")
            shortname = get(profile, "shortname")
            avatar = get(profile, "pic128x128")
            url_profile = get(profile, "url_profile")
            gender = ProfileAdapter._cast_gender(get(profile, "gender"))
            relationship = ProfileAdapter._cast_relationships(
                get(profile, "relations", []), gender
            )

            output.append(
                {
                    "url_profile": url_profile,
                    "name": name,
                    "shortname": shortname,
                    "avatar": avatar,
                    "gender": gender,
                    "relationships": relationship,
                    "Type": "relative",
                }
            )
        return output
