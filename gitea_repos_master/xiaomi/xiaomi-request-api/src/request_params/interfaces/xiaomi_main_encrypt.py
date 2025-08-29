import json

from src.config import ConfigApp
from src.crypto.crypto import CryptoAES, CryptoRSA
from src.request_params.interfaces.xiaomi_main import XiaomiMainRequestParams
from src.utils.utils import random_string


class XiaomiMainEncryptRequestParams(XiaomiMainRequestParams):
    """Шифрование для дополнительных настроек для получения URL адреса с сайта xiaomi
    который содержит параметры строки, необходимые для получения капчи.
    """

    timestamp: int

    def __init__(self, *args, **kwargs):
        data_to_dumps = self.get_data(self.timestamp)
        key = random_string(16)  # рандомная строка для каждого запроса
        s = CryptoRSA.encrypt(ConfigApp.PUBLIC_KEY, key)
        d = CryptoAES().encrypt(key, json.dumps(data_to_dumps))

        super().__init__(
            data={
                "s": s,
                "d": d,
                "a": "helpcenter",
            },
            *args,
            **kwargs,
        )

    @staticmethod
    def get_data(timestamp: int) -> dict:
        return {
            "type": 0,
            "startTs": timestamp,
            "endTs": timestamp + 6000,
            "env": {
                "p1": "0.1",
                "p2": "pc-Chrome121",
                "p3": "Linux x86_64",
                "p4": "Gecko",
                "p5": "en-US",
                "p6": "Netscape",
                "p7": "Mozilla",
                "p8": True,
                "p9": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.6167.160 Safari/537.36",
                "p10": 180,
                "p11": timestamp,
                "p12": 1920,
                "p13": 1080,
                "p14": 1920,
                "p15": 1053,
                "p16": 1856,
                "p17": 676,
                "p18": "https://account.xiaomi.com/helpcenter/service/forgetPassword",
                "p19": 2,
                "p20": "aecfd5a1eed6ead82676526059f2603dcc0c57f8",
                "p21": "Caecfd5a1eed6ead82676526059f2603dcc0c57f8",
                "p22": 5,
                "p23": "e479d57c5384e7ba5bddecf6090efd0af8a27beb",
                "p24": "A6ac807eecd9684616c940eaa984089d3d2a08c4b,H82c4f4c48dda879ccb32a7932a948d23a4df1087,T7dda3cd8f061e3cc2e4381f8d05f4e82fa32dc0c",
                "p25": "901da3696fe071f23eb6ece9f2c524c5cc243635",
                "p26": "f9bb80acbab318c1307f44d20d8ff63ef06f9e52",
                "p28": "",
                "p29": 68,
                "p30": 1984,
                "p31": 27,
                "p32": "0.7",
                "p33": [],
                "p34": "https://account.xiaomi.com/helpcenter/service/forgetPassword",
            },
            "action": {
                "a1": [1856, 676],
                "a2": [],
                "a3": [[728, 266, 3740], [489, 84, 4844]],
                "a4": [],
                "a5": [
                    [2, 503, 2969],
                    [250, 395, 3020],
                    [564, 289, 3071],
                    [779, 233, 3122],
                    [792, 230, 3173],
                    [797, 234, 3253],
                    [798, 254, 3304],
                    [782, 289, 3355],
                    [777, 291, 3406],
                    [775, 290, 3473],
                    [751, 275, 3525],
                    [736, 269, 3581],
                    [728, 266, 3632],
                    [728, 267, 3811],
                    [736, 288, 3864],
                    [749, 317, 3915],
                    [750, 322, 3966],
                    [752, 328, 4017],
                    [752, 329, 4144],
                    [742, 351, 4195],
                    [690, 366, 4246],
                    [604, 384, 4297],
                    [575, 389, 4354],
                    [574, 391, 4423],
                    [566, 374, 4474],
                    [556, 246, 4525],
                    [507, 112, 4576],
                ],
                "a6": [[0, 5438], [0, 5782], [0, 5944]],
                "a7": [],
                "a8": [3670],
                "a9": [3671],
                "a10": [],
                "a11": [],
                "a12": [],
                "a13": [],
                "a14": [],
            },
            "force": True,
            "talkBack": True,
            "uid": "",
            "nonce": {"t": timestamp, "r": 2219908196},
            "version": "2.0",
            "scene": "helpcenter",
        }
