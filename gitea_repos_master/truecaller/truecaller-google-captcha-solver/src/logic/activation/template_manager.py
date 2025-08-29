import os
from datetime import datetime

from putils_logic.putis import PUtils


class TemplateManager:
    template = open(PUtils.bp(__file__, "..", "template.xml"), "r").read()

    def create_template(self, token):
        if not PUtils.is_dir_exists("templates"):
            PUtils.mkdir("templates")
        filename = PUtils.bp("templates", "account.xml")

        with open(filename, "w") as f:
            f.write(self.template.format(token))
        return filename

    def push_to_app(self, filename):
        actions = [
            f"adb push {filename} /data/data/com.truecaller/shared_prefs/account.xml",
            "adb shell am force-stop com.truecaller",
            "adb shell monkey -p com.truecaller 1",
        ]
        os.system("; ".join(actions))
