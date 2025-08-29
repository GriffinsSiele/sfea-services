def logger_level_by_exception(e, logger):
    exception_class_name = type(e).__name__

    if exception_class_name == "NoneType" or exception_class_name == "NoDataError":
        return logger.info

    return logger.error
