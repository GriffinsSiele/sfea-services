import zlib


class CompressData:
    WBITS = 16 + zlib.MAX_WBITS

    @staticmethod
    def decompress(message):
        return zlib.decompress(message, CompressData.WBITS)

    @staticmethod
    def compress(message):
        z = zlib.compressobj(wbits=CompressData.WBITS)
        compress = z.compress(message) + z.flush()
        return compress[:9] + b'\x00' + compress[10:]
