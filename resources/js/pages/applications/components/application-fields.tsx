import { Link } from '@inertiajs/react';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { SourceControl } from '@/types/source-control';
import { useConfigs } from '@/stores/bootstrap-store';
import { Configs } from '@/types';
import { Field } from './section';

export type ApplicationFormData = {
  name: string;
  repository: string;
  source_control_id: string;
};

export const usableSourceControls = (sourceControls: SourceControl[], configs: Configs) =>
  sourceControls.filter((source) => configs.source_control.providers[source.provider]?.usable_for_sites !== false);

export default function ApplicationFields({
  data,
  errors,
  onChange,
  sourceControls,
  inline,
}: {
  data: ApplicationFormData;
  errors: Partial<Record<keyof ApplicationFormData, string>>;
  onChange: (name: keyof ApplicationFormData, value: string) => void;
  sourceControls: SourceControl[];
  inline?: boolean;
}) {
  const available = usableSourceControls(sourceControls, useConfigs()!);

  return (
    <>
      <Field
        id="application-source-control"
        label="Source control"
        inline={inline}
        error={errors.source_control_id}
        description={
          available.length === 0 && (
            <>
              <Link href="/settings/source-controls" className="underline">
                Connect a source control
              </Link>{' '}
              to select a repository.
            </>
          )
        }
      >
        <Select value={data.source_control_id} onValueChange={(value) => onChange('source_control_id', value)}>
          <SelectTrigger id="application-source-control">
            <SelectValue placeholder="Select a source control" />
          </SelectTrigger>
          <SelectContent>
            {available.map((source) => (
              <SelectItem key={source.id} value={String(source.id)}>
                {source.name}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </Field>
      <Field id="application-repository" label="Repository" inline={inline} error={errors.repository}>
        <Input
          id="application-repository"
          value={data.repository}
          onChange={(e) => onChange('repository', e.target.value)}
          placeholder="organization/repository"
          className="font-mono"
        />
      </Field>
      <Field id="application-name" label="Name" inline={inline} error={errors.name}>
        <Input id="application-name" value={data.name} onChange={(e) => onChange('name', e.target.value)} />
      </Field>
    </>
  );
}
