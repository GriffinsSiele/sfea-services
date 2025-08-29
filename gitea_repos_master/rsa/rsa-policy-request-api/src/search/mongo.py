import logging

from pydash import get


def get_count_v3_safe(mongo, sitekey, action):
    try:
        count_json = mongo.get_count_v3(sitekey, action)
        # TODO исправить когда будет нормально считать сервер
        return 1  #get(count_json, 'count')
    except Exception as e:
        logging.error(e)

    return 0
