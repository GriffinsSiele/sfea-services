import { RegisterProcess } from '../logic/thread/register';

(async function () {
  await new RegisterProcess().run();
})();
