import logging
import re
import time
from typing import Any

# from urllib.parse import urlparse
#
# from mysql.connector import connect
from pydash import get


class MetaSingleton(type):
    _instances: Any = {}

    def __call__(cls, *args, **kwargs):
        if cls not in cls._instances:
            cls._instances[cls] = super(MetaSingleton, cls).__call__(*args, **kwargs)
        return cls._instances[cls]


class RegionLocateDB(metaclass=MetaSingleton):
    global_regions = ["Российская Федерация"]

    def __init__(self):
        pass
        # url_parsed = urlparse(MYSQL_ROSSVYAZ_URL)
        #
        # logging.info("Connection to database")
        # self.connection = connect(
        #     connect_timeout=5,
        #     host=url_parsed.hostname,
        #     user=url_parsed.username,
        #     port=url_parsed.port,
        #     password=url_parsed.password,
        #     database=MYSQL_ROSSVYAZ_DB,
        # )

    def locate(self, phone):
        start_time = time.time()
        logging.info(f"Locating region for {phone}")

        response = self._locate(phone)
        end_time = time.time()
        logging.info(
            f"Region found: {response}. Elapsed time: {round(end_time - start_time, 2)} sec"
        )
        return response

    def _locate(self, phone):
        if phone.startswith("+"):
            phone = phone[1:]

        abcdef = phone[1:4]
        pool = phone[4:11]

        if not abcdef or not pool:
            return None

        SQL_QUERY = """SELECT region1 FROM rossvyaz WHERE abcdef = '{}' AND '{}' BETWEEN phone_poolstart AND phone_poolend;""".format(
            re.escape(abcdef), re.escape(pool)
        )
        logging.info(SQL_QUERY)
        try:
            if not self.connection.is_connected():
                logging.info("Not connected to database. Trying to reconnect")
                self.connection.reconnect(attempts=3, delay=3)
            cursor = self.connection.cursor()
            cursor.execute(SQL_QUERY)
            output = cursor.fetchall()
            return self._adapter(get(output, "0.0"))
        except Exception as e:
            logging.error(e)

        return None

    def _adapter(self, region):
        if not region:
            return None

        if region in self.global_regions:
            return None
        return region
