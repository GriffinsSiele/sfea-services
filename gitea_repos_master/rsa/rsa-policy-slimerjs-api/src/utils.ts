export const randomInt = (min: number, max: number) =>
  Math.floor(Math.random() * (max - min)) + min;

export const sleep = (ms: number) => slimer.wait(ms);

export const flatObject = (input: any, output: any = null) => {
  output = output || {};
  for (const key in input) {
    const value = input[key];
    if (typeof value === 'object' && value !== null) {
      flatObject(value, output);
    } else {
      output[key] = value;
    }
  }
  return output;
};
