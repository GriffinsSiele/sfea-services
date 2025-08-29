import * as redis from 'redis';
import { EXPIRE_MEMBER_TIME } from '../../config/settings';
import logger from '../logger/winston';
import process from 'process';
import { RedisClientType as _RedisClientType } from '@redis/client/dist/lib/client';

export default class KeyDBQueue {
  private readonly client: _RedisClientType;
  private readonly service;

  private timeout = EXPIRE_MEMBER_TIME;

  constructor(url: string, service: string) {
    this.service = service;
    this.client = redis.createClient({
      url,
      pingInterval: 30_000,
      disableOfflineQueue: true,
      socket: {
        connectTimeout: 30_000,
        keepAlive: 30_000
      }
    });
  }

  public async connect() {
    return this.client
      .connect()
      .then(() => logger.info('Connected to KeyDB'))
      .catch((e) => {
        logger.error(`Error while connecting to KeyDB: ${e}`);
        process.exit(1);
      });
  }

  private get queue_name() {
    return `${this.service}_queue`;
  }

  private get reestr_name() {
    return `${this.service}_reestr`;
  }

  private async _setTTL(phone: string, error = false) {
    return await this.client.sendCommand([
      'EXPIREMEMBER',
      this.service,
      phone,
      (!error ? this.timeout : 600).toString()
    ]);
  }

  public async addTask(phoneNumber: string) {
    return await this.client.RPUSH(this.queue_name, phoneNumber);
  }

  public async setAnswer(phoneNumber: string, value: Object) {
    await this.client.HSET(this.service, phoneNumber, JSON.stringify(value));
    await this._setTTL(phoneNumber);
  }

  public async returnToQueue(phoneNumber: string) {
    return await this.client.LPUSH(this.queue_name, phoneNumber);
  }

  public async checkQueue() {
    let phone = await this.client.LPOP(this.queue_name);
    if (!phone) {
      phone = await this.client.LPOP(this.reestr_name);
    }
    if (!phone) {
      return null;
    }
    const exists = await this.client.HGET(this.service, phone);
    if (exists) {
      const response = JSON.parse(exists);
      if (200 <= response.code && response.code < 300) {
        await this._setTTL(phone);
        return null;
      }
    }
    return phone;
  }
}
