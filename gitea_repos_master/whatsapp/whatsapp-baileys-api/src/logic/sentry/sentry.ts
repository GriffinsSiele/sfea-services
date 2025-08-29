import * as Sentry from '@sentry/node';
import logger from '../logger/winston';

export default class SentryConfig {
  constructor(sentry_url: string, mode: string) {
    if (sentry_url && mode === 'prod') {
      Sentry.init({
        dsn: sentry_url,
        environment: mode,
        tracesSampleRate: 1.0
      });
      logger.info('Sentry is active');
      return;
    }
    logger.info('Sentry is disabled');
  }
}
