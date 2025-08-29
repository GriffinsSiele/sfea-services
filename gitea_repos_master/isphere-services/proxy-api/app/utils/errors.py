class NotFoundError(Exception):
    pass


class EnvironmentVariableNotDefined(Exception):
    def __init__(self, variable_name: str):
        super().__init__(f"Environment variable {variable_name} is undefined or empty!")


class EnvironmentVariableWrong(Exception):
    def __init__(self, variable: str):
        super().__init__(f"Environment variable {variable} is wrong!")
