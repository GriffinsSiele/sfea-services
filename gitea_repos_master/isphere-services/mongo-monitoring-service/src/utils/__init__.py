def comparator_with_emoji(x, y):
    def compare_str(a, b):
        if a > b:
            return 1
        if a < b:
            return -1
        return 0

    em_x, em_y = x[0], y[0]
    if em_x == em_y:
        return compare_str(x[1:], y[1:])

    return compare_str(em_y, em_x)
