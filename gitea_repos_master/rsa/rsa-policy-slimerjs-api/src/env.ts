import * as fs from 'fs';
import dotenv from 'dotenv';
import logger from './logger';

const mainENV = '.env';

if (fs.existsSync(mainENV)) {
  dotenv.config({ path: mainENV });
}

export const KEYDB_HOST = process.env.KEYDB_HOST;
export const KEYDB_PORT = process.env.KEYDB_PORT;
export const KEYDB_PASSWORD = process.env.KEYDB_PASSWORD;

export const PROXY_LOGIN = process.env.PROXY_LOGIN;
export const PROXY_PASSWORD = process.env.PROXY_PASSWORD;

export const TELEGRAM_TOKEN_BOT = process.env.TELEGRAM_TOKEN_BOT;
export const TELEGRAM_CHAT_ID = process.env.TELEGRAM_CHAT_ID;

export const PROD = process.env.PROD === 'true';

if (!KEYDB_HOST || !KEYDB_PORT || !KEYDB_PASSWORD) {
  logger.error('No KEYDB database settings.');
  process.exit(1);
}

if (!PROXY_LOGIN || !PROXY_PASSWORD) {
  logger.error('No proxy settings.');
  process.exit(1);
}

if (!TELEGRAM_TOKEN_BOT || !TELEGRAM_CHAT_ID) {
  logger.error('No telegram bot settings.');
  process.exit(1);
}
