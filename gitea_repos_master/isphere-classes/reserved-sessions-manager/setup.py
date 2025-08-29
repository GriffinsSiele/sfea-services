import setuptools

setuptools.setup(
    name="reserved-sessions-manager",
    version="4.0.0",
    author="Kovynev M.",
    author_email="mk@i-sphere.ru",
    description="Класс автоматического дополнения сессий из резерва",
    packages=setuptools.find_packages(exclude=["tests"]),
    include_package_data=True,
    install_requires=["requests"],
    python_requires=">=3.10",
)
