import { MongoSessions } from '../mongo/client';
import KeyDBQueue from '../keydb/keydb';
import logger from '../logger/winston';
import { KeyDBAdapter } from '../keydb/adapter';
import { fieldXML } from '../keydb/schema';
import {
  AccountLocked,
  ErrorReturnToQueue,
  NoDataError
} from '../exceptions/exceptions';
import { sleep } from '../utils/date';
import { KeyDBResponseBuilder } from '../keydb/response_builder';
import { KeyDBBuilderXML } from '../keydb/builder_xml';
import { accountLocked, noData, returnToQueue } from './exception_handler';
import { COUNT_USE_FOR_RELOAD } from '../../config/settings';
import { SearchManager } from '../baileys/search_manager';
import { Socket } from 'bailyes';
import { HealthCheck } from '../livenessprobe/health_check';
import { safe } from '../utils/func';

export class ThreadManager {
  private countReload = 0;
  private countIterations = 0;
  private countFailureInRow = 0;
  private readonly mongo: MongoSessions;
  private readonly keydb: KeyDBQueue;
  private readonly socket: Socket;
  private session: { [k: string]: any };

  constructor(mongo: MongoSessions, keydb: KeyDBQueue, socket: Socket) {
    this.mongo = mongo;
    this.keydb = keydb;
    this.socket = socket;
    this.session = null;
  }

  public async start() {
    setTimeout(() => logger.info('Worker started. Ready for tasks!'), 800);
    this.mongo.projection = { active: 1 };
    while (this.isAlive()) {
      this.countIterations += 1;
      if (this.countIterations % 20 === 0) {
        const getSession = this.mongo.getSession.bind(this.mongo);
        this.session = await safe(getSession, false, true, 0);
        if (!this.session) {
          await sleep(500);
          continue;
        }
      }

      const start = Date.now();
      const phoneNumber = await this.keydb.checkQueue();
      if (!phoneNumber) {
        await sleep(100);
        continue;
      }
      logger.info(`LPOP ${phoneNumber}`);
      HealthCheck.checkpoint();

      try {
        const sm = new SearchManager(this.socket);

        const response = await sm.search(phoneNumber);
        const responseKeyDB = new KeyDBAdapter().to_key_db(
          response,
          new KeyDBBuilderXML(fieldXML)
        );

        const sessionSuccess = this.mongo.sessionSuccess.bind(this.mongo);
        await Promise.all([
          this.keydb.setAnswer(
            phoneNumber,
            KeyDBResponseBuilder.ok(responseKeyDB)
          ),
          safe(sessionSuccess, false, 0, this.session, 1, 3)
        ]);

        logger.info('Success');
        this.countFailureInRow = 0;
        HealthCheck.checkpoint();
      } catch (e) {
        const exceptions = [
          { error: NoDataError, func: noData },
          { error: AccountLocked, func: accountLocked },
          { error: ErrorReturnToQueue, func: returnToQueue },
          { error: Error, func: returnToQueue }
        ];
        e instanceof NoDataError ? logger.info(e) : logger.error(e);
        for (const { error, func } of exceptions) {
          if (e instanceof error) {
            await Promise.all([
              func(this.mongo, this.keydb, this.session, phoneNumber),
              safe(
                this.mongo.sessionUse.bind(this.mongo),
                false,
                0,
                this.session
              )
            ]);
            this.countFailureInRow += 1;
            break;
          }
        }
      }
      const end = Date.now();
      logger.info(`Total processing task time: ${(end - start) / 1000} sec`);
      this.countReload += 1;
    }
    logger.info('Stop cycle due to condition of alive');
    process.exit(0);
  }

  private isAlive() {
    if (this.countReload == 0) {
      return true;
    }
    if (this.countReload >= COUNT_USE_FOR_RELOAD) {
      return false;
    }
    if (this.countFailureInRow >= 30) {
      return false;
    }
    return true;
  }
}
