from time import sleep

import yaml
from pydash import map_

from src.call.search import SearchUser

with open('data/autoregister.yml', "r") as f:
    tokens = yaml.safe_load(f.read())

tokens = map_(tokens, lambda t: t['token'])

phone = '+79208533738'

for i, token in enumerate(tokens):
    search = SearchUser(token)
    try:
        print(i, token, search.search(phone))
    except Exception as e:
        print(i, token, e)

    sleep(60)
