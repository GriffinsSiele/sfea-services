import logging
import pathlib
import re
import sqlite3
import time

from putils_logic.putils import PUtils
from pydash import get

from src.logic.adapters.region.phone_to_region_db import MetaSingleton


class RegionLocateSQLite(metaclass=MetaSingleton):
    global_regions = ["Российская Федерация"]

    def __init__(self):
        path = PUtils.bp(
            pathlib.Path(__file__).absolute(),
            "..",
            "..",
            "..",
            "..",
            "..",
            "db",
            "rossvyaz.sqlt",
        )
        logging.info(f"Connection to database: {path}")
        self.sqlite_connection = sqlite3.connect(path, timeout=5)

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
            cursor = self.sqlite_connection.cursor()
            cursor.execute(SQL_QUERY)
            output = cursor.fetchone()
            return self._adapter(get(output, "0"))
        except Exception as e:
            logging.error(e)

        return None

    def _adapter(self, region):
        if not region:
            return None

        if region in self.global_regions:
            return None
        return region
