import { Records } from 'keydb';
import logger from '../logger/winston';

export class KeyDBResponseBuilder {
  private static __base_response(
    code: number = 200,
    message: string = 'ok',
    status: string = 'ok',
    records: Records | null = null
  ) {
    if (!records) {
      records = [];
    }

    logger.info(`KeyDB response: [${code}] ${String(message)}`);
    return {
      status,
      code,
      timestamp: Math.floor(Date.now() / 1000),
      message: String(message),
      records
    };
  }

  public static ok(response: Records) {
    return KeyDBResponseBuilder.__base_response(200, 'ok', 'ok', response);
  }

  public static empty() {
    return KeyDBResponseBuilder.__base_response(204);
  }

  public static error(e: Error | string, code: number = 500) {
    return KeyDBResponseBuilder.__base_response(code, String(e), 'Error');
  }
}
