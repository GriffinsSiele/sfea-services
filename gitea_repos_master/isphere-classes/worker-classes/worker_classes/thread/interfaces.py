from typing import Callable

from typing_extensions import OrderedDict

ExceptionHandlerDescription = OrderedDict[Exception, Callable]
