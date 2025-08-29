import logging

from settings import LOG_LEVEL
from src.auto_register.call import SeleniumRegister

logging.basicConfig(level=logging.getLevelName(LOG_LEVEL))

name = 'ae06ae'
data = open(f'data/{name}.txt').readlines()

for row in data:
    logging.info(f'Reading row {row}...')
    sr = SeleniumRegister()
    sr.register(row, dump_prefix=name)
