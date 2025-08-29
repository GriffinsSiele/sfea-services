import json
import os
from typing import Dict, List, Optional

from putils_logic.putils import PUtils
from pydantic import Field
from pydantic.dataclasses import dataclass

from src.config.settings import MODE


@dataclass
class ConfigAppDataClass:
    DEV: str = "dev"
    PROD: str = "prod"
    UNITTEST: str = "unittest"
    TASKS: Dict[str, str] = Field(default={})
    COLLECTIONS_WATCH_FOR: List[str] = Field(default={})
    COLLECTIONS_IGNORE_PATTERN: Optional[str] = None
    NEXT_USE_ALLOWABLE_INTERVAL_DEFAULT: float = 60
    NEXT_USE_ALLOWABLE_INTERVAL_PER_COLLECTION: Dict[str, float] = Field(default={})
    CRITICAL_MIN_PERCENT_OF_SESSIONS_TO_TRIGGER: float = 20
    CRITICAL_MIN_REPEAT_NOTIFICATION_DELAY: float = 1
    CRITICAL_MIN_IGNORE_COLLECTIONS: List[str] = Field(default=[])
    INACTIVE_IGNORE_COLLECTIONS: List[str] = Field(default=[])
    TEMP_LOCKED_IGNORE_COLLECTIONS: List[str] = Field(default=[])
    STATISTICS_IGNORE_COLLECTIONS: List[str] = Field(default=[])
    UNDERPERFORMING_SUCCESS_IGNORE_COLLECTIONS: List[str] = Field(default=[])
    UNDERPERFORMING_SUCCESS_MIN_USE: float = 100
    UNDERPERFORMING_SUCCESS_DEVIATION_PERCENT: float = 20
    MIGRATION_IGNORE_COLLECTIONS: List[str] = Field(default=[])
    MIGRATION_PERCENT_SESSIONS_FROM_DEV_SESSIONS: float = 65
    MIGRATION_DEV_MIN_SESSIONS: int = 3


def parse_config():
    config_folder = "config" if MODE == "prod" else "config_example"
    json_file = f"{MODE}.json"
    file_name = PUtils.bp(
        os.path.abspath(__file__), "..", "..", "..", config_folder, json_file
    )
    with open(file_name, "r") as f:
        config = json.load(f)

    return ConfigAppDataClass(**config)


ConfigApp: ConfigAppDataClass = parse_config()
