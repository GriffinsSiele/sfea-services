import winston from 'winston';

const loggerLevel = 'info';

const logger = winston.createLogger({
  format: winston.format.combine(
    winston.format.timestamp({
      format: 'YYYY-MM-DD HH:mm:ss,SSS'
    }),
    winston.format.printf(
      (info) =>
        `${info.timestamp} - [${info.level.toUpperCase()}] - ${info.message}` +
        (info.splat !== undefined ? `${info.splat}` : ' ')
    )
  ),
  transports: [new winston.transports.Console({ level: loggerLevel })]
});
export default logger;
