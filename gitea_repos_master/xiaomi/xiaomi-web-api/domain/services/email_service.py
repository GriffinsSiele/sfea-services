import re
from typing import Dict, List


class EmailParseService:
    email_re = re.compile(r"^[^@\s]+@[^@\s]+\.[^@\s]+$")

    def normalize(self, email: str) -> str:
        return email.strip().lower()

    def parse(self, normalized_email: str) -> Dict[str, List[str]] | None:
        if self.email_re.match(normalized_email):
            return {"list__emails": [normalized_email]}
        return None



