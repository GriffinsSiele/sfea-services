export type PeriodFormats = 'seconds' | 'minutes' | 'hours';
export type Period = Partial<Record<PeriodFormats, number>>;

export class MongoFields {
  private _nextUse: string;
  private _lastUse: string;
  private _active: string;
  private _session: string;
  private _blockTime: Period;
  private _lockTime: Period;
  private _defaultFilter: Partial<Record<string, any>> | null;
  private _projection: Partial<Record<string, any>> | null;
  private _nextUseDelay: number;
  public defaultSort: any;

  constructor() {
    this._nextUse = 'next_use';
    this._lastUse = 'last_use';
    this._active = 'active';
    this._session = 'session';

    this.blockTime = { hours: 8 };
    this.lock_time = { minutes: 10 };

    this.nextUseDelay = 1;

    this.defaultFilter = { [this._active]: true };
    this.defaultSort = [{ [this.lastUse]: 1 }];

    this._projection = { [this._session]: 1 };
  }

  public get nextUse(): string {
    return this._nextUse;
  }

  public set nextUse(value: string) {
    this._nextUse = value;
  }

  public get active(): string {
    return this._active;
  }

  public set active(value: string) {
    this._active = value;
  }

  public get session(): string {
    return this._session;
  }

  public set session(value: string) {
    this._session = value;
  }

  public get lastUse(): string {
    return this._lastUse;
  }

  public set lastUse(value: string) {
    this._lastUse = value;
  }

  public get blockTime(): Period {
    return this._blockTime;
  }

  public set blockTime(value: Period) {
    this._blockTime = value;
  }

  public get lock_time(): Period {
    return this._lockTime;
  }

  public set lock_time(value: Period) {
    this._lockTime = value;
  }

  public get defaultFilter(): Partial<Record<string, any>> | null {
    return this._defaultFilter;
  }

  public set defaultFilter(
    filter_options: Partial<Record<string, any>> | null
  ) {
    this._defaultFilter = filter_options;
  }

  public get projection(): Partial<Record<string, any>> | null {
    return this._projection;
  }

  public set projection(options: Partial<Record<string, any>> | null) {
    this._projection = options;
  }

  public get nextUseDelay(): number {
    return this._nextUseDelay;
  }

  public set nextUseDelay(value: number) {
    this._nextUseDelay = value >= 0 ? value : 1;
  }
}
