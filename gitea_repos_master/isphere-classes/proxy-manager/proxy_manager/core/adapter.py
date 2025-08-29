class ProxyAdapter:

    @staticmethod
    def base_format(data):
        server = data.get("server")
        port = data.get("port")
        login = data.get("login")
        password = data.get("password")

        url = ""
        if login and password:
            url += f"{login}:{password}@"

        url += server

        if port:
            url += f":{port}"

        return {
            "http": "http://" + url,
            "https": "http://" + url,
            "server": server,
            "port": port,
            "login": login,
            "password": password,
            "extra_fields": data,
        }

    @staticmethod
    def parse(response, name=None):
        return [ProxyAdapter.parse_one(proxy, name) for proxy in response]

    @staticmethod
    def parse_one(raw_data, name=None):
        data = ProxyAdapter.base_format(raw_data)
        if not name or name == "base":
            return data

        if name == "raw":
            return raw_data

        if name == "simple":
            return {"http": data.get("http"), "https": data.get("https")}

        if name == "httpx":
            return {
                "http://": data.get("http"),
                "https://": data.get("http"),
            }

        if name == "id":
            return data.get("extra_fields", {}).get("id")
