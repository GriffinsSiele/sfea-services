# docs-ci-supplementary

Репозиторий используется как хранилище вспомогательных файлов для сборки sphinx документации Python проектов по коду в CI/CD Drone на шаге `auto-docs`. 


Примеры указания комментариев к коду для sphinx:

```python
class PUtils:
    """Вспомогательный класс для работы с путями и папками"""

@staticmethod
def bp(*args) -> str:
    """Построение пути к файлу/директории на основе переданных частей пути

    :param args: список частей пути
    :return: относительный путь к файлу/директории
    :rtype: str

    Example:
    -------
    ``bp('media', 'home', 'user') -> './media/home/user'``
    """
    return os.path.normpath(os.path.join(*args))
```

```python
class MyOtherClass:
    """
    This class does that.
    """
    pass

class MyClass:
    """
    Description for class.
    """

    #: Syntax also works for class variables.
    class_var: int = 1

    def __init__(self, par1: int, par2: MyOtherClass):
        #: Doc for var1
        self.var1: int = par1

        #: Doc for var2.
        #: Another line!
        self.var2: MyOtherClass = par2

    def method(self):
        """
        My favorite method.
        """
        pass

    @classmethod
    def cmethod():
        """
        My favorite class method.
        """
        pass

 ```