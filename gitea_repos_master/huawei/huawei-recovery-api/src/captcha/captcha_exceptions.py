class CaptchaServiceException(Exception):
    def __init__(self, message="An error occurred while solving captcha"):
        self.message = message
        super().__init__(self.message)
