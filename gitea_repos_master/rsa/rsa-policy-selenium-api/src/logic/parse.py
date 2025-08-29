def transpose(a):
    return [list(x) for x in zip(*a)]


def parseTable(table, is_transpose=False):
    rows = table.findChild('tbody').find_all(recursive=False)
    data = []
    for row in rows:
        rowData = []
        for cell in row.find_all('td', recursive=False):
            inner_table = cell.find('table')
            rowData.append(parseTable(inner_table, True) if inner_table else cell.text)
        data.append(rowData)

    if is_transpose:
        data = transpose(data)

    if len(data) < 1:
        return data

    headers = data[0]
    output = []

    for r in data[1:]:
        rowData = {}
        for i, e in enumerate(r):
            rowData[headers[i]] = e
        output.append(rowData)
    return output
