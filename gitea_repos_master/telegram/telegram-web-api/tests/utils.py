class UtilsTest:
    @staticmethod
    def is_ignore_field(field):
        return field in [
            "status",
            "description",
            "__photos",
            "list__image",
            "__image_url",
        ]
