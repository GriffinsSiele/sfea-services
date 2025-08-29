from src.config.app import ConfigApp, SearchParams
from src.config.arguments import SearchArguments
from src.logic.adapters.viewpoints import ViewpointManager
from src.request_params.interfaces.signed import SignedParams


class SearchItems(SignedParams):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.path = "/3.0/items"

    def set_query(self, search_text, viewpoint1=None, viewpoint2=None, extra_query=None):
        viewpoint1, viewpoint2 = ViewpointManager.generate_random(
            viewpoint1, viewpoint2, as_string=True
        )

        self.query = {
            "key": ConfigApp.APP_KEY,
            "q": search_text,
            "fields": ",".join(SearchArguments.ITEMS_SEARCH),
            "type": ",".join(SearchArguments.TYPE_SEARCH),
            "page_size": str(SearchParams.MAX_ITEM_COUNT_IN_RESPONSE + 2),
            "page": "1",
            "locale": "ru_RU",
            "allow_deleted": "true",
            "search_device_type": "desktop",
            "shv": ConfigApp.APP_VERSION,
            "viewpoint1": viewpoint1,
            "viewpoint2": viewpoint2,
            **(extra_query if extra_query else {}),
        }
