import KeyDBQueue from '../logic/keydb/keydb';
import { KEYDB_QUEUE, KEYDB_URL } from '../config/settings';
import process from 'process';

async function run() {
  const keydb = new KeyDBQueue(KEYDB_URL, KEYDB_QUEUE);
  await keydb.connect();

  await keydb.addTask('74993506695');
  console.log(await keydb.checkQueue());

  process.exit(0);
}

(async function () {
  await run();
})();
