from asyncio import Lock


class MutexMixin:
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.mutex = Lock()

    async def mutex_wrapper(self, function, *args, **kwargs):
        await self.mutex.acquire()
        try:
            return await function(*args, **kwargs)
        finally:
            self.mutex.release()
