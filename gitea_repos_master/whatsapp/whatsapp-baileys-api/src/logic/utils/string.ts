export function randomString(len = 7) {
  return (Math.random() + 1).toString(36).substring(len);
}
export function toShortString(obj: Object) {
  const output: string[] = [];
  if (!obj) {
    return obj;
  }

  for (const [key, value] of Object.entries(obj)) {
    const v = value instanceof Function ? 'function' : value;
    const vLong = v && v.length > 40 ? v.slice(0, 40) + '...' : v;
    output.push(`"${key}": ${vLong}`);
  }
  return `{${output.join(', ')}}`;
}
export function toShortStringRec(obj: Object) {
  const output: string[] = [];

  for (const [key, value] of Object.entries(obj)) {
    let v = value;
    if (value instanceof Function) {
      v = 'function';
    } else if (typeof value === 'object') {
      v = toShortStringRec(value);
    } else {
      v = v && v.length > 40 ? v.slice(0, 40) + '...' : v;
    }
    output.push(`"${key}": ${v}`);
  }
  return `{${output.join(', ')}}`;
}
