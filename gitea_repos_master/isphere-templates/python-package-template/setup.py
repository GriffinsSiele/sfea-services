from pathlib import Path

import setuptools

install_requires = (
    open("requirements.txt", "r").read().splitlines()
    if Path("requirements.txt").exists()
    else []
)

setuptools.setup(
    name="python-package-template",
    version="1.0.0",
    author="Kovynev M.",
    author_email="mk@i-sphere.ru",
    description="Описание пакета",
    packages=setuptools.find_packages(exclude=["tests"]),
    include_package_data=True,
    install_requires=install_requires,
    python_requires=">=3.8",
)
