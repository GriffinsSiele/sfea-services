from app.models import Session
from .base import BaseManager


class SessionManager(BaseManager):
    model = Session
