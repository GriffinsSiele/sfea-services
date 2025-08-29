from pydantic import BaseModel


class CaptchaToken(BaseModel):
    TOKEN: str
    sitekey: str


class v2(CaptchaToken):
    ...


class hcaptcha(CaptchaToken):
    ...


class v3(CaptchaToken):
    action: str
