import PageFactory from './pageFactory';
import { sleep } from './utils';
import { parseTable } from './injectable/parserTable.js';
import KeyDBAdapter from './adapter/keydb/adapter';
import { inputVIN } from './input';
import logger from './logger';

const page = PageFactory.createPage();

page.open(
  'https://dkbm-web.autoins.ru/dkbm-web-1.0/policyInfo.htm',
  async function () {
    sleep(4000);
    logger.info('123');

    inputVIN(page, 'XU42824NEJ0003771');

    sleep(6000);
    const selector = '.policies-tbl';

    const data = page.evaluate(
      function (selector: string, f: string) {
        const table = document.body.querySelector(selector);
        return eval(`a = ${f}`)(table, false);
      },
      selector,
      parseTable.toString()
    );

    const output = KeyDBAdapter.toKeyDB(data);
    logger.debug(JSON.stringify(output));

    setTimeout(() => {
      page.close();
      phantom.exit();
    }, 4000);
  }
);
