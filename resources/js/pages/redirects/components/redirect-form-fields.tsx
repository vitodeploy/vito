import { InertiaFormProps } from '@inertiajs/react';
import { FormField, FormFields } from '@/components/ui/form';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/ui/input-error';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';

export type RedirectForm = {
  mode: string;
  from: string;
  to: string;
  websocket: boolean;
};

export default function RedirectFormFields({ form }: { form: InertiaFormProps<RedirectForm> }) {
  const onModeChange = (value: string) => {
    form.setData((data) => ({
      ...data,
      mode: value,
      websocket: value === '1000' ? data.websocket : false,
    }));
  };

  return (
    <FormFields>
      <FormField>
        <Label htmlFor="mode">Mode</Label>
        <Select onValueChange={onModeChange} value={form.data.mode}>
          <SelectTrigger id="mode">
            <SelectValue placeholder="Select mode" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="301">301 - Moved Permanently</SelectItem>
            <SelectItem value="302">302 - Found</SelectItem>
            <SelectItem value="307">307 - Temporary Redirect</SelectItem>
            <SelectItem value="308">308 - Permanent Redirect</SelectItem>
            <SelectItem value="1000">Proxy (/docs to https://docs.example.com)</SelectItem>
          </SelectContent>
        </Select>
        <InputError message={form.errors.mode} />
      </FormField>
      <FormField>
        <Label htmlFor="from">From</Label>
        <Input
          type="text"
          id="from"
          name="from"
          value={form.data.from}
          onChange={(e) => form.setData('from', e.target.value)}
          placeholder="/path/to/redirect/"
        />
        <InputError message={form.errors.from} />
      </FormField>
      <FormField>
        <Label htmlFor="to">To</Label>
        <Input
          type="text"
          id="to"
          name="to"
          value={form.data.to}
          onChange={(e) => form.setData('to', e.target.value)}
          placeholder="https://new-url/"
        />
        <InputError message={form.errors.to} />
      </FormField>
      {form.data.mode === '1000' && (
        <FormField>
          <div className="flex items-center gap-3">
            <Checkbox id="websocket" name="websocket" checked={form.data.websocket} onClick={() => form.setData('websocket', !form.data.websocket)} />
            <Label htmlFor="websocket">WebSocket support</Label>
          </div>
          <InputError message={form.errors.websocket} />
        </FormField>
      )}
    </FormFields>
  );
}
