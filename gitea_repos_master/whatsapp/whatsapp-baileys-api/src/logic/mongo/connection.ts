import { Collection, Db, MongoClient } from 'mongodb';
import { MongoFields } from './fields';

export class MongoConnection extends MongoFields {
  private client: MongoClient;
  private db: Db;
  public sessions: Collection;

  constructor(mongoURL: string, db: string, collection: string | null) {
    super();

    this.client = new MongoClient(mongoURL, {
      connectTimeoutMS: 8_000,
      socketTimeoutMS: 8_000,
      maxConnecting: 3,
      serverSelectionTimeoutMS: 5_000
    });
    this.db = this.client.db(db);
    this.sessions = collection ? this.db.collection(collection) : null;
  }

  public async connect(): Promise<MongoConnection> {
    await this.client.connect();
    await this._preprocessCollection();
    return this;
  }

  public async close(): Promise<void> {
    await this.client.close();
  }

  public switchCollection(collection: string): void {
    this.sessions = this.db.collection(collection);
  }

  protected async _preprocessCollection(): Promise<void> {
    await this._createIndexes();
  }

  private async _createIndexes(): Promise<void> {
    if (this.sessions === null) {
      return;
    }
    const indexes = await this.sessions.indexes();
    if (Object.keys(indexes).length === 1) {
      await this.sessions.createIndex([{ lastUse: 1 }]);
    }
  }
}
