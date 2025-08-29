from requests_logic.ja3_examples import ja3_examples

cases_match = {
    'POST_DATA': {
        'request': {
            'url': 'http://httpbin.org/post',
            'method': 'POST',
            'data': "12344",
            'headers': {
                'user-agent': ja3_examples[1]['UserAgent']
            }
        },
        'compare': ['json', 'headers', 'cookies', 'status']
    },
    'POST_JSON': {
        'request': {
            'url': 'http://httpbin.org/post',
            'method': 'POST',
            'json': '{"1": "status"}',
            'headers': {
                'user-agent': ja3_examples[1]['UserAgent']
            }
        },
        'compare': ['json', 'headers', 'cookies', 'status']
    },
    'STATUS': {
        'request': {
            'url': 'http://httpbin.org/status/404',
            'method': 'GET',
            'headers': {
                'user-agent': ja3_examples[1]['UserAgent']
            }
        },
        'compare': ['text', 'headers', 'cookies', 'status']
    },
    'HEADER_GET': {
        'request': {
            'url': 'http://httpbin.org/headers',
            'method': 'GET',
            'headers': {
                'user-agent': ja3_examples[1]['UserAgent']
            }
        },
        'compare': ['headers', 'cookies', 'status']
    },
    'HEADER_SET': {
        'request': {
            'url': 'http://httpbin.org/response-headers',
            'method': 'GET',
            'params': {
                'FREE': '1'
            },
            'headers': {
                'user-agent': ja3_examples[1]['UserAgent']
            }
        },
        'compare': ['text', 'headers', 'cookies', 'status']
    },
    'COOKIES_SET': {
        'request': {
            'url': 'http://httpbin.org/cookies/set/3/3',
            'method': 'GET',
            'cookies': {
                '2': '2'
            },
            'headers': {
                'user-agent': ja3_examples[1]['UserAgent']
            }
        },
        'compare': ['text', 'headers', 'cookies', 'status']
    },
    'COOKIES_NO_REDIRECT': {
        'request': {
            'url': 'http://httpbin.org/cookies/set/3/3',
            'method': 'GET',
            'allow_redirects': False,
            'cookies': {
                '2': '2'
            },
            'headers': {
                'user-agent': ja3_examples[1]['UserAgent']
            }
        },
        'compare': ['text', 'headers', 'cookies', 'status']
    },
    'USER_AGENT': {
        'request': {
            'url': 'http://httpbin.org/user-agent',
            'method': 'GET',
            'allow_redirects': False,
            'headers': {
                'user-agent': ja3_examples[1]['UserAgent']
            }
        },
        'compare': ['text', 'headers', 'cookies', 'status']
    },
    'FILE': {
        'request': {
            'url': 'http://httpbin.org/image',
            'method': 'GET',
            'allow_redirects': False,
            'headers': {
                'user-agent': ja3_examples[1]['UserAgent']
            }
        },
        'compare': ['text', 'headers', 'cookies', 'status']
    },
    'REDIRECT_TRUE': {
        'request': {
            'url': 'http://httpbin.org/redirect-to?url=http://httpbin.org/get',
            'method': 'GET',
            'allow_redirects': True,
            'headers': {
                'user-agent': ja3_examples[1]['UserAgent']
            }
        },
        'compare': ['headers', 'cookies', 'status']
    },
    'REDIRECT_FALSE': {
        'request': {
            'url': 'http://httpbin.org/redirect-to?url=http://httpbin.org/get',
            'method': 'GET',
            'allow_redirects': False,
            'headers': {
                'user-agent': ja3_examples[0]['UserAgent']
            }
        },
        'compare': ['text', 'headers', 'cookies', 'status']
    },
    'VK.COM': {
        'request': {
            'url': 'http://vk.com',
            'method': 'GET',
            'allow_redirects': True,
            'cookies': {
                'remixstlid': '9121136038393847119_wtNWCLOjsTTroDdu6J9ZyevnZXc61GATyhkPoBOm9mz',
                'remixstid': '1701932965_R2Quvtzm6y8E7sRr6qK0dMuXtZb75HgYPN0Qemmg1sP',
                'remixlgck': '716aa7a31505070ab9'
            },
            'headers': {
                'user-agent': ja3_examples[0]['UserAgent']
            }
        },
        'compare': ['cookies', 'status']
    },
    'GITEA': {
        'request': {
            'url': 'https://git.i-sphere.ru/kovinevmv',
            'method': 'GET',
            'allow_redirects': True,
            'headers': {
                'user-agent': ja3_examples[0]['UserAgent']
            }
        },
        'compare': ['headers', 'cookies', 'status']
    },
}
