import json
import os

from proxy_manager.core.mutex_mixin import MutexMixin


class FileManager(MutexMixin):
    def __init__(self, cache_file, *args, **kwargs):
        super().__init__(*args, **kwargs)
        if not cache_file:
            raise ValueError("Cache file is empty")
        self.cache_file = cache_file

    def _read_cache(self):
        if not os.path.exists(self.cache_file):
            return []

        with open(self.cache_file, "r") as f:
            return json.load(f)

    async def _write_cache(self, data):
        return await self.mutex_wrapper(self._write_cache_raw, data=data)

    async def _write_cache_raw(self, data):
        with open(self.cache_file, "w") as f:
            f.write(json.dumps(data, indent=4, default=str))

    def _clear_cache(self):
        if os.path.exists(self.cache_file):
            os.remove(self.cache_file)
