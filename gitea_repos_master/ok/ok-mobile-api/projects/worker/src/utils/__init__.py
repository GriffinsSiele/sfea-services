from datetime import datetime, timedelta


def current_date():
    return datetime.now().strftime("%Y-%m-%d")


def next_date(hours=6):
    return datetime.now() + timedelta(hours=hours)
