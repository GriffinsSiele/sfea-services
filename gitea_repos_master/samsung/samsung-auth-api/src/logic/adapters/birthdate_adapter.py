from datetime import datetime

from isphere_exceptions.source import SourceIncorrectDataDetected


class BirthdateAdapter:
    """Преобразует дату рождения в требуемый для использования в приложении формат"""

    @staticmethod
    def to_international_format(birthdate: str) -> str:
        """Преобразует день рождения формата 20.01.1990 в 19900120.

        :param birthdate: День рождения в формате 20.01.1990.
        :return: День рождения в формате 19900120 или исключение SourceIncorrectDataDetected.
        """
        try:
            date_obj = datetime.strptime(birthdate, "%d.%m.%Y")
            return date_obj.strftime("%Y%m%d")
        except ValueError:
            raise SourceIncorrectDataDetected()
