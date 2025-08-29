import random
import string

from lib.src.logic.device.device import Device
from lib.src.logic.device.variants import devices


class DeviceGenerator:
    @staticmethod
    def generate(seed=None) -> Device:
        device = Device()
        if seed:
            random.seed(seed)

        device.id = DeviceGenerator.__device_id()

        device_variant: dict = random.choice(devices)
        device.manufacturer = device_variant["manufacturer"]
        device.model = random.choice(device_variant["models"])
        device.os = "Android"
        device.version = random.choice(["11", "12"])
        return device

    @staticmethod
    def __device_id() -> str:
        return "".join(
            [random.choice(string.ascii_lowercase[:6] + string.digits) for _ in range(16)]
        )

    @staticmethod
    def from_payload(data, seed=None):
        if data:
            return Device.from_dict(data)
        return DeviceGenerator.generate(seed=seed)
