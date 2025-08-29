import { MongoSessions } from '../mongo/client';
import { MONGO_COLLECTION, MONGO_DB, MONGO_URL } from '../../config/settings';
import HostnameManager from '../utils/hostname';
import logger from '../logger/winston';
import process from 'process';
import { StoreManager } from '../baileys/store_manager';
import { WebSocketFactory } from '../baileys/websocket_factory';
import { WebSocketHandler } from '../baileys/websocket_handler';
import { sleep } from '../utils/date';

export class RegisterProcess {
  public async run() {
    const mongo = new MongoSessions(MONGO_URL, MONGO_DB, MONGO_COLLECTION);
    mongo.defaultFilter = { 'session.pod': HostnameManager.hostname };
    await mongo.connect();

    const sm = new StoreManager('temp');
    sm.createStore();

    await this.startSocket(mongo, sm);
  }

  private async startSocket(mongo: MongoSessions, sm: StoreManager) {
    const socket = await WebSocketFactory.create(sm, {
      printQRInTerminal: true
    });

    const possibleSession = await mongo.getSession(0);
    const phone = possibleSession?.session?.phone || 'ANY';
    logger.info(`AUTH WITH PHONE: ${phone}`);

    // Exit after 2 min to read QR again
    setTimeout(() => {
      process.exit(0);
    }, 120_000);

    const handler = new WebSocketHandler(socket);
    handler.registerHandler('open', async () => {
      await sleep(5_000);

      const phoneNumber = socket.authState.creds.me.id.split(':')[0];
      const pod = HostnameManager.hostname;

      logger.info(`Authed with phone number: ${phoneNumber}`);

      // Check is correct pod: is exists session with another pod but with the same phone
      mongo.defaultFilter = { 'session.phone': phoneNumber };
      const existedSessionWithPhone = await mongo.getSession(0);
      if (
        existedSessionWithPhone &&
        existedSessionWithPhone.session.pod !== pod
      ) {
        logger.error(
          `Session with such phone existed on pod ${existedSessionWithPhone.session.pod}. Please use correct pod`
        );
        socket.logout('');
        process.exit(0);
      }

      mongo.defaultFilter = {
        'session.phone': phoneNumber,
        'session.pod': pod
      };
      const [session, zip] = await Promise.all([
        mongo.getSession(0),
        sm.toMongoState()
      ]);
      if (!session) {
        logger.info(
          `There is no session with such phone. Creating new session for pod ${pod}`
        );
        await mongo.add({
          session: { phone: phoneNumber, pod, zip }
        });
      } else {
        logger.info('Updating existed session');
        await mongo.sessionUpdate(session, {
          active: true,
          next_use: null,
          ['session.zip']: zip
        });
      }
      logger.info('Exit register');
      process.exit(0);
    });
    handler.registerHandler('restart', async () => {
      handler.close();
      logger.info('Start socket again');
      await this.startSocket(mongo, sm);
    });
    handler.listen();
  }
}
