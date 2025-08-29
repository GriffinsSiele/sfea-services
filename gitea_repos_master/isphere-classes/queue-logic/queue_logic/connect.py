import redis.asyncio as redis
from isphere_exceptions.keydb import (
    KeyDBConfigurationInvalid,
    KeyDBConnection,
    KeyDBError,
)
from redis.exceptions import AuthenticationError
from redis.exceptions import ConnectionError as ConnectionErrorRedis


class KeyDBConnectInterface:
    def __init__(self, keydb_url: str, service: str | None):
        super().__init__()

        self.keydb_url: str = keydb_url
        self.service: str = service or ""

        if not self.keydb_url:
            raise KeyDBConfigurationInvalid()

        self.db: redis.Redis | None = None

    async def connect(self):
        try:
            pool = redis.ConnectionPool.from_url(
                self.keydb_url,
                decode_responses=True,
                health_check_interval=10,
                socket_timeout=5,
                socket_connect_timeout=5,
                socket_keepalive=True,
                retry_on_timeout=True,
                client_name="python-" + self.service,
            )
            self.db: redis.Redis = await redis.Redis(connection_pool=pool)
            await self.db.ping()
        except AuthenticationError as e:
            raise KeyDBConfigurationInvalid(str(e))
        except ConnectionErrorRedis as e:
            raise KeyDBConnection(str(e))
        except Exception as e:
            raise KeyDBError(str(e))
        return self

    async def close(self):
        await self.db.close(close_connection_pool=True)
