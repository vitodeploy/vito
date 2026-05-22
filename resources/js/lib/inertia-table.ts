import type { Row } from 'inertia-table-react';

export function asRow<T>(row: Row, requiredKeys: ReadonlyArray<keyof T & string>): T {
  for (const key of requiredKeys) {
    if (row[key] === undefined || row[key] === null) {
      throw new Error(`InertiaTable row missing required field: ${key}`);
    }
  }
  return row as unknown as T;
}
