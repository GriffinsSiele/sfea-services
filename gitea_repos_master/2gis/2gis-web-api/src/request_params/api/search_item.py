from src.config.app import ConfigApp
from src.config.arguments import SearchArguments
from src.logic.adapters.viewpoints import ViewpointManager
from src.request_params.interfaces.signed import SignedParams


class SearchItem(SignedParams):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.path = "/3.0/items/byid"

    def set_query(self, id, viewpoint1=None, viewpoint2=None, extra_query=None):
        viewpoint1, viewpoint2 = ViewpointManager.generate_random(
            viewpoint1, viewpoint2, as_string=True
        )

        self.query = {
            "locale": "ru_RU",
            "id": id,
            "key": ConfigApp.APP_KEY,
            "viewpoint1": viewpoint1,
            "viewpoint2": viewpoint2,
            "fields": ",".join(SearchArguments.ITEMS_SEARCH),
            **(extra_query if extra_query else {}),
        }
