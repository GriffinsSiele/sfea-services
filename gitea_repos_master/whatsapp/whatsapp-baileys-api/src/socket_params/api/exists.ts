import logger from '../../logic/logger/winston';
import { throwInvalidAccount } from '../../logic/exceptions/check';
import { Socket } from 'bailyes';
import { ExistsResponse } from 'own';

export class ExistsGetter {
  public static async get(
    socket: Socket,
    phone: string
  ): Promise<ExistsResponse> {
    try {
      const response = await socket.onWhatsApp(phone);
      return { _isExists: response?.[0]?.exists === true } as ExistsResponse;
    } catch (e) {
      logger.warn(`Error in getting existence profile: ${e}`);
      throwInvalidAccount(e);
    }
  }
}
