import json

from queue_logic.keydb import KeyDBQueue

from settings import KEYDB_HOST, KEYDB_PASSWORD

kdbq = KeyDBQueue(host=KEYDB_HOST, password=KEYDB_PASSWORD, service='autoins_test')

kdbq.add_payload(json.dumps({'type': 'vin', 'value': 'JTNBV56E70J006152'}))
kdbq.add_payload(json.dumps({'type': 'vin', 'value': 'LVVDB21B6ND050172'}))
kdbq.add_payload(json.dumps({'type': 'vin', 'value': 'NLAF07670AW020511'}))
kdbq.add_payload(json.dumps({'type': 'vin', 'value': 'XW7R43FV50S007488'}))
kdbq.add_payload(json.dumps({'type': 'gosNumber', 'value': 'Т361кс178'}))
kdbq.add_payload(json.dumps({'type': 'gosNumber', 'value': 'К361кс178'}))
kdbq.add_payload(json.dumps({'type': 'vin', 'value': 'XW7R43FV50S007486'}))
kdbq.add_payload(json.dumps({'type': 'vin', 'value': 'XW7R43FV50S007484'}))
kdbq.add_payload(json.dumps({'type': 'vin', 'value': 'XW7R43FV50S007481'}))
kdbq.add_payload(json.dumps({'type': 'vin', 'value': 'XW7R43FV50S007482'}))
kdbq.add_payload(json.dumps({'type': 'vin', 'value': 'XW7R43FV50S007483'}))
kdbq.add_payload(json.dumps({'type': 'vin', 'value': 'XW7R43FV50S007485'}))
kdbq.add_payload(json.dumps({'type': 'vin', 'value': 'XW7R43FV50S007487'}))

print('Tasks have been added.')
