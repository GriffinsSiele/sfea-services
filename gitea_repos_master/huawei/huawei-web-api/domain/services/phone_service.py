import re
from typing import Dict, Any, List


class PhoneParseService:
    phone_re = re.compile(r"^[+]?[0-9]{7,15}$")

    def normalize(self, phone: str) -> str:
        phone = phone.strip()
        if phone.startswith("+"):
            return phone
        return "+" + phone

    def parse(self, normalized_phone: str) -> Dict[str, List[str]] | None:
        # Minimal parser: return list__phones with the same phone if syntactically valid
        digits = re.sub(r"\D", "", normalized_phone)
        if 7 <= len(digits) <= 15:
            return {"list__phones": [digits]}
        return None


