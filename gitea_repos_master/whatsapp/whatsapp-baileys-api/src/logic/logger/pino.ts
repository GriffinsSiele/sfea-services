import pino from 'pino';

const loggerPino = pino({
  level: 'fatal',
  transport: {
    target: 'pino-pretty',
    options: {
      colorize: false
    }
  }
});

export default loggerPino;
