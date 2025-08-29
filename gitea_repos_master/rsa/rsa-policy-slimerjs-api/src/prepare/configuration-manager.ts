import ProxyManager from './proxy-manager';
import * as fs from 'fs';

export default class ConfigurationManager {
  private static readonly default = {
    allowMedia: false,
    errorLogFile: 'error.log',
    loadImages: false,
    viewportWidth: 1280,
    viewportHeight: 950,
    printDebugMessages: false,
    webSecurityEnabled: false
  };

  public static async generate() {
    const proxy = await ProxyManager.getProxyToSlimer();
    const configuration = { ...ConfigurationManager.default, ...proxy };
    fs.writeFileSync(
      'config.json',
      JSON.stringify(configuration, null, 4),
      'utf8'
    );
  }
}
