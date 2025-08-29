from app.models import ProxyUsage
from .foreign_manager import ForiegnManager


class ProxyUsageManager(ForiegnManager):
    model = ProxyUsage
