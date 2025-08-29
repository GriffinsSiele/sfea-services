import types

from mongo_client.client import MongoSessions

from src.config.app import ConfigApp


class MyMongoSessions(MongoSessions):
    def _filter_exclude_lock(self, offset=0):
        return super()._filter_exclude_lock(ConfigApp.NEXT_USE_ALLOWABLE_INTERVAL_DEFAULT)

    def _filter_include_lock(self, offset=0):
        return super()._filter_include_lock(ConfigApp.NEXT_USE_ALLOWABLE_INTERVAL_DEFAULT)

    def switch_collection(self, collection_name):
        options = ConfigApp.NEXT_USE_ALLOWABLE_INTERVAL_PER_COLLECTION
        timeout = (
            ConfigApp.NEXT_USE_ALLOWABLE_INTERVAL_DEFAULT
            if collection_name not in options
            else options[collection_name]
        )

        def _filter_exclude_lock(self, offset=0):
            return super()._filter_exclude_lock(timeout)

        def _filter_include_lock(self, offset=0):
            return super()._filter_include_lock(timeout)

        setattr(
            self,
            _filter_exclude_lock.__name__,
            types.MethodType(_filter_exclude_lock, self),
        )
        setattr(
            self,
            _filter_include_lock.__name__,
            types.MethodType(_filter_include_lock, self),
        )

        super().switch_collection(collection_name)
