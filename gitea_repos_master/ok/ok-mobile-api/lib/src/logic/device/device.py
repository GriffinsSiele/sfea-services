class Device:
    android_id = ""
    device_id = ""
    install_id = ""

    def __str__(self):
        return self.__repr__()

    def __repr__(self):
        return f"INSTALL_ID={self.install_id};DEVICE_ID={self.device_id};ANDROID_ID={self.android_id};"
