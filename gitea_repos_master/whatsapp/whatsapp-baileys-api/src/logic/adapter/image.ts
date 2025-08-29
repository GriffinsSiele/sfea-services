import fetch from 'node-fetch';
import logger from '../logger/winston';

export class DownloadImage {
  public static async fromURL(url: string) {
    const response = await fetch(url);
    const buf = await response.arrayBuffer();
    if (buf.byteLength <= 30) {
      logger.warn(`Not returned image bytes. Response: ${buf}`);
      return null;
    }
    if (!buf) {
      throw Error('Error on download avatar');
    }
    return 'data:image/png;base64,' + Buffer.from(buf).toString('base64');
  }
}
