import asyncio

from src.logic.worker import SearchDomruManager


async def run():
    # Logger().create()

    for i in range(1000, 9999):
        try:
            phone = f"+7395531{i}"
            s = SearchDomruManager(None)
            await s.prepare()
            r = await s.search(phone)
            print(r)
            if len(r) > 1:
                break
        except Exception as exc:
            print(f"{exc.message if hasattr(exc, 'message') else exc.__str__()}")
            pass


asyncio.run(run())
