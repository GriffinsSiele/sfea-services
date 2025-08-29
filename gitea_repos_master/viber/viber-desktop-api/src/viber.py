import pyautogui
import Xlib.display
import time
import sqlite3
from pathlib import Path
import os
from pydash import find
from pydash import get
import base64
from easyprocess import EasyProcess
from pyzbar import pyzbar
from pyvirtualdisplay.smartdisplay import SmartDisplay
import logging
import pyqrcode
from .exceptions import ViberError
from .mongo import mongo
from .Profile import viber_profile
from putils_logic.putis import PUtils


class Viber:
    def __init__(self, display: SmartDisplay) -> None:
        self.display = display
        self.tel = None
        self.uses = 0
        pyautogui._pyautogui_x11._display = Xlib.display.Display(
            f":{self.display.display}"
        )
        pass

    def start_viber(self):
        self.viber = EasyProcess(["/opt/viber/Viber"])
        self.viber.start()
        self.lastqr = None
        logging.info("Viber is started")
        time.sleep(5)

    def register(self):
        mongo.session_unactive()
        if self.viber.is_alive():
            self.stop_viber()
        try:
            viber_profile.delete_profile()
        finally:
            self.start_viber()
            tries = 0
            registered = False
            while tries < 4:
                chkimg = self.check_image()
                if chkimg["qr_exists"] and chkimg["qr"]:
                    tries += 1
                    qr = pyqrcode.create(self.lastqr)
                    if tries < 4:
                        logging.info("qr changed")
                        logging.critical(qr.terminal(quiet_zone=1))
                elif not chkimg["qr_exists"]:
                    registered = True
                    time.sleep(10)
                    break
                time.sleep(10)
            return registered

    def start(self):
        logging.info("Viber is starting")
        self.get_phone()
        self.start_viber()
        pyautogui.PAUSE = 0.05
        status = {}
        if not self.check_image()["qr_exists"]:
            pyautogui.click(x=100, y=100)
            status = {"status": "started", "code": 200}
        else:
            if self.register():
                status = {
                    "status": "registered",
                    "code": 201,
                    "phone": self.get_phone(),
                }
            else:
                status = {"status": "unregistered", "code": 500}
        return status

    def check_image(self):
        image = self.display.waitgrab()
        output = get(pyzbar.decode(image), "0")
        if not output:
            return {"qr_exists": False, "qr": False}
        if output.data == self.lastqr:
            return {"qr_exists": True, "qr": False}
        self.lastqr = output.data
        return {
            "qr_exists": True,
            "qr": image.crop(
                (
                    output.rect.left - 10,
                    output.rect.top - 10,
                    output.rect.left + output.rect.width + 10,
                    output.rect.top + output.rect.height + 10,
                )
            ),
        }

    def get_phone(self):
        try:
            self.tel = find(
                os.listdir(viber_profile.profile_path), lambda x: x.isdigit()
            )
            logging.info(f"found profile {self.tel}")
        finally:
            return self.tel

    def stop_viber(self):
        self.viber.stop()
        logging.info("viber is stopped")

    def readphoto(self, DownloadID):
        photo = None
        if DownloadID:
            file = PUtils.bp(
                viber_profile.profile_path, self.tel, "Avatars", DownloadID
            )
            while not PUtils.is_file_exists(file):
                time.sleep(0.001)
            with open(file, "rb") as f:
                photo = base64.b64encode(f.read()).decode()
            return photo

    def search(self, phone):
        try:
            pyautogui.press("esc")
            time.sleep(0.1)
            pyautogui.press("enter")
            pyautogui.press("esc")
            pyautogui.hotkey("ctrl", "f")
            pyautogui.hotkey("ctrl", "a")
            pyautogui.press("backspace")
            pyautogui.write(f"+{phone}")
            time.sleep(0.5)
            pyautogui.click(x=60, y=190)
            time.sleep(1)
            with sqlite3.connect(
                os.path.join(Path.home(), ".ViberPC", self.tel, "viber.db")
            ) as conn:
                conn.row_factory = sqlite3.Row
                cursor = conn.cursor()
                contact = dict(
                    cursor.execute(
                        f'select * from Contact where Number="+{phone}"'
                    ).fetchone()
                )
            self.uses += 1
            return {
                "phone": phone,
                "nic": contact["ClientName"],
                "img": self.readphoto(contact["DownloadID"]),
                "in_viber": bool(contact["ViberContact"]),
            }
        except:
            raise ViberError()
