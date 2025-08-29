from pathlib import Path
import os
import tarfile
from io import BytesIO
from putils_logic.putis import PUtils


class ViberProfile:
    home = Path.home()
    profile_path = PUtils.bp(Path.home(), ".ViberPC")

    def pack(self, phone):
        archive_name = PUtils.bp(self.home, f"{phone}.tar.gz")
        os.system(f"cd {self.home} && tar -cpzf {archive_name} .ViberPC")
        if PUtils.is_file_exists(archive_name):
            with open(archive_name, "rb") as f:
                tar = f.read()
            PUtils.delete_file(archive_name)
            return tar

    def unpack(self, fileobj):
        with tarfile.open(fileobj=BytesIO(fileobj)) as f:
            f.extractall(self.home)

    def delete_profile(self):
        if PUtils.is_dir_exists(self.profile_path):
            PUtils.delete_dir(self.profile_path)


viber_profile = ViberProfile()
