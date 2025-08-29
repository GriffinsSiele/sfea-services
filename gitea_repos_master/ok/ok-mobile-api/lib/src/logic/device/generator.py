import random
import string

from lib.src.logic.device.device import Device


class DeviceGenerator:
    @staticmethod
    def generate() -> Device:
        device = Device()
        device.android_id = DeviceGenerator.android_id()
        device.device_id = DeviceGenerator.device_id()
        device.install_id = DeviceGenerator.install_id()

        return device

    @staticmethod
    def random_hex(length: int) -> str:
        hex_alphabet = string.ascii_lowercase[:6] + string.digits
        return "".join([random.choice(hex_alphabet) for _ in range(length)])

    @staticmethod
    def android_id() -> str:
        return DeviceGenerator.random_hex(16)

    @staticmethod
    def device_id() -> str:
        return "".join([random.choice(string.digits) for _ in range(15)])

    @staticmethod
    def install_id() -> str:
        f = DeviceGenerator.random_hex
        return f"{f(8)}-{f(4)}-{f(4)}-{f(4)}-{f(12)}"
