class UtilsTest:
    @staticmethod
    def is_ignore_field(field):
        return field in ["score", "comments_count", "search_warnings", "spam_score"]
