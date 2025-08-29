import bson
from .Profile import viber_profile
from .asynctosync import async_to_sync_func
from mongo_client.client import MongoSessions


class Singleton(type):
    _instances = {}

    def __call__(cls, *args, **kwargs):
        if cls not in cls._instances:
            cls._instances[cls] = super(Singleton, cls).__call__(*args, **kwargs)
        return cls._instances[cls]


class IsphereToken(metaclass=Singleton):
    def init(self, mongo_url, mongo_db, mongo_collection, pod):
        self.client = MongoSessions(
            mongo_url=mongo_url, db=mongo_db, collection=mongo_collection
        )
        self.client.default_filter = {"active": True, "session.pod": pod}
        self.pod = pod

    def get(self):
        self.session = async_to_sync_func(self.client.get_session)()
        viber_profile.delete_profile()
        if self.session:
            viber_profile.unpack(self.session["session"]["file"])
        return self.session

    def increment_success(self):
        if self.session:
            async_to_sync_func(self.client.session_success)(self.session)

    def session_unactive(self):
        if self.session:
            async_to_sync_func(self.client.session_inactive)(self.session)

    def add(self, phone, tar=None) -> None:
        if not phone:
            return
        if not tar:
            tar = viber_profile.pack(phone=phone)
        data = {"session": {"pod": self.pod, "file": bson.Binary(tar), "phone": phone}}
        async_to_sync_func(self.client.add)(data)


mongo = IsphereToken()
