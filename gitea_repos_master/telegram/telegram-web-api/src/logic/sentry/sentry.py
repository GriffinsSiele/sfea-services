import sentry_sdk
from pydash import get

from src.config.settings import MODE, SENTRY_URL


class Sentry:
    IGNORE_ERRORS = [
        "Results not found",
    ]
    IGNORE_EXCEPTIONS = [KeyboardInterrupt]

    def __before_send(self, event, hint):
        log = get(event, "logentry.message", "")
        for message in Sentry.IGNORE_ERRORS:
            if message in log:
                return None

        if "exc_info" in hint:
            exc_type, exc_value, tb = hint["exc_info"]
            for exception in Sentry.IGNORE_EXCEPTIONS:
                if isinstance(exc_value, exception):
                    return None
        return event

    def create(self):
        if not SENTRY_URL:
            return

        sentry_sdk.init(
            SENTRY_URL,
            before_send=self.__before_send,
            max_breadcrumbs=50,
            environment=MODE,
            attach_stacktrace=True,
        )
