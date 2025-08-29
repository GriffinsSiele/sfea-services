from smsactivateru import Sms, GetNumber, GetBalance, GetFreeSlots, SmsTypes


class ServiceICQ:
    def __init__(self):
        setattr(self, '__service_short_name', 'iq')


class SMSActivateAPI:
    def __init__(self, options):
        self.token = options['token']
        self.wrapper = Sms(self.token)

    def balance(self):
        return GetBalance().request(self.wrapper)

    def get_icq(self):
        self.activation = GetNumber(service=ServiceICQ(),
                                    country='37').request(self.wrapper)
        return {
            'short_number': str(self.activation.phone_number),
            'tzid': str(self.activation.id)
        }

    def get_sms(self):
        return self.activation.wait_code(wrapper=self.wrapper, timeout=40)

    def cancel(self):
        return self.activation.cancel()

    def ok(self):
        return self.activation.mark_as_used()
