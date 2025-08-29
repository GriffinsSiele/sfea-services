def transpose(a):
    return [list(x) for x in zip(*a)]


def parse_table(table, is_transpose=False):
    rows = table.find_all(recursive=False)
    data = []
    for row in rows:
        row_data = []
        for cell in row.find_all('td', recursive=False):
            inner_table = cell.find('table')
            row_data.append(parse_table(inner_table, True) if inner_table else cell.text)
        data.append(row_data)

    if is_transpose:
        data = transpose(data)

    if len(data) < 1:
        return data

    headers = data[0]
    output = []

    for r in data[1:]:
        row_data = {}
        for i, e in enumerate(r):
            row_data[headers[i]] = e
        output.append(row_data)
    return output
