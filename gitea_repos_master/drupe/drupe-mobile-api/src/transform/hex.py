import codecs


class HexConverter:
    @staticmethod
    def unhex(message):
        return codecs.decode(message, "hex")

    @staticmethod
    def hex(message):
        return codecs.encode(message, "hex")