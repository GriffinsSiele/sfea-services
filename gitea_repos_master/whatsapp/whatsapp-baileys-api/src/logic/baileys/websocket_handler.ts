import { Boom } from '@hapi/boom';
import { EventEmitter } from 'events';
import logger from '../logger/winston';
import { toShortString } from '../utils/string';
import { Socket } from 'bailyes';
import { ConnectionState } from '@whiskeysockets/baileys';

export class WebSocketHandler {
  private readonly socket;
  private readonly emitter;

  constructor(socket: Socket) {
    this.socket = socket;
    this.emitter = new EventEmitter();
  }

  public registerHandler(name: string, func: (...args: any[]) => void) {
    this.emitter.on(name, func);
  }

  public close() {
    logger.info('Close handler');
    this.socket.end(undefined);
  }

  public listen() {
    this.socket.ev.on('connection.update', async (update) => {
      const { connection, lastDisconnect } = update;
      const code = (lastDisconnect?.error as Boom)?.output?.statusCode;

      if (connection === 'connecting') {
        this.processPendingConnection();
      } else if (connection === 'close') {
        this.processCloseConnection(code);
      } else if (connection === 'open') {
        this.processOpenConnection();
      } else {
        this.processUnhandledConnection(update);
      }
    });
    return this.socket;
  }

  private processCloseConnection(code: number) {
    logger.info(`Connection closed with code ${code}`);
    if (code === 401) {
      logger.warn('loggedOut event');
      return this.emitter.emit('logout', { socket: this.socket });
    }
    if (code === 402) {
      logger.warn('Possible ban');
      return this.emitter.emit('ban', { socket: this.socket });
    }
    if (code === 408) {
      logger.warn('Possible problem with proxy');
      return this.emitter.emit('proxy', { socket: this.socket });
    }
    if (code === 440) {
      logger.warn('Conflict sessions detected');
      return this.emitter.emit('conflict', { socket: this.socket });
    }
    if (code === 515 || code === 503 || code === 428) {
      logger.warn('Restart required');
      return this.emitter.emit('restart', { socket: this.socket });
    }
  }

  private processOpenConnection() {
    logger.info('Connection opened');
    return this.emitter.emit('open', { socket: this.socket });
  }

  private processPendingConnection() {
    logger.info('Connection pending...');
  }

  private processUnhandledConnection(update: Partial<ConnectionState>) {
    logger.info(`Connection unhandled status: ${toShortString(update)}`);
  }
}
