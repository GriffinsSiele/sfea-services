class Device:
    id = ''
    manufacturer = ''
    model = ''
    os = ''
    version = ''

    def __str__(self):
        return str(self.to_dict())

    def to_dict(self):
        return {
            "device": {
                "device_type": self.manufacturer,
                "name": self.model,
                "os": self.os,
                "unique_id": self.id
            },
            "os_version": self.version
        }
