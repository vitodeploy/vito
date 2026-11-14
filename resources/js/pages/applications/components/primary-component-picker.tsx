import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { PrimaryCandidate } from '@/types/application';
import { Field } from './section';

export default function PrimaryComponentPicker({
  value,
  onChange,
  candidates,
  error,
  inline,
}: {
  value: string;
  onChange: (value: string) => void;
  candidates: PrimaryCandidate[];
  error?: string;
  inline?: boolean;
}) {
  return (
    <Field
      id="primary-component"
      label="Primary component"
      description="Runs the release command. Deployments require one."
      error={error}
      inline={inline}
    >
      <Select value={value || 'none'} onValueChange={(value) => onChange(value === 'none' ? '' : value)}>
        <SelectTrigger id="primary-component">
          <SelectValue />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="none">None</SelectItem>
          {candidates.map((candidate) => (
            <SelectItem key={candidate.id} value={String(candidate.id)}>
              {candidate.name}
            </SelectItem>
          ))}
        </SelectContent>
      </Select>
    </Field>
  );
}
