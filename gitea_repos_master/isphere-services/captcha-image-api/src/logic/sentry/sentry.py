import sentry_sdk

from src.common.utils import SingletonLogging


class Sentry(SingletonLogging):
    IGNORE_EXCEPTIONS = [KeyboardInterrupt]

    def __before_send(self, event, hint):
        if "exc_info" in hint:
            _, exc_value, _ = hint["exc_info"]
            for exception in Sentry.IGNORE_EXCEPTIONS:
                if isinstance(exc_value, exception):
                    return None
        return event

    def create(self, dsn: str, mode: str):
        if mode != "prod":
            self.logger.info(f"Sentry MODE: '{mode}', GlitchTip disabled.")
            return
        self.logger.info(f"Sentry MODE: '{mode}', GlitchTip enabled.")
        sentry_sdk.init(
            dsn=dsn,
            before_send=self.__before_send,
            max_breadcrumbs=50,
            environment=mode,
            attach_stacktrace=True,
        )
