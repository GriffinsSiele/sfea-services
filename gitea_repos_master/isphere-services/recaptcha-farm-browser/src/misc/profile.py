import os
import shutil

from request_logic.path_utils import PUtils


class ProfilePicker:
    @staticmethod
    def get(name):
        profiles_dir = PUtils.bp(os.path.dirname(os.path.abspath(__file__)), '..', '..', 'profiles')
        zip_file = PUtils.bp(profiles_dir, 'zip', f'{name}.zip')
        output = PUtils.bp(profiles_dir, 'raw')

        PUtils.delete_dir(output)

        if PUtils.is_file_exists(zip_file):
            shutil.unpack_archive(zip_file, output)

        return output
