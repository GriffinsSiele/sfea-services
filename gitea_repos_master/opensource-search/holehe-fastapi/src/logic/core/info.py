from typing import Any

from requests_logic.base import RequestBaseParamsCFFIAsync

from src.logic.core.adapter import DefaultHoleheAdapter


class ClientInterface:
    DEFAULT_CLIENT = {
        "request_class": RequestBaseParamsCFFIAsync,
        "use_proxy": False,
        "impersonate": "chrome99_android",
    }

    DEFAULT_ADAPTER = lambda x: DefaultHoleheAdapter.cast_holehe_to_isphere(x)

    module_mapping: Any = None

    def get(self, module: str):
        if not self.module_mapping:
            raise Exception("Module mapping is empty")

        if module not in self.module_mapping:
            raise Exception("Module not found")

        module_info: dict = self.module_mapping.get(module)
        # if not module_info.get("active", True):
        #     raise Exception("Module is not active")

        func = module_info.get("func")
        client_args = module_info.get("client") or ClientInterface.DEFAULT_CLIENT
        adapter = module_info.get("adapter") or ClientInterface.DEFAULT_ADAPTER

        return func, client_args, adapter
