import { PROXY_LOGIN, PROXY_PASSWORD } from '../env';

export default class ProxyManager {
  public static async getProxy() {
    const params = new URLSearchParams();
    params.append('proxygroup', '5');
    params.append('status', '1');
    params.append('limit', '1');
    params.append('order', 'lasttime');

    const basicAuth = btoa(`${PROXY_LOGIN}:${PROXY_PASSWORD}`);
    const headers = new Headers();
    headers.append('Authorization', `Basic ${basicAuth}`);

    const response = await fetch(
      `https://i-sphere.ru/2.00/get_proxies.php?${params}`,
      { headers }
    );
    return (await response.json())[0];
  }

  public static async getProxyToSlimer() {
    const proxy = await ProxyManager.getProxy();
    return {
      proxy: `${proxy.server}:${proxy.port}`,
      proxyAuth: `${proxy.login}:${proxy.password}`,
      proxyType: 'http'
    };
  }
}
