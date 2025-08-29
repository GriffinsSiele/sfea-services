import setuptools

setuptools.setup(
    name="requests-logic",
    version="23.0.0",
    author="Kovynev M.",
    author_email="mk@i-sphere.ru",
    description="Общие классы для работы с запросами REST API requests на Python",
    packages=setuptools.find_packages(exclude=["tests"]),
    include_package_data=True,
    install_requires=["requests", "urllib3", "pydash"],
    python_requires=">=3.10",
)
