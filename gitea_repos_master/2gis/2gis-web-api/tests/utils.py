class UtilsTest:
    @staticmethod
    def is_ignore_field(field):
        return field in [
            "rating_count",
            "rating_general",
            "updated_at",
            "_id",
            "abilities",
        ]
