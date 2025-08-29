import { testData } from './testData';
import UnitTestAdapter from './utils';
import { MongoSessions } from '../src/logic/mongo/client';
import { MONGO_COLLECTION, MONGO_DB, MONGO_URL } from '../src/config/settings';
import { StoreManager } from '../src/logic/baileys/store_manager';
import { WebSocketFactory } from '../src/logic/baileys/websocket_factory';
import { WebSocketHandler } from '../src/logic/baileys/websocket_handler';
import { SearchManager } from '../src/logic/baileys/search_manager';
import { KeyDBAdapter } from '../src/logic/keydb/adapter';
import { KeyDBBuilderXML } from '../src/logic/keydb/builder_xml';
import { fieldXML } from '../src/logic/keydb/schema';
import { sleep } from '../src/logic/utils/date';
import logger from '../src/logic/logger/winston';

describe('API tests of baileys methods for getting info by phone number', () => {
  let socket: any = undefined;

  beforeAll(() => {
    if (socket) {
      return new Promise((resolve) => resolve(socket));
    }
    return new Promise(async (resolve) => {
      const mongo = new MongoSessions(MONGO_URL, MONGO_DB, MONGO_COLLECTION);
      await mongo.connect();
      const session = await mongo.getSession(0);
      if (!session) {
        logger.info('No session available');
        process.exit(0);
      }
      const account = session.session.phone;

      const sm = new StoreManager(account);
      sm.createStore();
      sm.fromMongoState(session.session.zip.buffer);
      sm.read();

      const socketInner = await WebSocketFactory.create(sm, {
        printQRInTerminal: false
      });

      const handler = new WebSocketHandler(socketInner);
      handler.registerHandler('open', async () => {
        socket = socketInner;
        resolve(socket);
      });
      handler.listen();
      await sleep(10_000);
    });
  });

  for (const [phoneNumber, expected] of Object.entries(testData)) {
    // eslint-disable-next-line jest/valid-title
    it(phoneNumber, async () => {
      const sn = new SearchManager(socket);
      const response = await sn.search(phoneNumber);
      const data = new KeyDBAdapter().to_key_db(
        response,
        new KeyDBBuilderXML(fieldXML)
      );
      const castFunction = UnitTestAdapter.castResponse;

      expect(castFunction(data)).toEqual(castFunction(expected));
    });
  }
});
