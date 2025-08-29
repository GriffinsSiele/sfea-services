import io
import os
import zipfile

import rarfile


def zip_folder(folder_path):
    memory_file = io.BytesIO()
    with zipfile.ZipFile(memory_file, mode="w") as zipf:
        for root, dirs, files in os.walk(folder_path):
            for file in files:
                file_path = os.path.join(root, file)
                zipf.write(file_path, arcname=os.path.relpath(file_path, folder_path))
    memory_file.seek(0)
    return memory_file.read()


def unzip_bytes(zip_bytes, target_folder):
    memory_file = io.BytesIO(zip_bytes)
    with zipfile.ZipFile(memory_file, mode="r") as zipf:
        zipf.extractall(path=target_folder)


def unrar_file(path, output):
    rar = rarfile.RarFile(path)
    rar.extractall(path=output)


def unzip_file(path, output):
    with zipfile.ZipFile(path, "r") as zip_ref:
        zip_ref.extractall(output)


def decompress(path, output):
    if "zip" in str(path):
        unzip_file(path, output)
    else:
        unrar_file(path, output)
