import logging

from src.auto_register.call import SeleniumRegister

logging.basicConfig(level='INFO')

for i in range(10, 120):
    sr = SeleniumRegister(f'546.{i}')
    sr.register()
