import logger from '../../logic/logger/winston';
import {
  isHiddenData,
  throwInvalidAccount
} from '../../logic/exceptions/check';
import { Socket } from 'bailyes';
import { StatusResponse } from 'own';

export class StatusGetter {
  public static async get(
    socket: Socket,
    phone: string
  ): Promise<StatusResponse> {
    try {
      const response = await socket.fetchStatus(phone);
      const output: StatusResponse = {
        statusHidden: false
      };

      if (response.status) {
        output.status = response.status;
        if (
          response.setAt &&
          !(response?.setAt ?? '').toString().includes('Invalid')
        ) {
          output.statusSetAt = response.setAt.toISOString();
        }
      }

      return output;
    } catch (e) {
      if (isHiddenData(e)) {
        return { statusHidden: true };
      }
      // Ошибка на уровне Baileys, падает в случае отсутствия статуса
      if ('Cannot read properties of undefined' in e.toString()) {
        return { statusHidden: false };
      }
      logger.warn(`Error in getting status profile: ${e}`);
      throwInvalidAccount(e);
    }
  }
}
