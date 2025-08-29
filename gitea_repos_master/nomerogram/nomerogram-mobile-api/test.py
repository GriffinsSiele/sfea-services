from queue_logic.keydb import KeyDBQueue

from settings import KEYDB_HOST, KEYDB_PASSWORD

kdbq = KeyDBQueue(host=KEYDB_HOST, password=KEYDB_PASSWORD, service='nomerogram_test')

kdbq.add_payload('к030ра97')
kdbq.add_payload('к030ра98')
kdbq.add_payload('н710нн32')
kdbq.add_payload('к731кв32')
kdbq.add_payload('к731кв3112')

print('Tasks have been added.')