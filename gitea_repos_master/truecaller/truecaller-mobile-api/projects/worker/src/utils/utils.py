from datetime import datetime, timedelta


def get_closest_3_am():
    now = datetime.now()
    next_day = now.replace(hour=3, minute=0, second=1, microsecond=1)
    if now >= next_day:
        next_day += timedelta(days=1)
    return next_day, (next_day - now).total_seconds()
