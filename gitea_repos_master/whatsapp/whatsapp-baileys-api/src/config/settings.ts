import fs from 'fs';
import dotenv from 'dotenv';
import * as process from 'process';

const mainENV = '.env';

if (fs.existsSync(mainENV)) {
  dotenv.config({ path: mainENV });
}

export const MODE = process.env.MODE || 'dev';

export const MONGO_URL = process.env.MONGO_URL;
export const MONGO_DB = process.env.MONGO_DB;
export const MONGO_COLLECTION_RAW = process.env.MONGO_COLLECTION;
export const MONGO_COLLECTION = MONGO_COLLECTION_RAW + '-' + MODE;

if (!MONGO_URL || !MONGO_DB || !MONGO_COLLECTION) {
  console.error('MNG-501');
  process.exit(1);
}

export const KEYDB_URL = process.env.KEYDB_URL;
export const KEYDB_QUEUE = process.env.KEYDB_QUEUE;

if (!KEYDB_URL || !KEYDB_QUEUE) {
  console.warn('KDB-501');
}

export const SENTRY_URL = process.env.SENTRY_URL;
export const COUNT_USE_FOR_RELOAD = parseInt(
  process.env.COUNT_USE_FOR_RELOAD || '1000'
);
export const EXPIRE_MEMBER_TIME = parseInt(
  process.env.EXPIRE_MEMBER_TIME || '600'
);
