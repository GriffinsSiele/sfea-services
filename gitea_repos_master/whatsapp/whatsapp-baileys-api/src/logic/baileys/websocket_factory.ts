import makeWASocket, {
  fetchLatestBaileysVersion
} from '@whiskeysockets/baileys';
import NodeCache from 'node-cache';
import logger from '../logger/winston';
import { StoreManager } from './store_manager';
import loggerPino from '../logger/pino';
import { toShortString } from '../utils/string';

export class WebSocketFactory {
  public static async create(storeManager: StoreManager, extraProps = {}) {
    logger.info('Socket creation...');
    const [{ state, saveCreds }, { version }] = await Promise.all([
      storeManager.getState(),
      fetchLatestBaileysVersion({ timeout: 3_000 })
    ]);
    logger.info(`Baileys version: ${version}`);

    const socket = makeWASocket({
      connectTimeoutMs: 30_000,
      defaultQueryTimeoutMs: 10_000,
      linkPreviewImageThumbnailWidth: 96,
      syncFullHistory: false,
      markOnlineOnConnect: false,
      generateHighQualityLinkPreview: true,
      printQRInTerminal: false,
      msgRetryCounterCache: new NodeCache(),
      getMessage: async () => undefined,
      logger: loggerPino,
      auth: state,
      version,
      ...extraProps
    });
    logger.info(`Created socket: ${toShortString(socket)}`);

    storeManager.store?.bind(socket.ev);
    socket.ev.on('creds.update', saveCreds);
    return socket;
  }
}
