import json

from lib.src.config.app import ConfigApp
from lib.src.request_params.interfaces.base import RequestParams


class ProfileParams(RequestParams):
    def __init__(self, session_key, uid, *args, **kwargs):
        super().__init__(*args, **kwargs, data=self._get_payload(session_key, uid))
        self.path = "/api/batch/executeV2"

    def _get_payload(self, session_key, uid):
        query = json.dumps(
            [
                {
                    "users.getInfoBy": {
                        "params": {
                            "fields": "*",
                            "register_as_guest": False,
                            "uid": uid,
                            "use_default_cover": False,
                        }
                    }
                },
                {
                    "users.getCounters": {
                        "params": {
                            "counterTypes": "PHOTOS_PERSONAL,PHOTOS_IN_ALBUMS,PHOTO_ALBUMS,FRIENDS,GROUPS,STATUSES,APPLICATIONS,SUBSCRIBERS,PRODUCTS",
                            "fid": uid,
                        },
                        "onError": "SKIP",
                    }
                },
                {"users.getAccessLevels": {"params": {"uid": uid}, "onError": "SKIP"}},
                {
                    "communities.getList": {
                        "params": {"count": 20, "fid": uid, "fields": "*"},
                        "onError": "SKIP",
                    }
                },
                {
                    "photos.getPhotos": {
                        "params": {
                            "count": 5,
                            "detectTotalCount": "true",
                            "fid": uid,
                            "fields": "photo.pic_max",
                        },
                        "onError": "SKIP",
                    }
                },
                {
                    "friends.getV2": {
                        "params": {
                            "count": 10,
                            "fid": uid,
                            "fields": "name,shortname,pic128x128,url_profile,gender,relations",
                            "list_type": "RELATIVE",
                        }
                    }
                },
            ]
        )

        return {
            "application_key": ConfigApp.APP_KEY,
            "__screen": "profile_user",
            "session_key": session_key,
            "id": "users.getRelationInfo",
            "methods": query,
        }
