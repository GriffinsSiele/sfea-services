from pydash import sort, sort_by

from src.config.app import ConfigApp
from src.config.settings import MODE, MONGO_URL_CLEAN
from src.logic.utils.decorators import with_prefix
from src.utils import comparator_with_emoji


class TelegramMessages:
    @staticmethod
    def _prefix():
        prefix_dev = "Игнорируйте данное сообщение.\n" if MODE == "dev" else ""
        mode = MODE.upper()
        message = f"[RSM][{mode}][{MONGO_URL_CLEAN}] "
        return f"{prefix_dev}{message}"

    def percent_to_emoji(self, percent):
        if percent >= 90:
            w = "✅"
        elif percent == 0:
            w = "💀"
        elif percent < 50:
            w = "❗"
        else:
            w = "❓"
        return w

    def __collection_state_to_str(
        self, count_active=0, count_total=1, count_blocked=0, count_locked=0
    ):
        count_total = count_total if count_total else 1
        percent = int(count_active / count_total * 100)

        w = self.percent_to_emoji(percent)

        return w, f"{percent}% - {count_active}💪, {count_locked}🔒, {count_blocked}💀"

    @with_prefix
    def locked(self, type="active", *args, **kwargs):
        lock_text_n = "временно " if type == "locked" else ""
        lock_text_p = "временная " if type == "locked" else ""
        field_count = "count_locked" if type == "locked" else "count_blocked"

        prod = kwargs.pop("prod")
        collection = "-".join(prod.split("-")[0:-1])

        title = f'Обнаружена {lock_text_p}блокировка сессий в коллекции "{prod}"'

        start, end = kwargs.pop("start"), kwargs.pop("end")

        before_stats, after_stats = kwargs.pop("before"), kwargs.pop("after")
        wb, before = self.__collection_state_to_str(**before_stats)
        wa, after = self.__collection_state_to_str(**after_stats)
        locked = after_stats[field_count] - before_stats[field_count]

        message = (
            f"В период с {start} по {end} {lock_text_n}заблокировано {locked} сессий.\n"
            + f"До: {wb} {before}\n"
            + f"После: {wa} {after}"
        )

        tags = ["RSM", collection]
        return title, message, tags

    @with_prefix
    def below_normal(self, *args, **kwargs):
        prod = kwargs.pop("prod")
        collection = "-".join(prod.split("-")[0:-1])
        title = f'Обнаружено снижение кол-ва сессий ниже минимума ({ConfigApp.CRITICAL_MIN_PERCENT_OF_SESSIONS_TO_TRIGGER}%) в коллекции "{prod}"'

        stats = kwargs.pop("stats")
        w, stats_message = self.__collection_state_to_str(**stats)

        message = f"{w} {prod}: {stats_message}"
        tags = ["RSM", collection, "critical_min"]
        return title, message, tags

    @with_prefix
    def migrate_success(self, *args, **kwargs):
        prod, dev = kwargs.pop("prod"), kwargs.pop("dev")
        collection = prod.replace("-" + ConfigApp.PROD, "")
        title = f'Обнаружен недостаток сессий в коллекции "{prod}"'

        count = kwargs.pop("count")

        stats_prod_before = kwargs.pop("stats_prod_before")
        wpb, stats_prod_before = self.__collection_state_to_str(**stats_prod_before)

        stats_after_prod = kwargs.pop("stats_after_prod")
        wpa, stats_after_prod = self.__collection_state_to_str(**stats_after_prod)

        stats_dev_before = kwargs.pop("stats_dev_before")
        wdb, stats_dev_before = self.__collection_state_to_str(**stats_dev_before)

        stats_dev_after = kwargs.pop("stats_dev_after")
        wda, stats_dev_after = self.__collection_state_to_str(**stats_dev_after)

        message = (
            f'Выполнена миграция сессий из "{dev}" в "{prod}" в размере {count} записей.\n'
            + f"Коллекция {ConfigApp.PROD} до миграции:\n{wpb} {prod}: {stats_prod_before}\n"
            + f"Коллекция {ConfigApp.DEV} до миграции:\n{wdb} {dev}: {stats_dev_before}\n\n"
            + f"Коллекция {ConfigApp.PROD} после миграции:\n{wpa} {prod}: {stats_after_prod}\n"
            + f"Коллекция {ConfigApp.DEV} после миграции:\n{wda} {dev}: {stats_dev_after}\n"
        )

        tags = ["RSM", collection, "migration"]

        return title, message, tags

    @with_prefix
    def migration_failure(self, *args, **kwargs):
        prod, dev = kwargs.pop("prod"), kwargs.pop("dev")
        collection = prod.replace("-" + ConfigApp.PROD, "")

        title = f'Обнаружен недостаток сессий в коллекции "{prod}"'

        stats_prod = kwargs.pop("stats_prod")
        wp, stats_prod = self.__collection_state_to_str(**stats_prod)

        stats_dev = kwargs.pop("stats_dev")
        wd, stats_dev = self.__collection_state_to_str(**stats_dev)

        message = (
            f'В таблице "{dev}" недостаточно сессий для пополнения. '
            + f'Пожалуйста, пополните "{dev}" и "{prod}" сессионными данными.\n'
            + f"Коллекция {ConfigApp.PROD}:\n{wp} {prod}: {stats_prod}\n"
            + f"Коллекция {ConfigApp.DEV}:\n{wd} {dev}: {stats_dev}\n"
        )
        tags = ["RSM", collection, "migration", "failure"]
        return title, message, tags

    @with_prefix
    def statistics(self, *args, **kwargs):
        collections = kwargs.pop("collections")

        title = "Статистика активных сессий в MongoDB"
        groups = {ConfigApp.PROD: [], ConfigApp.DEV: [], ConfigApp.UNITTEST: []}

        for collection_name, stats in collections.items():
            for group in groups.keys():
                if collection_name.endswith(group):
                    collection_name = collection_name.replace("-" + group, "")
                    w, stats_message = self.__collection_state_to_str(**stats)
                    message = f"{w} {collection_name}: {stats_message}"
                    groups[group].append(message)

        output = ""
        for group_name, messages in groups.items():
            if not messages:
                continue

            output += f"Коллекции {group_name}:\n"

            for message in sort(messages, comparator=comparator_with_emoji):
                output += message + "\n"
            output += "\n"

        tags = ["RSM", "stats"]
        return title, output, tags

    @with_prefix
    def underperforming_success(self, *args, **kwargs):
        collections = kwargs.pop("collections")

        title = "Статистика успешности использования сессий в MongoDB"

        messages = []

        def percent(s):
            return round(s * 100, 1)

        for collection, (sessions, avg_use) in collections.items():
            avg_use = percent(avg_use)

            w = self.percent_to_emoji(avg_use if not sessions else 49)
            output = f"{w} {collection}: {avg_use}% успешности\n"
            if sessions:
                output += (
                    f"Обнаружено {len(sessions)} сессия(й) {collection} ниже порога:\n"
                )
                for session in sort_by(sessions[:5], lambda x: x.get("rate")):
                    output += (
                        f"{session.get('session')}: {percent(session.get('rate'))}%\n"
                    )
                if len(sessions) > 5:
                    output += "...\n"

            messages.append(output)

        output = ""
        for message in sort(messages):
            output += message
        output += "\n"

        tags = ["RSM", "underperforming"]
        return title, output, tags
