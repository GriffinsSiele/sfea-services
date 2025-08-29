import setuptools

setuptools.setup(
    name="isphere-exceptions",
    version="5.5.0",
    author="Kovynev M.",
    author_email="mk@i-sphere.ru",
    description="Список классов-ошибок для внутреннего отслеживания событий и выдачи ответа для клиентов",
    packages=setuptools.find_packages(exclude=["tests"]),
    include_package_data=True,
    install_requires=[],
    python_requires=">=3.8",
)
