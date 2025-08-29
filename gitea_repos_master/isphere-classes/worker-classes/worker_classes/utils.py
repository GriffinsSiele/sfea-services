from typing import Any


def short_str(string: Any) -> str:
    s = str(string)
    return s if len(s) < 200 else s[:200] + "..."
