import { randomInt, sleep } from './utils';
import logger from './logger';

export default class SlimerSelector {
  private readonly page!: any;

  constructor(page: any) {
    this.page = page;
  }

  public getElement(selector: string) {
    return this.page.evaluate(function (selector: string) {
      return document.body.querySelector(selector);
    }, selector);
  }

  public getBoundsOfObject(object: HTMLDivElement) {
    return object?.getBoundingClientRect();
  }

  public getBoundsOfElement(selector: string) {
    return this.getBoundsOfObject(this.getElement(selector));
  }

  public getClickCoords(bounds: any) {
    logger.debug('Bounds of element:');
    logger.debug('bounds.left:   ' + bounds.left);
    logger.debug('bounds.top:    ' + bounds.top);
    logger.debug('bounds.width:  ' + bounds.width);
    logger.debug('bounds.height: ' + bounds.height);

    const coords = { x: 0, y: 0 };

    coords.x = Math.round(bounds.left + (Math.random() * bounds.width) / 2);
    coords.y = Math.round(bounds.top + (Math.random() * bounds.height) / 2);

    logger.debug('Getting coords: ' + coords.x + 'x' + coords.y);
    return coords;
  }

  public click(coords: { x: number; y: number }) {
    return this.page.sendEvent('click', coords.x, coords.y, 1, 0);
  }

  public clickOnSelector(selector: string) {
    const bounds = this.getBoundsOfElement(selector);

    if (bounds) {
      this.click(this.getClickCoords(bounds));
    } else {
      return false;
    }
  }

  public setValueBySelector(selector: string, value: string) {
    logger.debug(`Setting value "${value} "in selector`);

    this.page.evaluate(function () {
      document.getElementById('vin').focus();
    });

    for (let i = 0; i < value.length; i++) {
      const char = value.charAt(i);
      this.page.sendEvent('keypress', value.charAt(i));
      logger.debug(`Setting char: ${char}`);
      sleep(150 + randomInt(10, 200));
    }
  }
}
