import re
from datetime import datetime

from isphere_exceptions.success import NoDataEvent
from pydash import filter_, get

from src.config.app import ConfigApp


class ProfileAdapter:
    @staticmethod
    def cast(data):
        data = get(data, "data")
        if not data:
            raise NoDataEvent()

        first_name, last_name, name = ProfileAdapter.__cast_name(data)

        bio = get(data, "profile.bio")
        register = ProfileAdapter.__cast_datetime(get(data, "profile.createdAt"))
        instagram = get(data, "profile.instagram")

        is_install_app = ProfileAdapter.__cast_boolean(get(data, "profile.id"))

        avatar = get(data, "ui.avatar240", get(data, "ui.avatar"))
        avatar = avatar if avatar and ("autoavatars" not in avatar) else None

        bans = get(data, "metrics.bans")

        comments_count = get(data, "metrics.commentsCount")
        is_verified = ProfileAdapter.__cast_boolean(get(data, "metrics.isVerified"))
        is_unwanted = ProfileAdapter.__cast_boolean(get(data, "metrics.isUnwanted"))
        is_hidden = ProfileAdapter.__cast_boolean(get(data, "metrics.isHidden"))
        is_pro = ProfileAdapter.__cast_boolean(get(data, "metrics.isPro"))
        index = get(data, "metrics.index")
        contacts_count = get(data, "metrics.contactsCount")

        region = get(data, "phone.region")
        operator = get(data, "phone.carrier")

        is_banned = ProfileAdapter.__cast_boolean(get(data, "common.isBanned"))
        caller_type = get(data, "tagsTop.callerType.name")

        comments = ProfileAdapter.__cast_comments(
            get(data, "comments", []), get(data, "pinnedComment")
        )
        tags = ProfileAdapter.__cast_tags(
            get(data, "tagsTop.categories", []), get(data, "tagsTop.subcategories", [])
        )

        emotags = ProfileAdapter.__cast_emotags(get(data, "tagsTop.emotags", []))

        left_request = get(data, "common.leftRequests")

        return [
            {
                "name": name,
                "first_name": first_name,
                "last_name": last_name,
                "avatar": avatar,
                "bio": bio,
                "register": register,
                "instagram": instagram,
                "caller_type": caller_type,
                "index": index,
                "names_count": contacts_count,
                "region": region,
                "operator": operator,
                "is_install_app": is_install_app,
                "is_hidden": is_hidden,
                "is_verified": is_verified,
                "is_banned": is_banned,
                "is_unwanted": is_unwanted,
                "is_pro": is_pro,
                "bans": bans,
                "emotags": emotags,
                "tags": tags,
                "comments_count": comments_count,
                "Type": "profile",
                "__left_request": left_request,
            }
        ] + comments

    @staticmethod
    def cast_contacts(data):
        data = get(data, "data")
        if not data:
            return []

        output = []
        for contact in get(data, "contacts", [])[: ConfigApp.MAX_TAGS_COUNT]:
            output.append(
                {
                    "first_name": get(contact, "firstName"),
                    "last_name": get(contact, "lastName"),
                    "imports": get(contact, "count"),
                    "likes": get(contact, "likes"),
                    "dislikes": get(contact, "dislikes"),
                }
            )
        return output

    @staticmethod
    def __cast_name(data):
        first_name_v1 = get(data, "profile.firstName")
        first_name_v2 = get(data, "averageProfile.firstName")
        first_name_v3 = get(data, "name.firstName")

        last_name_v1 = get(data, "profile.lastName")
        last_name_v2 = get(data, "averageProfile.lastName")
        last_name_v3 = get(data, "name.lastName")

        name = get(data, "name")
        name = first_name_v3 + " " + last_name_v3 if isinstance(name, dict) else name

        first_name = get(
            filter_([first_name_v1, first_name_v2, first_name_v3], lambda f: f), "0"
        )
        last_name = get(
            filter_([last_name_v1, last_name_v2, last_name_v3], lambda f: f), "0"
        )

        if first_name and ("." in first_name) and not last_name:
            name, first_name = first_name, ""

        if not first_name and (not last_name) and name and len(name) >= 3:
            # Имеет фамилию и имя
            match_1 = re.findall(r"(.+) ([А-ЯЁа-яёA-Za-z\d]+\.?)", name)

            # Нет фамилии, но есть имя
            match_2 = re.findall(r" ([А-ЯЁа-яёA-Za-z\d]+\.?)", name)

            # Имеет фамилию, но нет имени
            match_3 = re.findall(r"(.*) \.?$", name)

            # Нет ни фамилии, ни имени
            match_4 = "No name" == name

            match_5 = "Covers up info" == first_name

            if len(match_1) >= 1:
                first_name = match_1[0][0]
                last_name = match_1[0][1]
            elif len(match_2) >= 1:
                first_name = match_2[0]
                last_name = ""
            elif len(match_3) >= 1:
                first_name = match_3[0]
                last_name = ""
            elif match_4:
                first_name = "Имя не указано"
                last_name = ""
            elif match_5:
                first_name = "Информация скрыта"
                last_name = ""
        return first_name, last_name, name

    @staticmethod
    def __cast_boolean(v):
        return "Да" if v else "Нет"

    @staticmethod
    def __cast_comments(comments, pinned):
        output = []
        for comment in [*comments, pinned]:
            if not comment:
                continue
            author = get(comment, "firstName", "") + " " + get(comment, "lastName", "")
            author = "" if author == " " else author

            dt = ProfileAdapter.__cast_datetime(get(comment, "createdAtTimestamp"))
            output.append(
                {
                    "comment__author": author.strip(),
                    "comment__datetime": dt,
                    "comment__text": get(comment, "text"),
                    "comment__likes": get(comment, "likes"),
                    "comment__dislikes": get(comment, "dislikes"),
                    "Type": "comment",
                }
            )
        return output

    @staticmethod
    def __cast_tags(tags, subtags):
        output = ""
        for tag in [*tags, *subtags]:
            output += f'"{get(tag, "name")}" - {get(tag, "count")}\n'
        return output

    @staticmethod
    def __cast_emotags(emotags):
        output = ""
        for tag in emotags:
            output += f'"{get(tag, "name")}" - {get(tag, "count")}\n'
        return output

    @staticmethod
    def __cast_datetime(dt):
        if not dt:
            return None
        return str(datetime.fromtimestamp(dt))
