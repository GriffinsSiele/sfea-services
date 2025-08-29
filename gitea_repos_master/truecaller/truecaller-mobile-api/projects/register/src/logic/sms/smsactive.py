from smsactivateru import GetBalance, GetNumber, Sms


class ServiceTrueCaller:
    def __init__(self):
        setattr(self, "__service_short_name", "tl")


class SMSActivateAPI:
    def __init__(self, options: dict):
        self.token = options["token"]
        self.wrapper = Sms(self.token)

    def balance(self) -> int:
        return GetBalance().request(self.wrapper)

    def get_truecaller(self, country_code=6) -> dict:
        self.activation = GetNumber(
            service=ServiceTrueCaller(), country=country_code
        ).request(self.wrapper)
        return {
            "short_number": str(self.activation.phone_number),
            "tzid": str(self.activation.id),
        }

    def get_sms(self, *args, **kwargs) -> dict:
        return self.activation.wait_code(wrapper=self.wrapper, timeout=60)
