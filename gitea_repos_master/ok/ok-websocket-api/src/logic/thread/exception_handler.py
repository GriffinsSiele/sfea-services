from collections import OrderedDict

from worker_classes.keydb.response_builder import KeyDBResponseBuilder


async def normal(exception, mongo, kdbq, session, payload, api):
    pass


async def base_exception(exception, mongo, kdbq, session, payload, api):
    await kdbq.set_answer(payload, KeyDBResponseBuilder.error(exception))


order_exceptions = OrderedDict(
    [
        (None, normal),
        (Exception, base_exception),
    ]
)
