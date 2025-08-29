import setuptools

setuptools.setup(
    name="worker-classes",
    version="16.0.0",
    author="Kovynev M.",
    author_email="mk@i-sphere.ru",
    description="Основные классы для обработчиков на Python",
    packages=setuptools.find_packages(exclude=["tests"]),
    include_package_data=True,
    install_requires=["pydash", "typing-extensions", "livenessprobe-logic"],
    python_requires=">=3.10",
)
