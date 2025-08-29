from pathlib import Path

import setuptools

install_requires = (
    open("requirements.txt", "r").read().splitlines()
    if Path("requirements.txt").exists()
    else []
)

setuptools.setup(
    name="mongo-client",
    version="10.2.0",
    author="Kovynev M.",
    author_email="mk@i-sphere.ru",
    description="Класс клиент подключения к базе данных mongo с "
    "основными используемыми методами получения сессионных данных",
    packages=setuptools.find_packages(exclude=["tests"]),
    include_package_data=True,
    install_requires=install_requires,
    python_requires=">=3.10",
)
