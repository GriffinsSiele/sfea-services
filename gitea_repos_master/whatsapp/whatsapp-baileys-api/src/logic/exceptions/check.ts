import { AccountLocked } from './exceptions';

export function throwInvalidAccount(exception: Error) {
  const errorText = exception.toString();
  if (
    errorText.includes('Connection Closed') ||
    errorText.includes('Session is invalid')
  ) {
    throw new AccountLocked(errorText);
  }
}

export function isHiddenData(exception: Error) {
  const errorText = exception.toString();
  return (
    errorText.includes('Cannot read properties of undefined') ||
    errorText.includes('not-authorized')
  );
}

export function isEmptyData(exception: Error) {
  const errorText = exception.toString();
  return errorText.includes('item-not-found');
}
