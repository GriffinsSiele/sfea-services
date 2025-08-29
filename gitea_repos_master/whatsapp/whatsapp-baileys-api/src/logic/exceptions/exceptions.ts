class MyError extends Error {
  constructor(message: string) {
    super(message);
  }
}
export class ErrorNoReturnToQueue extends MyError {}
export class ErrorReturnToQueue extends MyError {}
export class AccountBlocked extends ErrorReturnToQueue {}
export class AccountLocked extends ErrorReturnToQueue {}

export class NoDataError extends ErrorNoReturnToQueue {}
export class LimitError extends ErrorReturnToQueue {}
export class TimeoutError extends ErrorReturnToQueue {}
export class ConnectionError extends ErrorReturnToQueue {}
export class UnknownError extends ErrorReturnToQueue {}
