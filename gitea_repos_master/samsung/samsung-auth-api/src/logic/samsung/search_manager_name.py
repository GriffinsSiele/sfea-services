from src.logic.samsung.samsung_name import SamsungName
from src.logic.samsung.search_manager_person import SamsungSearchManagerPerson


class SamsungSearchManagerName(SamsungSearchManagerPerson):
    """
    Осуществляет поиск учетной записи пользователя по телефону или e-mail, имени, фамилии и дате рождения.
    """

    samsung = SamsungName
