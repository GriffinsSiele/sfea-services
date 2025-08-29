import logger from '../logger/winston';
import { WhatsappAdapter } from '../adapter/whatsapp';
import { ExistsGetter } from '../../socket_params/api/exists';
import { ErrorReturnToQueue, NoDataError } from '../exceptions/exceptions';
import { StatusGetter } from '../../socket_params/api/status';
import { BusinessGetter } from '../../socket_params/api/business';
import { AvatarGetter } from '../../socket_params/api/avatar';
import { ProfileAdapter } from '../adapter/profile';
import { toShortString, toShortStringRec } from '../utils/string';
import { Socket } from 'bailyes';
import { ProfileResponse } from 'own';

export class SearchManager {
  private readonly socket: Socket;

  constructor(socket: Socket) {
    this.socket = socket;
  }

  public async search(payload: string) {
    const start = Date.now();
    const response = await this._search(payload);
    const end = Date.now();
    logger.info(`API elapsed time: ${(end - start) / 1000} sec`);
    return response;
  }

  private async _search(payload: string) {
    const phone = WhatsappAdapter.phoneNumberToJID(payload);

    const callable = [
      ExistsGetter.get(this.socket, phone),
      StatusGetter.get(this.socket, phone),
      BusinessGetter.get(this.socket, phone),
      AvatarGetter.get(this.socket, phone, {
        format: 'preview',
        download: false
      }),
      AvatarGetter.get(this.socket, phone, {
        format: 'image',
        download: false
      })
    ];

    const response = await Promise.allSettled(callable);
    const [exists, status, business, avatarCropped, avatarFull] = response;
    logger.info(`Responses: ${toShortStringRec(response)}`);

    if (exists.status == 'rejected') {
      throw new ErrorReturnToQueue(
        'Reject existence profile request, try again'
      );
    }

    if (!(exists as any)?.value?._isExists) {
      logger.info(`User doesn't exist: ${payload}`);
      throw new NoDataError('Not found user');
    }

    const output = {
      ...(exists.status === 'fulfilled' ? exists?.value : {}),
      ...(status.status === 'fulfilled' ? status?.value : {}),
      ...(business.status === 'fulfilled' ? business?.value : {}),
      ...(avatarFull.status === 'fulfilled' ? avatarFull?.value : {}),
      ...(avatarCropped.status === 'fulfilled' ? avatarCropped?.value : {})
    };

    const castedOutput = ProfileAdapter.cast(output as ProfileResponse);
    logger.info(`Answer: ${toShortString(castedOutput)}`);
    return [castedOutput];
  }
}
