import random
from email.message import Message

from pydash import get, find, filter_, omit_by
from requests import Request, RequestException
from requests.adapters import HTTPAdapter
from urllib3._collections import HTTPHeaderDict
from urllib3.response import HTTPResponse

from requests_logic.ja3_examples import ja3_examples


class JA3Setter:
    _ja3_set = ja3_examples[0]

    def set_random_ja3(self, examples=None):
        examples = examples if examples else ja3_examples
        self._ja3_set = random.choice(examples)

    def set_random_ja3_by_tls(self, tls_version="1.3"):
        tls_ja3 = {"1.3": lambda j: j.startswith("772"), "any": lambda j: j}
        condition = tls_ja3[tls_version] if tls_version in tls_ja3 else tls_ja3["any"]
        self.set_random_ja3(filter_(ja3_examples, lambda j: condition(j["Ja3"])))

    def set_ja3_by_index(self, index):
        self._ja3_set = ja3_examples[index if 0 <= index < len(ja3_examples) else 0]

    def set_ja3(self, ja3, user_agent=None):
        if ja3 and user_agent:
            self._ja3_set = {"Ja3": ja3, "UserAgent": user_agent}
        elif ja3:
            res = find(ja3_examples, lambda j: j["Ja3"] == ja3)
            self._ja3_set = res if res else {"Ja3": ja3, "UserAgent": None}
        else:
            raise ValueError("Pass JA3 as argument")

    def _validate_ja3(self, headers):
        ja3 = get(self._ja3_set, "Ja3")
        user_agent = get(self._ja3_set, "UserAgent")
        if ja3 and user_agent:
            return True

        header_key = find(
            headers.keys() if headers else [],
            lambda k: k.lower() == "user-agent"
            and not headers[k].startswith("python-requests"),
        )
        if header_key:
            self._ja3_set["UserAgent"] = headers[header_key]
            return True

        return False

    def get_ja3(self):
        return {
            "ja3": get(self._ja3_set, "Ja3"),
            "user-agent": get(self._ja3_set, "UserAgent"),
        }


class JA3Adapter(HTTPAdapter, JA3Setter):
    proxy_url = ""
    proxy_method = "POST"
    proxy_headers = {"Content-Type": "application/json"}

    def set_proxy_server_url(self, url, method=None, headers=None):
        self.proxy_url = url
        self.proxy_method = method if method else self.proxy_method
        self.proxy_headers = headers if headers else self.proxy_headers

    def send(self, r, stream=False, timeout=None, verify=True, cert=None, proxies=None):
        proxy_payload = self._prepare_proxy_payload(r, timeout, verify, proxies)
        request_to_proxy = Request(
            self.proxy_method,
            self.proxy_url,
            json=proxy_payload,
            headers=self.proxy_headers,
        )

        response_from_proxy = super().send(
            request_to_proxy.prepare(), stream, timeout, verify, cert, proxies=None
        )
        response_from_proxy = response_from_proxy.json()

        body = self.validate_body_response(response_from_proxy)
        headers = self._parse_headers(get(response_from_proxy, "payload.headers", {}))
        http_response_fake = HTTPResponse(
            body=body,
            headers=headers,
            status=get(response_from_proxy, "payload.status", None),
            request_method=r.method,
            request_url=r.url,
        )

        m = Message()
        m._headers = [(key, value) for key, value in headers.items()]
        http_response_fake._original_response = http_response_fake
        http_response_fake._original_response.msg = m

        http_response_fake = self.build_response(r, http_response_fake)
        http_response_fake._content = body

        # Manual merge cookies: union(requests.cookies, response.cookies)
        # c = cookiejar_from_dict({cookie['name']: cookie['value'] for cookie in get(response_from_proxy, 'payload.cookies', [])})
        # http_response_fake.cookies = merge_cookies(c, http_response_fake.cookies)

        return http_response_fake

    def _prepare_proxy_payload(self, req, timeout, verify, proxies):
        cookies = [{"Name": key, "Value": value} for key, value in req._cookies.items()]

        body = req.body
        if body:
            body = body.decode() if not isinstance(body, str) else body

        is_ok_ja3 = self._validate_ja3(req.headers)
        if not is_ok_ja3:
            raise ValueError('Set valid header or call "set_ja3" method')

        return {
            **omit_by(self._ja3_set, lambda _, k: k.startswith("_")),
            "Method": req.method.upper(),
            "Url": req.url,
            "Body": body,
            "Cookies": cookies,
            "Proxy": get(proxies, "http", None),
            "Timeout": timeout,
            "Headers": dict(req.headers) if req.headers else None,
            "DisableRedirect": True,
            "InsecureSkipVerify": not verify,
        }

    def _parse_headers(self, headers):
        headers_dict = HTTPHeaderDict()
        for k, header in headers.items():
            if k.lower() == "set-cookie":
                for cookie in header.split("/,/"):
                    headers_dict.add(k, cookie)
            else:
                headers_dict.add(k, header)
        return headers_dict

    def validate_body_response(self, response_from_proxy):
        if not get(response_from_proxy, "success", False):
            raise RequestException(
                get(
                    response_from_proxy,
                    "error",
                    f"Error during sending to proxy. Ja3: {self._ja3_set}.",
                )
            )

        body = get(response_from_proxy, "payload.text", "")
        if (
            "uTlsConn.Handshake() error: remote error: tls: error decoding message"
            in body
        ):
            raise RequestException(
                f"Possibly gzip response content type. Ja3: {self._ja3_set}. Response: {body}"
            )

        if "uTlsConn.Handshake()" in body or body.startswith("->"):
            raise RequestException(
                f"Error in Go JA3 Server occurred. Ja3: {self._ja3_set}. Response: {body}"
            )

        return body.encode("utf-8")
