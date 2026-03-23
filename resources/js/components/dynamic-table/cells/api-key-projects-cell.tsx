import React from 'react';
import { Badge } from '@/components/ui/badge';
import { DynamicCellComponentProps } from '../component-registry';

export default function ApiKeyProjectsCell({ row }: DynamicCellComponentProps) {
  const projectNames = row.project_ids as string[] | null;

  if (!projectNames || projectNames.length === 0) {
    return <Badge variant="outline">All projects</Badge>;
  }

  return (
    <div className="flex flex-wrap gap-1">
      {projectNames.map((name) => (
        <Badge key={name} variant="default">
          {name}
        </Badge>
      ))}
    </div>
  );
}
