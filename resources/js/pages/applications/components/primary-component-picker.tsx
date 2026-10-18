import { FormField } from '@/components/ui/form';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputError from '@/components/ui/input-error';
import { PrimaryCandidate } from '@/types/application';

export default function PrimaryComponentPicker({ value, onChange, candidates, error }: {
  value: string;
  onChange: (value: string) => void;
  candidates: PrimaryCandidate[];
  error?: string;
}) {
  return (
    <FormField>
      <Label htmlFor="primary-component">Primary component</Label>
      <Select value={value || 'none'} onValueChange={(value) => onChange(value === 'none' ? '' : value)}>
        <SelectTrigger id="primary-component"><SelectValue /></SelectTrigger>
        <SelectContent>
          <SelectItem value="none">None</SelectItem>
          {candidates.map((candidate) => <SelectItem key={candidate.id} value={String(candidate.id)}>{candidate.name}</SelectItem>)}
        </SelectContent>
      </Select>
      <p className="text-muted-foreground text-sm">The primary runs the release command. Deployments require a primary component.</p>
      <InputError message={error} />
    </FormField>
  );
}
