import { Link } from '@inertiajs/react';
import { FormField } from '@/components/ui/form';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/ui/input-error';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { SourceControl } from '@/types/source-control';
import { useConfigs } from '@/stores/bootstrap-store';

export type ApplicationFormData = {
  name: string;
  repository: string;
  source_control_id: string;
};

export default function ApplicationFields({
  data,
  errors,
  onChange,
  sourceControls,
}: {
  data: ApplicationFormData;
  errors: Partial<Record<keyof ApplicationFormData, string>>;
  onChange: (name: keyof ApplicationFormData, value: string) => void;
  sourceControls: SourceControl[];
}) {
  const configs = useConfigs()!;
  const available = sourceControls.filter((source) => configs.source_control.providers[source.provider]?.usable_for_sites !== false);

  return (
    <>
      <FormField>
        <Label htmlFor="application-name">Name</Label>
        <Input id="application-name" value={data.name} onChange={(e) => onChange('name', e.target.value)} />
        <InputError message={errors.name} />
      </FormField>
      <FormField>
        <Label htmlFor="application-source-control">Source control</Label>
        <Select value={data.source_control_id} onValueChange={(value) => onChange('source_control_id', value)}>
          <SelectTrigger id="application-source-control">
            <SelectValue placeholder="Select a source control" />
          </SelectTrigger>
          <SelectContent>
            {available.map((source) => (
              <SelectItem key={source.id} value={String(source.id)}>{source.name}</SelectItem>
            ))}
          </SelectContent>
        </Select>
        {available.length === 0 && (
          <p className="text-muted-foreground text-sm">
            <Link href="/settings/source-controls" className="underline">Connect a source control</Link> to select a repository.
          </p>
        )}
        <InputError message={errors.source_control_id} />
      </FormField>
      <FormField>
        <Label htmlFor="application-repository">Repository</Label>
        <Input id="application-repository" value={data.repository} onChange={(e) => onChange('repository', e.target.value)} placeholder="organization/repository" />
        <InputError message={errors.repository} />
      </FormField>
    </>
  );
}
