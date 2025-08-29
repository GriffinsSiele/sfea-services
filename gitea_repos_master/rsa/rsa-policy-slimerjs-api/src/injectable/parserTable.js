export function parseTable(table, is_transpose = false) {
  const transpose = (matrix) => {
    return matrix[0].map((col, i) => matrix.map((row) => row[i]));
  };

  let data = [];
  const rows = table.children[0].children;

  for (let i = 0; i < rows.length; i++) {
    const row = rows[i];
    const rowData = [];
    for (let j = 0; j < row.children.length; j++) {
      const cell = row.children[j];
      rowData.push(
        cell.childNodes.length > 1
          ? parseTable(cell.children[0].children[0], true)
          : cell.textContent
      );
    }
    data.push(rowData);
  }

  if (is_transpose) {
    data = transpose(data);
  }

  if (data.length < 1) {
    return data;
  }

  const headers = data[0];
  const output = [];

  for (let i = 1; i < data.length; i++) {
    const row = data[i];
    const rowData = {};
    for (let j = 0; j < row.length; j++) {
      rowData[headers[j]] = row[j];
    }
    output.push(rowData);
  }

  return output;
}
