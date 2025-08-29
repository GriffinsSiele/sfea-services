import logger from '../../logic/logger/winston';
import { throwInvalidAccount } from '../../logic/exceptions/check';
import { Socket, WABusinessProfileBusinessHours } from 'bailyes';
import { BusinessResponse } from 'own';

const translateDay = new Map([
  ['mon', 'Пн'],
  ['tue', 'Вт'],
  ['wed', 'Ср'],
  ['thu', 'Чт'],
  ['fri', 'Пт'],
  ['sat', 'Сб'],
  ['sun', 'Вс']
]);

const translateWorkMode: { [k: string]: string } = {
  appointment_only: 'Только по записи',
  open_24h: 'Круглосуточно',
  closed: 'Закрыто'
};

export class BusinessGetter {
  public static async get(
    socket: Socket,
    phone: string
  ): Promise<BusinessResponse> {
    try {
      const response = await socket.getBusinessProfile(phone);
      let output: BusinessResponse = { isBusiness: false };

      if (!response) {
        return output;
      }

      output.isBusiness = true;
      if (response.address) {
        output.businessAddress = response.address;
      }
      if (response.description) {
        output.businessDescription = response.description;
      }
      if (response.category) {
        output.businessCategory = response.category;
      }
      if (response.email) {
        output.businessEmail = response.email;
      }
      if (Array.isArray(response.website)) {
        output.list__businessWebsite = response.website;
      }
      if (response.business_hours) {
        output = {
          ...output,
          ...BusinessGetter.castSchedule(response.business_hours)
        };
      }

      return output;
    } catch (e) {
      logger.warn(`Error in getting business profile: ${e}`);
      throwInvalidAccount(e);
    }
  }

  private static castSchedule(work: WABusinessProfileBusinessHours) {
    const output = { businessTimezone: work.timezone, businessSchedule: '' };

    const castInterval = (interval: number | undefined) => {
      if (!interval) {
        return null;
      }
      const hour = Math.floor(interval / 60);
      const min = interval % 60;
      const to = (v: number) => v.toString().padStart(2, '0');
      return `${to(hour)}:${to(min)}`;
    };

    const castAppointment = (mode?: string) => {
      if (mode && mode in translateWorkMode) {
        return translateWorkMode[mode];
      }
      return null;
    };

    for (const [day, text] of translateDay) {
      const period = work.business_config?.find((d) => d.day_of_week === day);
      if (period) {
        const open = castInterval(period.open_time);
        const close = castInterval(period.close_time);
        const appointment = castAppointment(period.mode);
        const intervalWork = appointment
          ? appointment
          : `${open ?? '??'} - ${close ?? '??'}`;

        output.businessSchedule += `${text}: ${intervalWork ?? 'Не указано'}\n`;
      }
    }

    return output;
  }
}
