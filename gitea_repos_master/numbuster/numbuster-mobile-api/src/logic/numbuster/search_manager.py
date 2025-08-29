import logging

from src.logic.numbuster.search_manager_interface import SearchNumbusterManagerInterface


class SearchNumbusterManager(SearchNumbusterManagerInterface):
    def __init__(self, auth_data=None, proxy=None, logger=logging, *args, **kwargs):
        super().__init__(auth_data, proxy, logger=logger, *args, **kwargs)
