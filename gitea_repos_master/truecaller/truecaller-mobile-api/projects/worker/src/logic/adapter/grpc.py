class GRPCAdapter:
    @staticmethod
    def phone_to_request(phone):
        base_part = b"\x00\x00\x00\x00\x0E\x0A\x0C"
        phone = f"+{phone}" if not phone.startswith("+") else phone
        phone = phone.encode()
        return base_part + phone
