from pathlib import Path

import setuptools

install_requires = (
    open("requirements.txt", "r").read().splitlines()
    if Path("requirements.txt").exists()
    else []
)

setuptools.setup(
    name="browsers-repository",
    version="1.0.0",
    author="Matveev S.",
    author_email="sm@i-sphere.ru",
    description="Репозиторий браузеров",
    packages=setuptools.find_packages(exclude=["tests", "driver"]),
    include_package_data=True,
    install_requires=install_requires,
    python_requires=">=3.11",
)
