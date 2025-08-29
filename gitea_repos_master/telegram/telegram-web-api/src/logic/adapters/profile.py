from pydash import filter_, map_
from telethon.tl.types import (
    UserStatusLastMonth,
    UserStatusLastWeek,
    UserStatusOffline,
    UserStatusOnline,
    UserStatusRecently,
)


class ProfileAdapter:
    @staticmethod
    def cast(user, extract_name=True):
        if not user:
            return {}
        to_bool = ProfileAdapter.__cast_bool

        return {
            "id": user.id,
            "list__username": ProfileAdapter.__cast_usernames(user),
            "link": f"https://t.me/{user.username}" if user.username else None,
            "first_name": user.first_name if extract_name else None,
            "last_name": user.last_name if extract_name else None,
            "has_photo": to_bool(user.photo),
            "status": ProfileAdapter.__cast_status(user.status),
            "emoji_status": to_bool(user.emoji_status),
            "premium": to_bool(user.premium),
            "bot": to_bool(user.bot),
            "verified": to_bool(user.verified),
            "restricted": to_bool(user.restricted),
            "support": to_bool(user.support),
            "scam": to_bool(user.scam),
            "fake": to_bool(user.fake),
        }

    @staticmethod
    def cast_full(user_full):
        if not user_full:
            return {}

        user = user_full.full_user

        # to_bool = ProfileAdapter.__cast_bool
        return {
            **ProfileAdapter.cast(user_full.users[0]),
            "description": user.about,
            # Закомментированы, т.к. используем данные с сайта
            # "phone_calls_available": to_bool(user.phone_calls_available),
            # "video_calls_available": to_bool(user.video_calls_available),
            # "voice_messages_forbidden": to_bool(user.voice_messages_forbidden),
        }

    @staticmethod
    def __cast_status(status):
        map_status = {
            UserStatusRecently: lambda x: "Недавно",
            UserStatusOnline: lambda x: "В сети " + str(x.expires),
            UserStatusOffline: lambda x: "Не в сети с " + str(x.was_online),
            UserStatusLastWeek: lambda x: "Был в сети неделю назад",
            UserStatusLastMonth: lambda x: "Был в сети месяц назад",
        }

        for instance_class, translation in map_status.items():
            if isinstance(status, instance_class):
                return translation(status)

    @staticmethod
    def __cast_bool(v):
        return "Да" if v else "Нет"

    @staticmethod
    def __cast_usernames(user):
        usernames = [
            user.username,
            *map_(user.usernames if user.usernames else [], lambda u: u.username),
        ]
        return filter_(usernames, lambda u: u)
