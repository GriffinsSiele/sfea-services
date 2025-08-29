from src.logic.huawei.honor_explorer import HonorExplorer
from src.logic.huawei.search_manager_common import SearchManagerCommon


class SearchHonorManager(SearchManagerCommon):
    site_explorer_cls = HonorExplorer
