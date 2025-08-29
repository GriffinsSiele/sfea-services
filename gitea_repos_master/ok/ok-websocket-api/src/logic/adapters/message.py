from pydash import get, map_


class MessageAdapter:
    @staticmethod
    def has_captcha(response):
        message = get(response, "payload.message.text", "")
        return "символы с картинки" in message

    @staticmethod
    def captcha_url(response):
        return get(response, "payload.message.attaches.0.url", "")

    @staticmethod
    def options_for_attaches(response):
        return map_(
            get(response, "payload.messages.-1.attaches.0.keyboard", []),
            lambda x: get(x, "0.message.text"),
        )
