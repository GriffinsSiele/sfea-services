import setuptools

setuptools.setup(
    name="queue_logic",
    version="10.0.0",
    author="Rudakov A., Kovynev M.",
    author_email="ar@i-sphere.ru",
    description="Логика работы с очередями keydb",
    packages=setuptools.find_packages(exclude=["tests"]),
    include_package_data=True,
    install_requires=["redis"],
    python_requires=">=3.10",
)
