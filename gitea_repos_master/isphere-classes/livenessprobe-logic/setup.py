import setuptools

setuptools.setup(
    name="livenessprobe-logic",
    version="10.0.0",
    author="Rudakov. A",
    author_email="ran@ats-co.ru",
    description="Проверка времени последнего ответа обработчика",
    packages=setuptools.find_packages(exclude=["tests"]),
    include_package_data=True,
    install_requires=["putils-logic"],
    python_requires=">=3.10",
    scripts=["bin/healthtest"],
)
