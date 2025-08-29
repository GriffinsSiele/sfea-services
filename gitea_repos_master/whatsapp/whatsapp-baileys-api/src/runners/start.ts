import { StartProcess } from '../logic/thread/starts';

(async function () {
  await new StartProcess().run();
})();
