class UtilsTest:
    @staticmethod
    def is_ignore_field(field):
        return field in [
            "abilities",
            "aspects",
            "load",
            "rating_general",
            "rating_count",
            "review_count",
            "list__images",
            "count_images",
            "abilities",
        ]
