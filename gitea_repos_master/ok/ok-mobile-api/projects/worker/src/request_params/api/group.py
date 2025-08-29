from lib.src.config.app import ConfigApp
from lib.src.request_params.interfaces.base import RequestParams


class GroupParams(RequestParams):
    def __init__(self, session_key, uid, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.path = "/api/group/getUserGroupsInfo"
        self.query = self._get_query(session_key, uid)

    def _get_query(self, session_key, uid):
        return {
            "__screen": "profile_user,groups",
            "application_key": ConfigApp.APP_KEY,
            "session_key": session_key,
            "count": 10,
            "direction": "FORWARD",
            "fields": "*",
            "uid": uid,
        }
