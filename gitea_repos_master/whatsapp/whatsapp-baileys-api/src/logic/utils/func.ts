import logger from '../logger/winston';

const stateLocked = new Map();

function now(offset_min = 0) {
  const now = new Date();
  now.setTime(now.getTime() + offset_min * 1000 * 60);
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  const hours = String(now.getHours()).padStart(2, '0');
  const minutes = String(now.getMinutes()).padStart(2, '0');
  return `${year}-${month}-${day}T${hours}:${minutes}`;
}

export async function safe(
  func: Function,
  raise_exception: boolean = true,
  default_value: any = null,
  ...args: any[]
) {
  const funcName = func?.name || 'unknown';

  try {
    const blockTimestamp = stateLocked.get('blocked');
    if (blockTimestamp && blockTimestamp > now()) {
      logger.warn(
        `Skipped mongo operation ${funcName} due to temp lock until ${blockTimestamp}`
      );
      return default_value;
    }

    const r = await func(...args);
    stateLocked.set('failure_in_row', 0);
    return r;
  } catch (error) {
    stateLocked.set(
      'failure_in_row',
      (stateLocked.get('failure_in_row') || 0) + 1
    );
    if (stateLocked.get('failure_in_row') > 2) {
      stateLocked.set('blocked', now(1.5));
      stateLocked.set('failure_in_row', 0);
    }

    if (raise_exception) {
      throw error;
    }

    const text = error.toString().replace('\n', '');
    logger.warn(`Suppressed error on call '${funcName}' (${args}): ${text}`);
    return default_value;
  }
}
