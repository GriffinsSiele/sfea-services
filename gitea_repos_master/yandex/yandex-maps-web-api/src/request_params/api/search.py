from src.config.app import SearchConfig
from src.config.snippets import snippets
from src.request_params.interfaces.signed import SignedParams


class SearchParams(SignedParams):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.path = "/maps/api/search"

    def set_query(self, csrf_token, session_id, payload):
        self.query = {
            "add_type": "direct",
            "ajax": "1",
            "csrfToken": csrf_token,
            "experimental[0]": "rearr=scheme_Local/Geo/EnableBeautyFilter=1",
            "experimental_business_show_exp_features[0]": "only_byak_prod",
            "internal_pron[advertShimmer]": "true",
            "internal_pron[bookmarksShare]": "true",
            "internal_pron[extendConfig]": "true",
            "internal_pron[flyover]": "true",
            "internal_pron[graphicsFpsMeter]": "true",
            "internal_pron[noTrafficTimeOff]": "true",
            "internal_pron[portalProductsTab]": "true",
            "internal_pron[tile3dPing]": "true",
            "internal_pron[vectorGraphics]": "true",
            "lang": "ru_RU",
            "origin": "maps-form",
            "results": str(SearchConfig.MAX_ITEM_COUNT_IN_RESPONSE),
            "sessionId": session_id,
            "snippets": ",".join(snippets),
            "text": payload,
            "yandex_gid": "2",
            "z": "11",
        }
