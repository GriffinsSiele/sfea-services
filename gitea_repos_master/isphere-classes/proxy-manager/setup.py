from pathlib import Path

import setuptools

install_requires = (
    open("requirements.txt", "r").read().splitlines()
    if Path("requirements.txt").exists()
    else []
)

setuptools.setup(
    name="proxy-manager",
    version="1.1.0",
    author="Kovynev M.",
    author_email="mk@i-sphere.ru",
    description="Работа с прокси для обработчиков",
    packages=setuptools.find_packages(exclude=["tests"]),
    include_package_data=True,
    install_requires=install_requires,
    python_requires=">=3.10",
)
