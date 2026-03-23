import React from 'react';

export type DynamicCellComponentProps<TData = Record<string, unknown>> = {
  row: TData;
};

const componentRegistry: Record<string, React.ComponentType<DynamicCellComponentProps>> = {};

export function registerCellComponent(name: string, component: React.ComponentType<DynamicCellComponentProps>): void {
  componentRegistry[name] = component;
}

export function getCellComponent(name: string): React.ComponentType<DynamicCellComponentProps> | null {
  return componentRegistry[name] ?? null;
}
