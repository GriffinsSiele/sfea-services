import os.path
from time import sleep

# from mega import Mega
from putils_logic.putils import PUtils
from selenium import webdriver
from selenium.webdriver.common.by import By

from src.utils.utils import decompress


class MegaDownloader:
    @staticmethod
    def download(link):
        return MegaDownloader._download(link)

    @staticmethod
    def _download(link):
        mega = Mega()
        m = mega.login()

        if "!" not in link:
            archive = m.download_url(link)
            return MegaDownloader._unzip(archive)
        else:
            archive = MegaDownloader.download_folder(link, "tdata")
            content = MegaDownloader._unzip(archive)
            PUtils.delete_dir("tdata")
            return content[6:]

    @staticmethod
    def _unzip(archive):
        decompress(archive, ".")
        PUtils.delete_dir(archive)
        return PUtils.bp(
            ".",
            str(archive).replace(".zip", "").replace(".rar", "").split("_")[0],
            "tdata",
        )

    @staticmethod
    def download_folder(link, destination):
        PUtils.mkdir(destination)
        profile = webdriver.FirefoxProfile()
        profile.set_preference("browser.download.folderList", 2)
        profile.set_preference("browser.download.manager.showWhenStarting", False)
        profile.set_preference("browser.download.dir", os.path.abspath(destination))
        profile.set_preference(
            "browser.helperApps.neverAsk.saveToDisk", "application/octet-stream"
        )
        driver = webdriver.Firefox(firefox_profile=profile)

        driver.get(link)

        sleep(5)
        e = driver.find_element(By.CLASS_NAME, "fm-download-as-zip")
        e.click()

        sleep(13)
        driver.close()

        files = PUtils.get_files(destination)
        zip = files[0]
        return zip
