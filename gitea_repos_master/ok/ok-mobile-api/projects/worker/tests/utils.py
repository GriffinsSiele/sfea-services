class UtilsTest:
    @staticmethod
    def is_ignore_field(field):
        return field in [
            "age",
            "avatar",
            "list__photos",
            "last_online",
            "groups_members",
            "groups_avatar",
            "counter_subscribers",
        ]
