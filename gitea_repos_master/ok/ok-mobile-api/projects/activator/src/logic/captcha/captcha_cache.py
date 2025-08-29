import os
import pathlib
import random
import string

from putils_logic.putils import PUtils


class CaptchaCacheManager:
    CAPTCHA_DIR = PUtils.bp(
        pathlib.Path(__file__).parent.absolute(),
        "..",
        "..",
        "..",
        "..",
        "captcha_images",
    )

    @staticmethod
    def save(data):
        if not PUtils.is_dir_exists(CaptchaCacheManager.CAPTCHA_DIR):
            PUtils.mkdir(CaptchaCacheManager.CAPTCHA_DIR)

        random_name = (
            "".join([random.choice(string.hexdigits) for _ in range(16)]) + ".jpg"
        )
        image_path = PUtils.bp(CaptchaCacheManager.CAPTCHA_DIR, random_name)

        with open(image_path, "wb") as f:
            f.write(data)
        return image_path

    @staticmethod
    def set_result(image_path, text):
        new_path = "{0}_{2}.{1}".format(*image_path.rsplit(".", 1) + [text])
        os.rename(image_path, new_path)

    @staticmethod
    def clear():
        PUtils.delete_dir(CaptchaCacheManager.CAPTCHA_DIR)
        PUtils.mkdir(CaptchaCacheManager.CAPTCHA_DIR)
