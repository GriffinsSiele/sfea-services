import logging

from isphere_exceptions.session import SessionBlocked
from livenessprobe_logic import HealthCheck
from pydash import get

from src.logic.activator import SessionActivation


class RabbitMQHook:
    def __init__(self, mongo):
        self.mongo = mongo

    async def process(self, message):
        session = message.body

        collection = get(session, "collection")
        if collection:
            self.mongo.switch_collection(collection)

        search = {
            "session.login": get(session, "login"),
            "session.password": get(session, "password"),
        }
        logging.info(f"Payload search: {search}")
        session = await self.mongo.get_activation_sessions(search)
        if not session:
            logging.info("No session found")
            return None

        token = None
        try:
            activator = SessionActivation(get(session, "session"), use_proxy=True)
            token = await activator.activate()
        except SessionBlocked as e:
            logging.error(e)
            await self.mongo.session_inactive(session)
        except Exception as e:
            logging.error(e)
            await self.mongo.session_lock(session, period={"hours": 6})
        else:
            await self.mongo.session_update(
                session,
                {"next_use": None, "active": True, "session": activator.get_session()},
            )

        HealthCheck().checkpoint()
        return token
