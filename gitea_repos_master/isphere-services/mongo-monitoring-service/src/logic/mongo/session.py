import re

from pydash import map_

from src.config.app import ConfigApp


def is_watching_collection(collection, extra_exclude=None):
    if extra_exclude is None:
        extra_exclude = []

    if re.match(ConfigApp.COLLECTIONS_IGNORE_PATTERN, collection):
        return False

    watching_collections_types = map_(
        ConfigApp.COLLECTIONS_WATCH_FOR, lambda x: getattr(ConfigApp, x)
    )

    if not any(map_(watching_collections_types, lambda x: collection.endswith(x))):
        return False

    if collection in extra_exclude:
        return False

    return True
