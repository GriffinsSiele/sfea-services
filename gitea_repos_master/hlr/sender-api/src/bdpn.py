from xml.dom import NoDataAllowedErr
import requests
from bs4 import BeautifulSoup
import re
from pydash import get


class BDPN:
    @staticmethod
    def get_operator(phone):
        try:
            if phone[0:2] != '79':
                raise NoDataAllowedErr()
            data = {'num': phone[-10:]}
            response = requests.post('https://www.niir.ru/bdpn/bdpn-proverka-nomera/', data=data)
            soup = BeautifulSoup(response.text, "html.parser")
            el = soup.find('div', {'class': 'elementor-widget-shortcode'})
            answer = re.findall('Оператор: "(.*)" ', el.text)
        except:
            answer = []
        return get(answer, '0', '')
