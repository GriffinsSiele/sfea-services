import Logger from '../node_modules/js-logger/src/logger';

Logger.useDefaults({
  defaultLevel: Logger.DEBUG,
  formatter: function (messages) {
    messages.unshift(new Date().toUTCString());
  }
});

export default Logger;
