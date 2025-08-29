import { Period } from '../mongo/fields';

export const sleep = (ms: number) => new Promise((r) => setTimeout(r, ms));
export function nowLocal() {
  return nowLocalDate().toISOString();
}

export function nowLocalDate() {
  const date = new Date();
  return new Date(date.getTime() - date.getTimezoneOffset() * 60000);
}

export function nowWithOffset(seconds = 0) {
  const currentDate = nowLocalDate();
  currentDate.setSeconds(currentDate.getSeconds() + seconds);
  return currentDate;
}

export function castPeriodToSeconds(period: Period) {
  if ('seconds' in period) {
    return period.seconds;
  }
  if ('minutes' in period) {
    return period.minutes * 60;
  }
  if ('hours' in period) {
    return period.hours * 60 * 60;
  }
}
