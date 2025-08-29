import os

import yaml


class YamlFile:
    def __init__(self, path):
        self.path = path

    def read(self):
        if not os.path.isfile(self.path):
            return []

        with open(self.path, "r") as f:
            data = yaml.safe_load(f.read())
            return data if data else []

    def write(self, data):
        with open(self.path, "w") as f:
            f.write(yaml.dump(data, default_flow_style=False))

    def append(self, data):
        old_data = self.read()
        data = old_data + data
        self.write(data)