import * as os from 'os';

export default class HostnameManager {
  public static get hostname() {
    return os.hostname();
  }
}
