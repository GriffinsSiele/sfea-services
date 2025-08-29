import ConfigurationManager from '../prepare/configuration-manager';
import logger from '../logger';

const start = async () => {
  await ConfigurationManager.generate();
};

start().then(() => {
  logger.info('Generated configuration with proxy');
  process.exit(0);
});
