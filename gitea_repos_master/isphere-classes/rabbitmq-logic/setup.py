import setuptools

setuptools.setup(
    name="rabbitmq_logic",
    version="5.0.0",
    author="Kovynev M.",
    author_email="mk@i-sphere.ru",
    description="Общие классы для работы с запросами очередями RabbitMQ на Python",
    packages=setuptools.find_packages(exclude=["tests"]),
    include_package_data=True,
    install_requires=["aio-pika"],
    python_requires=">=3.10",
)
