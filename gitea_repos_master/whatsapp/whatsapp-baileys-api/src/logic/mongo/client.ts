import { DeleteResult, InsertOneResult, UpdateResult } from 'mongodb';
import { MongoConnection } from './connection';
import { Period } from './fields';
import {
  castPeriodToSeconds,
  nowLocalDate,
  nowWithOffset
} from '../utils/date';
import { Session } from 'own';

export class MongoSessions extends MongoConnection {
  constructor(mongoURL: string, db: string, collection: string | null) {
    super(mongoURL, db, collection);
  }

  private _filterOne(session: Session) {
    return { _id: session['_id'] };
  }

  private _filterExcludeLock(offset = 0) {
    return {
      $or: [
        { [this.nextUse]: { $lt: nowWithOffset(offset) } },
        { [this.nextUse]: null }
      ]
    };
  }

  public _filterIncludeLock(offset = 0) {
    return { [this.nextUse]: { $gt: nowWithOffset(offset) } };
  }

  public __nextUseDelay(delay: number | undefined): Date | undefined {
    delay = delay === undefined ? delay : this.nextUseDelay;
    return nowWithOffset(delay);
  }

  public async sessionLock(
    session: Session,
    period?: Period
  ): Promise<UpdateResult> {
    const nextUse = nowWithOffset(
      castPeriodToSeconds(period ? period : this.blockTime)
    );
    const update = { $set: { [this.nextUse]: nextUse } };
    return await this.sessions.updateOne(this._filterOne(session), update);
  }

  public async sessionBlock(session: Session): Promise<UpdateResult> {
    const update = { $set: { [this.active]: false } };
    return await this.sessions.updateOne(this._filterOne(session), update);
  }

  public async countActive(): Promise<number> {
    const filter = { ...this.defaultFilter, ...this._filterExcludeLock() };
    return await this.sessions.countDocuments(filter);
  }

  public async getSessions() {
    const filter = this.defaultFilter;
    const projection = this.projection ? this.projection : undefined;
    const cursor = this.sessions.find(filter).project(projection);
    return await cursor.toArray();
  }

  public async getSession(
    countUse = 1,
    ignoreNextUse = false
  ): Promise<Session> {
    const update = {
      $set: {
        [this.lastUse]: nowLocalDate()
      },
      $inc: { count_use: countUse }
    };
    const filter = {
      ...this.defaultFilter,
      ...(ignoreNextUse ? {} : this._filterExcludeLock())
    };
    const projection = this.projection ? this.projection : undefined;
    const sort = this.defaultSort;
    return (
      await this.sessions.findOneAndUpdate(filter, update, {
        sort,
        projection
      })
    ).value;
  }

  public async sessionSuccess(
    session: Session,
    countSuccess = 1,
    nextUseDelay?: number
  ): Promise<UpdateResult> {
    const update = {
      $inc: { count_success: countSuccess },
      $set: { [this.nextUse]: this.__nextUseDelay(nextUseDelay) }
    };
    return await this.sessions.updateOne(this._filterOne(session), update);
  }

  public async sessionUse(session: Session): Promise<UpdateResult> {
    const update = {
      $inc: { count_use: 1 }
    };
    return await this.sessions.updateOne(this._filterOne(session), update);
  }

  public async add(data: Object): Promise<InsertOneResult> {
    const payload: any = {
      [this.active]: true,
      count_use: 0,
      count_success: 0,
      created: nowLocalDate(),
      [this.lastUse]: null,
      [this.nextUse]: null,
      ...data
    };
    return await this.sessions.insertOne(payload);
  }

  public async sessionUpdate(
    session: Session,
    payload: any,
    unsetPayload?: any
  ): Promise<UpdateResult> {
    const update = {
      $set: payload,
      ...(unsetPayload ? { $unset: unsetPayload } : {})
    };
    return await this.sessions.updateOne(this._filterOne(session), update);
  }

  public async sessionDelete(session: Session): Promise<DeleteResult> {
    return await this.sessions.deleteOne(this._filterOne(session));
  }
}
