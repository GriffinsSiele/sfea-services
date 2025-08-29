import process from 'process';
import { HealthCheck } from '../logic/livenessprobe/health_check';

const args = process.argv.slice(2);
if (args.length === 0) {
  process.exit(0);
}
HealthCheck.check(parseInt(args[0]));
