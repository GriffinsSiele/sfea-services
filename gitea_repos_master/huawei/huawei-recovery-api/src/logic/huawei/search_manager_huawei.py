from src.logic.huawei.huawei_explorer import HuaweiExplorer
from src.logic.huawei.search_manager_common import SearchManagerCommon


class SearchHuaweiManager(SearchManagerCommon):
    site_explorer_cls = HuaweiExplorer
