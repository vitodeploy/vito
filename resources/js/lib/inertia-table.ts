import type { Row } from 'inertia-table-react';

/**
 * Cast an InertiaTable row payload to the typed model shape, asserting that
 * every required key is present. Throws if the backend `Table` omits or
 * renames a field — surfaces shape drift at the page boundary rather than
 * silently producing `undefined` route params downstream.
 */
export function asRow<T extends Record<string, unknown>>(row: Row, requiredKeys: ReadonlyArray<keyof T & string>): T {
  for (const key of requiredKeys) {
    if (row[key] === undefined) {
      throw new Error(`InertiaTable row missing required field: ${key}`);
    }
  }
  return row as unknown as T;
}
