from requests_logic.ja3_examples import ja3_examples

cases_mismatch = {
    'POST_DATA': {
        'request': {
            'url': 'https://tls.peet.ws/api/all',
            'method': 'GET',
            'headers': {
                'user-agent': ja3_examples[1]['UserAgent']
            }
        },
        'match': {
            ja3_examples[1]['Ja3']: 'ja3',
            ja3_examples[1]['UserAgent']: 'request'
        }
    }}