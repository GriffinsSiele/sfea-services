import random
import string

from src.devices.device import Device
from src.devices.variants import devices


class DeviceGenerator:
    @staticmethod
    def generate():
        device = Device()
        device.id = DeviceGenerator.device_id()

        device_variant = random.choice(devices)
        device.manufacturer = device_variant['manufacturer']
        device.model = random.choice(device_variant['models'])
        device.os = 'Android'
        device.version = '6.0'
        return device

    @staticmethod
    def device_id():
        return ''.join([random.choice(string.ascii_lowercase[:6] + string.digits) for _ in range(16)])