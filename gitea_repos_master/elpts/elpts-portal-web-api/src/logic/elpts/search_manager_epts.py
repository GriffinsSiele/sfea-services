from src.logic.elpts.elpts_epts import ElPtsEpts
from src.logic.elpts.search_manager_vin import ElPtsSearchManagerVin


class ElPtsSearchManagerEpts(ElPtsSearchManagerVin):
    elpts_cls = ElPtsEpts
