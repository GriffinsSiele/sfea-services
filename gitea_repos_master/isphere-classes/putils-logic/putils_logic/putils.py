import os
import shutil
from pathlib import Path
from typing import List


class PUtils:
    """Вспомогательный класс для работы с путями и папками"""

    @staticmethod
    def bp(*args) -> str:
        """Построение пути к файлу/директории на основе переданных частей пути

        :param args: список частей пути
        :return: относительный путь к файлу/директории
        :rtype: str

        Example:
        -------
        ``bp('media', 'home', 'user') -> './media/home/user'``
        """
        return os.path.normpath(os.path.join(*args))

    @staticmethod
    def bp_abs(*args) -> str:
        """Построение пути к файлу/директории на основе переданных частей пути

        :param args: список частей пути
        :return: абсолютный путь к файлу/директории
        :rtype: str

        Example:
        -------
        ``bp_abs('media', 'project', '1.png') -> '/home/alien/git/media/project/1.png'``
        """
        return os.path.abspath(PUtils.bp(*args))

    @staticmethod
    def mkdir(directory: str, parents=True, exists_ok=True) -> None:
        """Создание директории в файловой системе

        :param directory: путь директории, которую нужно создать
        :param parents: создавать промежуточные подпапки, если не были созданы
        :param exists_ok: не вызывать exception в случае если папка уже создана
        :return: None
        """
        return Path(directory).mkdir(parents=parents, exist_ok=exists_ok)

    @staticmethod
    def delete_file(path: str, logger=None) -> None:
        """Удаление файла по переданному пути

        :param path: путь файла для удаления
        :param logger: опциональная функция вывода ошибки
        :return: None
        """
        try:
            if path and os.path.exists(path):
                os.remove(path)
        except Exception as e:
            if logger:
                logger(e)

    @staticmethod
    def delete_dir(path: str, logger=None) -> None:
        """Удаление директории

        :param path: путь директории для удаления
        :param logger: опциональная функция вывода ошибки
        :return: None
        """
        try:
            shutil.rmtree(path)
        except Exception as e:
            if logger:
                logger(e)

    @staticmethod
    def touch_file(path: str) -> None:
        """Создание пустого файла

        :param path: путь к файлу
        :return: None
        """
        return Path(path).touch()

    @staticmethod
    def copy_dir(from_path: str, to_path: str) -> str:
        """Рекурсивное копирование файлов директории copytree

        :param from_path: исходная папка
        :param to_path: папка назначения
        :return: Папка назначения
        """
        return shutil.copytree(from_path, to_path)

    @staticmethod
    def move_dir(from_path: str, to_path: str) -> str:
        """Рекурсивное перемещение файлов директории mv

        :param from_path: исходная папка
        :param to_path: папка назначения
        :return: Папка назначения
        """
        return shutil.move(from_path, to_path)

    @staticmethod
    def copy_file(from_path: str, to_path: str) -> str:
        """Копирование файла с метаданными

        :param from_path: исходный файл
        :param to_path: папка или файл назначения
        :return: Путь до копии файла
        """
        return shutil.copy2(from_path, to_path)

    @staticmethod
    def is_empty_file(filename: str) -> bool:
        """Проверка на пустоту файла

        Reference https://stackoverflow.com/a/2507871

        :param filename: путь к файлу
        :return: bool размер файла равен 0
        """
        return PUtils.size_of_file(filename) == 0

    @staticmethod
    def is_empty_dir(path: str) -> bool:
        """Проверка на пустоту директории. Директория не содержит файлов

        Reference https://stackoverflow.com/a/59050548

        :param path: путь к директории
        :return: bool кол-во файлов внутри директории равен 0
        """
        return len(os.listdir(path)) == 0

    @staticmethod
    def is_dir_exists(path: str) -> bool:
        """Проверка на существование директории

        Reference https://stackoverflow.com/a/44228213

        :param path: путь к директории
        :return: bool существует ли директория
        """
        return Path(path).exists() and Path(path).is_dir()

    @staticmethod
    def is_file_exists(path: str) -> bool:
        """Проверка на существование файла

        Reference https://stackoverflow.com/a/44228213

        :param path: путь к директории
        :return: bool существует ли файл
        """
        return Path(path).exists() and Path(path).is_file()

    @staticmethod
    def zip_path(path: str, zip_name: str, *args, **kwargs) -> str:
        """Запаковка в архив файлов директории

        :param path: путь к директории
        :param zip_name: имя архива (без .zip)
        :param args: параметры shutil.make_archive
        :param kwargs: параметры shutil.make_archive
        :return: имя архива
        """
        return shutil.make_archive(zip_name, "zip", path, *args, **kwargs)

    @staticmethod
    def unzip(filename: str, extract_path: str, *args, **kwargs) -> None:
        """Извлечение файлов из архива

        :param filename: имя архива
        :param extract_path: путь к папке назначения распаковки
        :param args: параметры shutil.make_archive
        :param kwargs: параметры shutil.make_archive
        :return: None
        """
        return shutil.unpack_archive(filename, extract_path, *args, **kwargs)

    @staticmethod
    def size_of_file(file: str) -> int:
        """Размер файла в байтах

        :param file: путь к файлу
        :return: размер файла в байтах
        """
        return os.stat(file).st_size

    @staticmethod
    def get_filename_from_path(path: str) -> str:
        """Извлечение файла из пути

        :param path: путь к файлу
        :return: имя файла

        Example:
        -------
        `get_filename_from_path('/hello/world/file.txt') -> 'file.txt'`
        """
        return os.path.basename(path)

    @staticmethod
    def get_files(path: str) -> List[str]:
        """Получение списка файлов и папок директории. Не рекурсивное

        :param path: путь к директории
        :return: список файлов/папок с относительными путями

        Example:
        -------
        ``get_files('.') -> ['./.idea', './.git', './setup.py', './.gitignore', ...]``
        ``get_files('/home/Desktop') -> ['/home/p/file.drawio', '/home/Desktop/folder', ...]``
        """
        return [f.path for f in os.scandir(path)]

    # Reference https://thispointer.com/python-how-to-get-list-of-files-in-directory-and-sub-directories/
    @staticmethod
    def get_files_recursive(path: str) -> List[str]:
        """Получение списка файлов и папок директории. Рекурсивное

        :param path: путь к директории
        :return: список файлов/папок с относительными путями

        Example:
        -------
        ``get_files('.') -> ['./.idea/.gitignore', './.idea/vcs.xml', './.idea/sonarlint/securityhotspotstore/index.pb', ...]``
        ``get_files('/home') -> ['/home/readable_serial.js', '/home/node_modules/asynckit/lib/state.js', ...]``
        """
        all_files: List[str] = []
        for entry in os.listdir(path):
            full_path = os.path.join(path, entry)
            if os.path.isdir(full_path):
                all_files = all_files + PUtils.get_files_recursive(full_path)
            else:
                all_files.append(full_path)

        return all_files
