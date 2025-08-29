import {
  makeInMemoryStore,
  useMultiFileAuthState
} from '@whiskeysockets/baileys';
import * as fs from 'fs';
import {
  readBinaryFile,
  unzipFolder,
  writeBinaryFile,
  zipFolder
} from '../utils/zip';
import { randomString, toShortString } from '../utils/string';
import logger from '../logger/winston';
import loggerPino from '../logger/pino';
import { Store } from 'bailyes';

export class StoreManager {
  private readonly phoneNumber: string = '';
  private readonly _sessionFolder: string;
  public store: Store | null;

  constructor(phoneNumber: string) {
    this.phoneNumber = phoneNumber;
    this._sessionFolder = `sessions/${this.phoneNumber}_${randomString()}`;
    logger.info(`Using session folder: ${this.sessionFolder}`);
    if (fs.existsSync('sessions')) {
      fs.rmSync('sessions', { recursive: true });
    }
    fs.mkdirSync('sessions', { recursive: true });
  }

  public createStore() {
    this.store = makeInMemoryStore({ logger: loggerPino });
    logger.info(`Created store: ${toShortString(this.store)}`);
  }

  public read() {
    return this.store?.readFromFile(this.sessionJSON);
  }

  public write() {
    this.store?.writeToFile(this.sessionJSON);
  }

  public get sessionFolder() {
    return this._sessionFolder;
  }

  public get sessionJSON() {
    return `${this.sessionFolder}/baileys_all.json`;
  }
  public get sessionMultiFile() {
    return `${this.sessionFolder}/baileys_multi`;
  }

  public getState() {
    const result = useMultiFileAuthState(this.sessionMultiFile);
    logger.info('State: ok');
    return result;
  }

  public async toMongoState() {
    const zip = `${this.sessionFolder}.zip`;
    zipFolder(this.sessionFolder, zip);
    const bytes = readBinaryFile(zip);
    fs.rmSync(zip, { recursive: true, force: true });
    return bytes;
  }

  public fromMongoState(bytes: Buffer) {
    const zip = `${this.sessionFolder}.zip`;
    writeBinaryFile(bytes, zip);
    unzipFolder(this.sessionFolder, `${this.sessionFolder}.zip`);
    fs.rmSync(zip, { recursive: true, force: true });
  }

  public clear() {
    fs.rmSync(this.sessionFolder, { recursive: true, force: true });
  }
}
