import time


class MESSAGE:
    ACTIVE = 1
    USER_AGENT = 6
    SETTINGS = 19
    TOKEN = 23
    USER_CONTACTS = 32
    FETCH_CHATS = 48
    FETCH_CHATS_MESSAGES = 49
    MARK_AS_READ = 50
    PICK_ACTION_BUTTON = 64
    ACTION_TYPE = 65
    ACTION_CHAT_TYPING = 177


class SocketMessages:
    @staticmethod
    def dict_messages():
        return {
            MESSAGE.ACTIVE: SocketMessages.send_active,
            MESSAGE.USER_AGENT: SocketMessages.send_user_agent,
            MESSAGE.SETTINGS: SocketMessages.send_settings,
            MESSAGE.TOKEN: SocketMessages.send_token,
            MESSAGE.USER_CONTACTS: SocketMessages.send_user_contacts,
            MESSAGE.FETCH_CHATS: SocketMessages.send_fetch_chats,
            MESSAGE.FETCH_CHATS_MESSAGES: SocketMessages.send_fetch_chats_messages,
            MESSAGE.MARK_AS_READ: SocketMessages.send_mark_as_read,
            MESSAGE.PICK_ACTION_BUTTON: SocketMessages.send_pick_action_button,
            MESSAGE.ACTION_TYPE: SocketMessages.send_action_type,
            MESSAGE.ACTION_CHAT_TYPING: SocketMessages.send_action_chat_typing,
        }

    @staticmethod
    def payload(opcode, *args):
        return {
            "ver": 10,
            "cmd": 0,
            "opcode": opcode,
            "payload": SocketMessages._get_payload(opcode, *args),
        }

    @staticmethod
    def _get_payload(opcode, *args):
        return SocketMessages.dict_messages()[opcode](*args)

    @staticmethod
    def now():
        return round(time.time() * 1000)

    @staticmethod
    def send_token(token):
        return {
            "token": token,
            "tokenType": "ANONYM_CONFIRM",
            "deviceType": "WEB_SUPPORT",
            "deviceId": "WEB:1",
        }

    @staticmethod
    def send_user_agent():
        return {
            "deviceId": "WEB:1",
            "userAgent": {
                "deviceType": "WEB_SUPPORT",
                "appVersion": "1.0.0",
                "osVersion": "Linux",
                "locale": "ru",
                "deviceLocale": "ru",
                "deviceName": "Chrome",
                "screen": "1024x768 2.0x",
                "headerUserAgent": "",
                "deviceId": "SUPPORT_CHAT",
                "timezone": "UTC+3",
            },
        }

    @staticmethod
    def send_active():
        return {"interactive": True}

    @staticmethod
    def send_settings(token):
        return {
            "token": token,
            "userAgent": SocketMessages.send_user_agent()["userAgent"],
            "chatsCount": 20,
            "chatsSync": 0,
            "contactsSync": 0,
            "draftsSync": 0,
            "presenceSync": 0,
            "interactive": True,
        }

    @staticmethod
    def send_user_contacts(group_id, contact_type):
        return {"contactIds": [group_id], "contactType": contact_type}

    @staticmethod
    def send_fetch_chats_messages(chat_id):
        return {
            "chatId": chat_id,
            "from": SocketMessages.now(),
            "forward": 25,
            "backward": 25,
        }

    @staticmethod
    def send_fetch_chats(chat_id):
        return {"chatIds": [chat_id]}

    @staticmethod
    def send_mark_as_read(chat_id, profile_id, last_message_id):
        return {
            "chatId": chat_id,
            "userId": profile_id,
            "mark": SocketMessages.now(),
            "setAsUnread": False,
            "messageId": last_message_id,
        }

    @staticmethod
    def send_action_type(chat_id):
        return {"chatId": chat_id, "type": "TEXT"}

    @staticmethod
    def send_action_chat_typing(chat_id):
        return {"chatId": chat_id, "time": SocketMessages.now()}

    @staticmethod
    def send_pick_action_button(chat_id, text):
        return {
            "chatId": chat_id,
            "message": {
                "cid": SocketMessages.now(),
                "text": text,
                "detectShare": False,
                "attachMEL": True,
            },
            "notify": True,
        }
