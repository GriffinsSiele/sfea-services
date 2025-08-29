class ListUtils:
    @staticmethod
    def has_intersection(lst1: list, lst2: list) -> bool:
        return bool([value for value in lst1 if value in lst2])

    @staticmethod
    def difference(lst1: list | set, lst2: list | set) -> list:
        if not isinstance(lst1, set):
            lst1 = set(lst1)
        if not isinstance(lst2, set):
            lst2 = set(lst2)
        return list(lst1.difference(lst2))
