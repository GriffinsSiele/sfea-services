import os
import shutil
from pathlib import Path


def join_path(*args) -> str:
    return os.path.normpath(os.path.join(*args))


def is_dir_exists(path: str) -> bool:
    return Path(path).exists() and Path(path).is_dir()


def mk_dir(directory: str) -> None:
    return Path(directory).mkdir(parents=True, exist_ok=True)


def delete_dir(path: str) -> None:
    try:
        shutil.rmtree(path)
    except Exception:
        pass


def copy_file(from_path: str, to_path: str) -> str:
    return shutil.copy2(from_path, to_path)
