import os
import pathlib


def bp(*args):
    return os.path.normpath(os.path.join(*args))


def is_file_exists(path):
    return pathlib.Path(path).exists() and pathlib.Path(path).is_file()