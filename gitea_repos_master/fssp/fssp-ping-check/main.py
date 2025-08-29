import os
from time import sleep

import requests
from dotenv import load_dotenv

env = '.env'
load_dotenv(env)

headers = {
    'User-Agent': 'android',
    'Host': 'api.fssprus.ru',
    'Connection': 'close',
}

params = {
    'ip_number': '1233/1233/1233-ИП',
    'type': 'num',
    'udid': 'c7941448-6f1e-42ed-b9ea-7b46a0d52659',
    'ver': '49',
}


def telegram_bot_send_text(bot_message):
    bot_token = os.getenv('BOT_TOKEN')
    bot_chat_id = os.getenv('BOT_CHAT_ID')
    send_text = 'https://api.telegram.org/bot' + bot_token + '/sendMessage?chat_id=' + bot_chat_id + '&parse_mode=Markdown&text=' + bot_message
    response = requests.get(send_text)

    return response.json()


while True:
    response = requests.get('https://api.fssprus.ru/api/v2/search', params=params, headers=headers)
    response.encoding = 'utf-8'

    if 'На данный момент проводятся технические работы' in response.text:
        sleep(60 * 60 * 6)  # 6Hr
    else:
        telegram_bot_send_text('ФССП. Мобильная версия стала возвращать ответ отличный от ошибки.\nЕсли вы читаете это, то сообщите Максиму')
        break
