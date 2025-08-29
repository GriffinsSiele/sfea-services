// eslint-disable-next-line @typescript-eslint/no-var-requires
const page = require('webpage').create();

export default class PageFactory {
  public static createPage() {
    page.settings.userAgent =
      'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:59.0) Gecko/20100101 Firefox/59.0';
    page.captureContent = [/text/, /html/, /json/];
    return page;
  }
}
