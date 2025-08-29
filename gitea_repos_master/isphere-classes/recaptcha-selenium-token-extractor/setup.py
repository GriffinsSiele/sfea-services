import setuptools

setuptools.setup(
    name="recaptcha-selenium-token-extractor",
    version="3.0.0",
    author="Kovynev M.",
    author_email="mk@i-sphere.ru",
    description="Пакет для проектов с Selenium/Google Recaptcha по извлечению токенов со страниц",
    packages=setuptools.find_packages(),
    install_requires=['requests', 'urllib3'],
    python_requires='>=3.7',
)
