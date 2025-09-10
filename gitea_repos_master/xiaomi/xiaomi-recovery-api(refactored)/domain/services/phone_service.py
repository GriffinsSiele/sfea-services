import re
from typing import Dict, List


class PhoneParseService:
    def normalize(self, phone: str) -> str:
        phone = phone.strip()
        if phone.startswith("+"):
            return phone
        return "+" + phone

    def parse(self, normalized_phone: str) -> Dict[str, List[str]] | None:
        digits = re.sub(r"\D", "", normalized_phone)
        if 7 <= len(digits) <= 15:
            return {"list__phones": [digits]}
        return None



