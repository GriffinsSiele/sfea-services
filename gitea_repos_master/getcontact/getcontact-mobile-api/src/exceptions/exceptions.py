class MyError(Exception):
    def __init__(self, text=''):
        self.message = text


class AccountBlocked(MyError):
    pass


class AccountLocked(MyError):
    pass


class CaptchaDetected(MyError):
    pass


class LimitError(MyError):
    pass


class UnknownError(MyError):
    pass


class NoDataError(MyError):
    pass
