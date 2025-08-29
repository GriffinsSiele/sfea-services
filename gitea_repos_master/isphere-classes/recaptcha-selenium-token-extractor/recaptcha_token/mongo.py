import requests as requests


class MongoTokenAPI:
    def __init__(self, server, port):
        self.base_url = f'http://{server}:{port}'

    def request(self, method, url, data=None, params=None):
        response = requests.request(method, self.base_url + url, json=data, params=params)
        return response.json()

    def get_v2(self, sitekey):
        return self.request('GET', '/v2', params={'sitekey': sitekey})

    def get_v3(self, sitekey, action):
        return self.request('GET', '/v3', params={'sitekey': sitekey, 'action': action})

    def get_count_v2(self, sitekey):
        return self.request('GET', '/count/v2', params={'sitekey': sitekey})

    def get_count_v3(self, sitekey, action):
        return self.request('GET', '/count/v3', params={'sitekey': sitekey, 'action': action})

    def add_v2(self, token, sitekey):
        return self.request('POST', '/v2', data={'TOKEN': token, 'sitekey': sitekey})

    def add_v3(self, token, sitekey, action):
        return self.request('POST', '/v3', data={'TOKEN': token, 'sitekey': sitekey, 'action': action})
