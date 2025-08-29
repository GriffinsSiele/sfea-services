from dataclasses import dataclass


@dataclass
class Device:
    id = ""
    manufacturer = ""
    model = ""
    os = ""
    version = ""

    def to_dict(self):
        return {
            "id": self.id,
            "manufacturer": self.manufacturer,
            "model": self.model,
            "version": self.version,
        }

    def __str__(self):
        return f"Device(content={self.to_dict()})"

    @staticmethod
    def from_dict(data):
        if not data:
            return None

        device = Device()
        device.id = data.get("id")
        device.manufacturer = data.get("manufacturer")
        device.model = data.get("model")
        device.os = "Android"
        device.version = data.get("version")
        return device
