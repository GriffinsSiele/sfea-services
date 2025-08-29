from src.chemas import HLRStatus, Types
from fastapi import FastAPI, Form
from src.asyncify import async_wrap
import uvicorn
import re
from queue_logic.keydb import KeyDBQueue
from src.prepare_data import prepare_data
from settings import KEYDB_HOST, KEYDB_PASSWORD, APPLICATION
import logging
from src.misc.logger import Logger
from pprint import pformat

Logger().create()

keydb_queue = KeyDBQueue(KEYDB_HOST, KEYDB_PASSWORD, APPLICATION)

app = FastAPI()


@app.on_event("startup")
def startup_event():
    logging.info('Application started')


@app.post('/response')
async def response(status: Types = Form(), to: str = Form(), errCode: int = Form(0)):
    error_code = errCode
    to = re.sub('[^\d]', '', str(to))
    logging.info(f'POST data "to: {to} ; status: {status}; error_code: {error_code}')
    if status not in HLRStatus.OTHER:
        answer = await async_wrap(prepare_data)((to, status, error_code))
        await async_wrap(keydb_queue.set_answer)(to, answer)
        logging.info(f'returned answer {pformat(answer)}')
        return 'ok'


if __name__ == '__main__':
    uvicorn.run('main:app', host='0.0.0.0', port=8001)
