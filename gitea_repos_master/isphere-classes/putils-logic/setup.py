from pathlib import Path

import setuptools

install_requires = (
    open("requirements.txt", "r").read().splitlines()
    if Path("requirements.txt").exists()
    else []
)

setuptools.setup(
    name="putils-logic",
    version="10.2.0",
    author="Kovynev M.V.",
    author_email="mk@i-sphere.ru",
    description="Общие классы для работы с файловой системой",
    packages=setuptools.find_packages(exclude=["tests"]),
    include_package_data=True,
    install_requires=install_requires,
    python_requires=">=3.8",
)
