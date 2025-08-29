from app.models import ProxyTag
from .foreign_manager import ForiegnManager


class ProxyTagManager(ForiegnManager):
    model = ProxyTag
