import logging


def _logging(log, func, *args, **kwargs):
    try:
        log(f"Call '{func.__name__}', parameters : {args}, {kwargs}")
        return func(*args, **kwargs)
    except Exception as e:
        logging.exception(e)


def logger_debug(func):
    def wrapper(*args, **kwargs):
        return _logging(logging.debug, func, *args, **kwargs)

    return wrapper


def logger_info(func):
    def wrapper(*args, **kwargs):
        return _logging(logging.info, func, *args, **kwargs)

    return wrapper
