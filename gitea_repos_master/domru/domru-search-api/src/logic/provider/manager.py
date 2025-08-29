import json
import os
from collections import defaultdict
from typing import Any, Optional

from proxy_manager.utils import Singleton
from putils_logic import PUtils


class DomruProviderIDManager(metaclass=Singleton):

    def __init__(self) -> None:
        self.__all: list[dict[str, Any]] = self.__load_config(filename="domru_data.json")

        self.provider_ids_by_region_codes = self.__load_region_codes()
        self.provider_ids_by_city_names = self.__load_city_names()

    def __load_config(self, filename: str) -> Any:
        path = PUtils.bp(os.path.abspath(__file__), "..", "..", "..", "config", filename)
        with open(path, encoding="utf-8") as json_file:
            return json.load(json_file)

    def __load_region_codes(self) -> dict[str, list[int]]:
        data = defaultdict(list)
        for d in self.__all:
            region_codes = d.get("region_code", [])
            for reg_code in region_codes:
                if reg_code not in data:
                    data[reg_code] = [d["providerId"]]
                else:
                    data[reg_code] += [d["providerId"]]
        return data

    def __load_city_names(self) -> dict[str, list[int]]:
        data = defaultdict(list)
        for d in self.__all:
            for name in d["city"].split(","):
                name = name.strip()
                if name not in data:
                    data[name] = [d["providerId"]]
                else:
                    data[name] += [d["providerId"]]
        return data

    def define_provider_ids(
        self, regions: list[str], code: Optional[int] = None, city: Optional[str] = None
    ) -> set[int]:
        ids = []
        if city:
            ids += self.provider_ids_by_city_names.get(city, [])
        if code:
            ids += self.provider_ids_by_region_codes.get(str(code), [])
        for r in regions:
            r = r.strip()
            ids += self.provider_ids_by_city_names.get(r, [])
        return set(ids)
