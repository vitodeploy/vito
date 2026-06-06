export function generateUniqueKey(existingKeys: string[]): string {
  let counter = 1;
  let newKey = 'NEW_VARIABLE';

  while (existingKeys.includes(newKey)) {
    newKey = `NEW_VARIABLE_${counter}`;
    counter++;
  }

  return newKey;
}
