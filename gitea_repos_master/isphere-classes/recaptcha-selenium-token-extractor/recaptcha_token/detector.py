import re


class SitekeyDetector:
    @staticmethod
    def find(page_src):
        result = re.findall(r"recaptcha\/api\.js\?render=(.*)\"><", page_src)
        return result[0] if len(result) and result[0] else None
