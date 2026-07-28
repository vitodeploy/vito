import { FormEvent, useEffect, useId, useMemo, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { Sheet, SheetClose, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { Form, FormField } from '@/components/ui/form';
import { Label } from '@/components/ui/label';
import { RadioGroup } from '@/components/ui/radio-group';
import RadioCard from '@/components/radio-card';
import { Skeleton } from '@/components/ui/skeleton';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { AlertCircleIcon, LoaderCircleIcon, PlusIcon, RefreshCwIcon } from 'lucide-react';
import { useInputFocus } from '@/stores/useInputFocus';
import { EnvVariable } from '@/types/env';
import { generateUniqueKey } from '@/lib/env';
import { rowId } from '@/lib/utils';
import EnvVariableRow from '@/pages/application/components/env-variable-row';

type ApplyChoice = '' | 'config' | 'restart';

export default function WorkerEnvDialog({
  open,
  onOpenChange,
  serverId,
  workerId,
  siteId,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  serverId: number;
  workerId?: number;
  siteId?: number;
}) {
  const setFocused = useInputFocus((state) => state.setFocused);
  const groupLabelId = useId();
  const [variables, setVariables] = useState<EnvVariable[]>([]);
  const [apply, setApply] = useState<ApplyChoice>('');

  useEffect(() => {
    setFocused(open);
    return () => setFocused(false);
  }, [open, setFocused]);

  const duplicateKeys = useMemo(() => {
    const keyCounts = new Map<string, number>();
    variables.forEach((v) => {
      const key = v.key.trim();
      if (key) {
        keyCounts.set(key, (keyCounts.get(key) || 0) + 1);
      }
    });
    const duplicates = new Set<string>();
    keyCounts.forEach((count, key) => {
      if (count > 1) {
        duplicates.add(key);
      }
    });
    return duplicates;
  }, [variables]);

  const hasDuplicates = duplicateKeys.size > 0;
  const workerMode = workerId !== undefined;
  const choiceMissing = workerMode && apply === '';

  const form = useForm<{ variables: Array<{ key: string; value: string; is_secret: boolean }>; restart?: boolean }>({
    variables: [],
  });

  const query = useQuery({
    queryKey: ['workerEnv', serverId, workerId, siteId],
    queryFn: async () => {
      const response = await axios.get(
        workerMode
          ? route('workers.env', { server: serverId, worker: workerId })
          : route('site-settings.worker-env', { server: serverId, site: siteId }),
      );
      const parsed = (response.data?.variables ?? []).map((v: { key: string; value: string; is_secret: boolean }) => ({
        id: rowId(),
        key: v.key,
        value: v.value,
        isSecret: v.is_secret,
        isNew: false,
      }));
      setVariables(parsed);
      return response.data;
    },
    retry: false,
    enabled: open,
    refetchOnWindowFocus: false,
  });

  const handleVariableChange = (index: number, updatedVariable: EnvVariable) => {
    setVariables((prev) => {
      const newVariables = [...prev];
      newVariables[index] = updatedVariable;
      return newVariables;
    });
  };

  const handleVariableDelete = (index: number) => {
    setVariables((prev) => prev.filter((_, i) => i !== index));
  };

  const handleAddVariable = () => {
    setVariables((prev) => [
      ...prev,
      {
        id: rowId(),
        key: generateUniqueKey(prev.map((v) => v.key)),
        value: '',
        isSecret: false,
        isNew: true,
      },
    ]);
  };

  const submit = (e: FormEvent) => {
    e.preventDefault();
    if (choiceMissing || !query.isSuccess) {
      return;
    }
    form.transform(() => ({
      variables: variables.map((v) => ({
        key: v.key,
        value: v.value,
        is_secret: v.isSecret,
      })),
      ...(workerMode ? { restart: apply === 'restart' } : {}),
    }));
    form.patch(
      workerMode
        ? route('workers.update-env', { server: serverId, worker: workerId })
        : route('site-settings.update-worker-env', { server: serverId, site: siteId }),
      {
        onSuccess: () => onOpenChange(false),
      },
    );
  };

  return (
    <Sheet open={open} onOpenChange={onOpenChange}>
      <SheetContent className="flex flex-col overflow-hidden sm:max-w-4xl" onCloseAutoFocus={(e) => e.preventDefault()}>
        <SheetHeader>
          <SheetTitle>Worker Environment Variables</SheetTitle>
          <SheetDescription>Environment variables passed to the worker process via supervisor.</SheetDescription>
        </SheetHeader>
        <Form id="worker-env-form" className="flex min-h-0 flex-1 flex-col p-4" onSubmit={submit}>
          <div className="min-h-0 flex-1 overflow-y-auto">
            {form.errors && Object.keys(form.errors).length > 0 && (
              <Alert variant="destructive" className="mb-4">
                <AlertCircleIcon className="size-4" />
                <AlertDescription>
                  {Object.values(form.errors).map((error, i) => (
                    <div key={i}>{error}</div>
                  ))}
                </AlertDescription>
              </Alert>
            )}
            {query.isError ? (
              <Alert variant="destructive">
                <AlertCircleIcon className="size-4" />
                <AlertDescription className="flex items-center gap-2">
                  Failed to load the environment variables.
                  <Button type="button" variant="outline" size="sm" onClick={() => query.refetch()} disabled={query.isFetching}>
                    <RefreshCwIcon className={query.isFetching ? 'animate-spin' : ''} />
                    Retry
                  </Button>
                </AlertDescription>
              </Alert>
            ) : query.isSuccess ? (
              <div className="space-y-3 py-2">
                {variables.map((variable, index) => (
                  <EnvVariableRow
                    key={variable.id}
                    variable={variable}
                    onChange={(updated) => handleVariableChange(index, updated)}
                    onDelete={() => handleVariableDelete(index)}
                    error={duplicateKeys.has(variable.key.trim()) ? 'Duplicate key' : undefined}
                  />
                ))}
                <Button type="button" variant="outline" className="w-full" onClick={handleAddVariable}>
                  <PlusIcon className="mr-2 size-4" />
                  Add Variable
                </Button>
              </div>
            ) : (
              <div className="space-y-3 py-2">
                {[...Array(3)].map((_, i) => (
                  <div key={i} className="flex gap-2">
                    <Skeleton className="h-9 w-48" />
                    <Skeleton className="h-9 flex-1" />
                    <Skeleton className="size-9" />
                  </div>
                ))}
              </div>
            )}
          </div>
          <div className="shrink-0 pt-4">
            {workerMode ? (
              <FormField>
                <Label id={groupLabelId}>How should we apply this change?</Label>
                <RadioGroup value={apply} onValueChange={(value) => setApply(value as ApplyChoice)} aria-labelledby={groupLabelId} className="gap-2">
                  <RadioCard
                    value="config"
                    selected={apply === 'config'}
                    onSelect={(value) => setApply(value as ApplyChoice)}
                    title="Update config only"
                    description="The worker keeps running with its current variables. The change takes effect on the next restart or deploy."
                  />
                  <RadioCard
                    value="restart"
                    selected={apply === 'restart'}
                    onSelect={(value) => setApply(value as ApplyChoice)}
                    title="Update and restart now"
                    description="Rewrites the config and restarts the worker immediately so the new variables take effect right away."
                  />
                </RadioGroup>
              </FormField>
            ) : (
              <p className="text-muted-foreground text-sm">
                These variables will be applied when the application worker is created on the next deploy.
              </p>
            )}
          </div>
        </Form>
        <SheetFooter className="shrink-0">
          <div className="flex items-center gap-2">
            <SheetClose asChild>
              <Button variant="outline">Cancel</Button>
            </SheetClose>
            <Button form="worker-env-form" type="submit" disabled={form.processing || !query.isSuccess || hasDuplicates || choiceMissing}>
              {form.processing && <LoaderCircleIcon className="animate-spin" />}
              Save
            </Button>
          </div>
        </SheetFooter>
      </SheetContent>
    </Sheet>
  );
}
