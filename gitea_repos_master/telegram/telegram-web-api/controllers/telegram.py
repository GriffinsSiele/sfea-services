"""Compatibility re-export for controller layer"""

from src.interface.controllers.telegram_controller import (
    TelegramController,
    get_telegram_controller,
    router as telegram_router,
)

__all__ = [
    "TelegramController",
    "get_telegram_controller",
    "telegram_router",
]



