import * as fs from 'fs';

export class HealthCheck {
  private static readonly file = '/tmp/app_alive.pid';

  public static check(minPeriod: number) {
    if (!fs.existsSync(this.file)) {
      this.checkpoint();
    }
    const lastTime = parseInt(this.__read_timestamp());
    const diff = (Date.now() - lastTime) / 1000;
    process.exit(diff > minPeriod ? 1 : 0);
  }

  private static __read_timestamp(): string {
    return fs.readFileSync(this.file, 'utf8');
  }

  public static checkpoint(timestamp?: number): void {
    if (!timestamp) {
      timestamp = Date.now();
    }
    fs.writeFileSync(this.file, timestamp.toString());
  }
}
