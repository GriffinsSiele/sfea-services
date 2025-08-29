import { DownloadImage } from '../../logic/adapter/image';
import logger from '../../logic/logger/winston';
import {
  isEmptyData,
  isHiddenData,
  throwInvalidAccount
} from '../../logic/exceptions/check';
import { ImageFormat, Socket } from 'bailyes';
import { AvatarResponse } from 'own';

export class AvatarGetter {
  public static async get(
    socket: Socket,
    phone: string,
    options = { download: true, format: 'preview' as ImageFormat }
  ): Promise<AvatarResponse | {}> {
    try {
      const response = await socket.profilePictureUrl(phone, options.format);
      const output: AvatarResponse = { hasAvatar: false, avatarHidden: false };

      if (!response) {
        return output;
      }

      output.hasAvatar = true;
      output[`${options.format}URL`] = response;
      if (options.download) {
        output[`${options.format}Base64`] = await DownloadImage.fromURL(
          response
        );
      }

      return output;
    } catch (e) {
      if (isHiddenData(e)) {
        return { avatarHidden: true, hasAvatar: true };
      }
      if (isEmptyData(e)) {
        return { hasAvatar: false, avatarHidden: false };
      }
      logger.warn(`Error in getting avatar profile: ${e}`);
      throwInvalidAccount(e);
    }
  }
}
