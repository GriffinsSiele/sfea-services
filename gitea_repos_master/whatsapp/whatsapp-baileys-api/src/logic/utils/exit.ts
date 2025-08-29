import * as process from 'process';

export function delayedExist(time = 3_000) {
  setTimeout(() => process.exit(1), time);
}
