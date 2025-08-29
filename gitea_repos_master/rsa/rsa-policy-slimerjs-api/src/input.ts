import SlimerSelector from './slimerSelector';
import { randomInt, sleep } from './utils';

export function inputVIN(page: any, vin: string) {
  const sw = new SlimerSelector(page);
  console.log(page.title);

  page.onResourceReceived = function (response: any) {
    console.log(
      'Response (#' +
        response.id +
        ', stage "' +
        response.stage +
        '"): ' +
        JSON.stringify(response)
    );
  };

  sleep(4_000);
  console.log('wait');

  console.log(sw.clickOnSelector('#tsBlockTab'));
  sleep(100 + randomInt(500, 1_000));

  console.log(sw.clickOnSelector('#vin'));
  sleep(100 + randomInt(100, 300));

  sw.setValueBySelector('#vin', vin);

  console.log(sw.clickOnSelector('#buttonFind'));
}
