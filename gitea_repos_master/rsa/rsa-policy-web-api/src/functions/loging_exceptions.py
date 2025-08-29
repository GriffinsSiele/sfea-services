import traceback


def logging_exceptions():
    exception_string = traceback.format_exc().strip()
    exception_list = exception_string.split("\n")
    exception_log_text = (
        "->".join([*exception_list[-4:-2], exception_list[-1]]).strip().replace("^", "")
    )

    return exception_log_text
