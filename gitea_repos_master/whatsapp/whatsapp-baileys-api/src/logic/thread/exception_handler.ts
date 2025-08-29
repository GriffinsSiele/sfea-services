import * as process from 'process';
import { MongoSessions } from '../mongo/client';
import KeyDBQueue from '../keydb/keydb';
import { KeyDBResponseBuilder } from '../keydb/response_builder';
import logger from '../logger/winston';
import { Session } from 'own';
import { HealthCheck } from '../livenessprobe/health_check';
import { safe } from '../utils/func';

export async function noData(
  mongo: MongoSessions,
  keydb: KeyDBQueue,
  session: Session,
  payload: string
) {
  const sessionSuccess = mongo.sessionSuccess.bind(mongo);
  await Promise.all([
    keydb.setAnswer(payload, KeyDBResponseBuilder.empty()),
    safe(sessionSuccess, false, 0, session, 1, 3)
  ]);
  HealthCheck.checkpoint();
  logger.info('Success task: user not found');
}

export async function accountLocked(
  mongo: MongoSessions,
  keydb: KeyDBQueue,
  session: Session,
  payload: string
) {
  await keydb.setAnswer(
    payload,
    KeyDBResponseBuilder.error('Учетная запись заблокирована', 506)
  );
  process.exit(1);
}

export async function returnToQueue(
  mongo: MongoSessions,
  keydb: KeyDBQueue,
  session: Session,
  payload: string
) {
  await keydb.setAnswer(
    payload,
    KeyDBResponseBuilder.error('Произошла ошибка возврата в очередь', 500)
  );
}
