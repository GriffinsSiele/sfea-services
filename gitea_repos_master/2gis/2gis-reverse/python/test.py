import json
import re
import time

from pydash import get
from requests import Session
from requests_logic.ja3_adapter import JA3Adapter

from python.sign import sign

phones = ['+74832260025', '+79208313140', '+74951997531']

app_key = 'rurbbn3446'
r_key = 'baf4c54e9dae'

for phone in phones:
    start = time.time()
    session = Session()
    adapter = JA3Adapter()
    adapter.set_proxy_server_url('http://172.16.1.254:8009/handle')
    adapter.set_ja3_by_index(0)
    session.mount('https://', adapter)
    session.mount('http://', adapter)

    # response = session.get('https://2gis.ru/')
    #
    # payload = re.findall("JSON\.parse\('({.*}?)'\);\s+var i", response.text)
    # payload = json.loads(payload[0])
    #
    # searchUserHash = get(payload, 'searchUserHash')
    # sessionId = get(payload, 'sessionId')
    # userId = get(payload, 'userId')
    appVersion = '2022-09-26-12'  # TODO cast commitIsoDate from payload

    cookies = {} # response.cookies.get_dict()

    headers = {
        'authority': 'catalog.api.2gis.ru',
        'accept': 'application/json, text/plain, */*',
        'accept-language': 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
        'dnt': '1',
        'origin': 'https://2gis.ru',
        'referer': 'https://2gis.ru/',
        'sec-ch-ua-mobile': '?0',
        'sec-ch-ua-platform': '"Linux"',
        'sec-fetch-dest': 'empty',
        'sec-fetch-mode': 'cors',
        'sec-fetch-site': 'same-site',
    }

    params = {
        'key': app_key,
        'q': phone,
        'fields':
        'items.locale,items.flags,search_attributes,items.adm_div,items.city_alias,items.region_id,items.segment_id,items.reviews,items.point,request_type,context_rubrics,query_context,items.links,items.name_ex,items.org,items.group,items.external_content,items.comment,items.ads.options,items.email_for_sending.allowed,items.stat,items.description,items.geometry.centroid,items.geometry.selection,items.geometry.style,items.timezone_offset,items.context,items.address,items.is_paid,items.access,items.access_comment,items.for_trucks,items.is_incentive,items.paving_type,items.capacity,items.schedule,items.floors,dym,ad,items.rubrics,items.routes,items.reply_rate,items.purpose,items.route_logo,items.has_goods,items.has_apartments_info,items.has_pinned_goods,items.has_realty,items.has_payments,items.is_promoted,items.delivery,items.order_with_cart,search_type,items.has_discount,items.metarubrics,broadcast,items.detailed_subtype,items.temporary_unavailable_atm_services,items.poi_category,filters,widgets',
        'type':
        'adm_div.city,adm_div.district,adm_div.district_area,adm_div.division,adm_div.living_area,adm_div.place,adm_div.region,adm_div.settlement,attraction,branch,building,crossroad,foreign_city,gate,parking,road,route,station,street,coordinates,kilometer_road_sign',
        'page_size': '12',
        'page': '1',
        'locale': 'ru_RU',
        'allow_deleted': 'true',
        'search_device_type': 'desktop',
        'shv': appVersion,
        'viewpoint1': '27.489554491271235, 65.4814610468197',
        'viewpoint2': '53.47884350009943, 47.289514486097765',
        # Session args
        # 'search_user_hash': searchUserHash,
        # 'stat\\[sid\\]': sessionId,
        # 'stat\\[user\\]': userId,
    }

    url = 'https://catalog.api.2gis.ru/3.0/items'
    params['r'] = sign(url, params, r_key)

    response = session.get(url,
                           params=params,
                           cookies=cookies,
                           headers=headers)
    print('items' in response.text, response.text)
    end = time.time()
    print('Time: ', end - start)
