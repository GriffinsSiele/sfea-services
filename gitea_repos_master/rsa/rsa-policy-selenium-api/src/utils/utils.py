def flatten_json(y):
    out = {}

    def flatten(x, name=''):
        if type(x) is dict:
            for a in x:
                flatten(x[a], a)
        else:
            out[name] = x

    flatten(y)
    return out