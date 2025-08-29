import { MongoSessions } from '../mongo/client';
import {
  KEYDB_QUEUE,
  KEYDB_URL,
  MODE,
  MONGO_COLLECTION,
  MONGO_DB,
  MONGO_URL,
  SENTRY_URL
} from '../../config/settings';
import HostnameManager from '../utils/hostname';
import KeyDBQueue from '../keydb/keydb';
import logger from '../logger/winston';
import { StoreManager } from '../baileys/store_manager';
import { WebSocketFactory } from '../baileys/websocket_factory';
import { WebSocketHandler } from '../baileys/websocket_handler';
import { ThreadManager } from './thread_manager';
import { delayedExist } from '../utils/exit';
import { RegisterProcess } from './register';
import { Session } from 'own';
import { toShortString } from '../utils/string';
import SentryConfig from '../sentry/sentry';

export class StartProcess {
  public async run() {
    const mongo = new MongoSessions(MONGO_URL, MONGO_DB, MONGO_COLLECTION);
    mongo.defaultFilter = { 'session.pod': HostnameManager.hostname };
    mongo.projection = null;
    await mongo.connect();

    const keydb = new KeyDBQueue(KEYDB_URL, KEYDB_QUEUE);
    await keydb.connect();

    new SentryConfig(SENTRY_URL, MODE);

    const session = await mongo.getSession(0, true);
    logger.info(`Current session: ${toShortString(session)}`);

    if (!session?.active) {
      await new RegisterProcess().run();
      return;
    }

    const phoneNumber = session.session.phone;
    const sm = new StoreManager(phoneNumber);
    sm.createStore();
    sm.fromMongoState(session.session.zip.buffer);
    sm.read();

    mongo.defaultFilter = {
      active: true,
      'session.pod': HostnameManager.hostname
    };

    await this.startSocket(mongo, keydb, sm, session);
  }

  private async startSocket(
    mongo: MongoSessions,
    keydb: KeyDBQueue,
    sm: StoreManager,
    session: Session
  ) {
    const socket = await WebSocketFactory.create(sm, {
      printQRInTerminal: false
    });

    const handler = new WebSocketHandler(socket);
    handler.registerHandler('open', async () => {
      const tm = new ThreadManager(mongo, keydb, socket);
      await tm.start().catch((e) => {
        logger.error(e);
        delayedExist();
      });
    });
    handler.registerHandler('conflict', async () => {
      const interval = { minutes: 1 };
      logger.info(`Session locked: ${JSON.stringify(interval)}`);
      await mongo.sessionLock(session, interval);
      delayedExist();
    });
    handler.registerHandler('logout', async () => {
      logger.info('Session blocked');
      await mongo.sessionBlock(session);
      delayedExist();
    });
    handler.registerHandler('ban', async () => {
      const interval = { hours: 24 };
      logger.info(`Session ban for ${JSON.stringify(interval)}`);
      await mongo.sessionLock(session, interval);
    });
    handler.registerHandler('restart', async () => {
      handler.close();
      logger.info('Start socket again');
      await this.startSocket(mongo, keydb, sm, session);
    });
    handler.listen();
  }
}
