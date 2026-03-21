import React, { FormEvent, ReactNode, useMemo, useRef, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { Sheet, SheetClose, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { Form } from '@/components/ui/form';
import { Skeleton } from '@/components/ui/skeleton';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { LoaderCircleIcon, PlusIcon, RefreshCwIcon, UploadIcon, AlertCircleIcon, ClipboardIcon } from 'lucide-react';
import { Site } from '@/types/site';
import { Input } from '@/components/ui/input';
import { useInputFocus } from '@/stores/useInputFocus';
import { EnvVariable } from '@/types/env';
import EnvVariableRow from './env-variable-row';

function generateUniqueKey(existingKeys: string[]): string {
  let counter = 1;
  let newKey = 'NEW_VARIABLE';

  while (existingKeys.includes(newKey)) {
    newKey = `NEW_VARIABLE_${counter}`;
    counter++;
  }

  return newKey;
}

export default function Env({ site, children }: { site: Site; children: ReactNode }) {
  const setFocused = useInputFocus((state) => state.setFocused);
  const [open, setOpen] = useState(false);
  const [variables, setVariables] = useState<EnvVariable[]>([]);
  const [isUploading, setIsUploading] = useState(false);
  const [isPasting, setIsPasting] = useState(false);
  const [uploadError, setUploadError] = useState<string | null>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);

  // Find duplicate keys
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

  const form = useForm<{
    variables: Array<{ key: string; value: string; is_secret: boolean }>;
    path: string;
  }>({
    variables: [],
    path: site.type_data.env_path || `${site.path}/.env`,
  });

  const handleOpenChange = (isOpen: boolean) => {
    setOpen(isOpen);
    setFocused(isOpen);
  };

  const submit = (e: FormEvent) => {
    e.preventDefault();

    // Transform variables for submission
    form.transform(() => ({
      variables: variables.map((v) => ({
        key: v.key,
        value: v.value,
        is_secret: v.isSecret,
      })),
      path: form.data.path,
    }));

    form.put(route('application.update-env', { server: site.server_id, site: site.id }), {
      onSuccess: () => handleOpenChange(false),
    });
  };

  const query = useQuery({
    queryKey: ['application.env', site.server_id, site.id, form.data.path],
    queryFn: async () => {
      const response = await axios.get(
        route('application.env', {
          server: site.server_id,
          site: site.id,
          env: form.data.path,
        }),
      );
      if (response.data?.variables) {
        // Use server-parsed variables (with is_secret from backend)
        // Variables from server are NOT new (isNew = false)
        const parsed = response.data.variables.map((v: { key: string; value: string; is_secret: boolean }) => ({
          key: v.key,
          value: v.value,
          isSecret: v.is_secret,
          isNew: false,
        }));
        setVariables(parsed);
      }
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
    const existingKeys = variables.map((v) => v.key);
    const newKey = generateUniqueKey(existingKeys);
    setVariables((prev) => [
      ...prev,
      {
        key: newKey,
        value: '',
        isSecret: false,
        isNew: true, // Mark as new so user can toggle secret and see value
      },
    ]);
  };

  const handleFileUpload = async (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = async (e) => {
      const content = e.target?.result as string;
      if (content) {
        setIsUploading(true);
        setUploadError(null);
        try {
          // Send to backend for parsing
          const response = await axios.post(
            route('application.parse-env', {
              server: site.server_id,
              site: site.id,
            }),
            { content },
          );

          if (response.data?.variables) {
            // Mark all uploaded variables as new
            const parsed = response.data.variables.map((v: { key: string; value: string; is_secret: boolean }) => ({
              key: v.key,
              value: v.value,
              isSecret: v.is_secret,
              isNew: true,
            }));
            setVariables(parsed);
          }
        } catch (error) {
          if (axios.isAxiosError(error)) {
            setUploadError(error.response?.data?.message || 'Failed to parse uploaded file');
          } else {
            setUploadError('Failed to parse uploaded file');
          }
        } finally {
          setIsUploading(false);
        }
      }
    };
    reader.readAsText(file);

    // Reset the input so the same file can be selected again
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
  };

  const handleUploadClick = () => {
    fileInputRef.current?.click();
  };

  const handlePasteFromClipboard = async () => {
    try {
      const content = await navigator.clipboard.readText();
      if (!content.trim()) {
        setUploadError('Clipboard is empty');
        return;
      }

      setIsPasting(true);
      setUploadError(null);

      const response = await axios.post(
        route('application.parse-env', {
          server: site.server_id,
          site: site.id,
        }),
        { content },
      );

      if (response.data?.variables) {
        const parsed = response.data.variables.map((v: { key: string; value: string; is_secret: boolean }) => ({
          key: v.key,
          value: v.value,
          isSecret: v.is_secret,
          isNew: true,
        }));
        setVariables(parsed);
      }
    } catch (error) {
      if (axios.isAxiosError(error)) {
        setUploadError(error.response?.data?.message || 'Failed to parse clipboard content');
      } else {
        setUploadError('Failed to read from clipboard');
      }
    } finally {
      setIsPasting(false);
    }
  };

  return (
    <Sheet open={open} onOpenChange={handleOpenChange}>
      <SheetTrigger asChild>{children}</SheetTrigger>
      <SheetContent className="flex flex-col overflow-hidden sm:max-w-4xl">
        <SheetHeader>
          <SheetTitle>Environment Variables</SheetTitle>
          <SheetDescription>Manage environment variables for your application.</SheetDescription>
        </SheetHeader>
        <Form id="update-env-form" className="flex min-h-0 flex-1 flex-col p-4" onSubmit={submit}>
          <div className="mb-4 flex shrink-0 items-center gap-2">
            <Input name="path" value={form.data.path} onChange={(e) => form.setData('path', e.target.value)} autoFocus={false} className="flex-1" />
            <Tooltip>
              <TooltipTrigger asChild>
                <Button
                  type="button"
                  variant="outline"
                  size="icon"
                  onClick={() => query.refetch()}
                  disabled={query.isFetching || isUploading || isPasting}
                >
                  <RefreshCwIcon className={query.isFetching ? 'animate-spin' : ''} />
                </Button>
              </TooltipTrigger>
              <TooltipContent>Reload from server</TooltipContent>
            </Tooltip>
            <Tooltip>
              <TooltipTrigger asChild>
                <Button
                  type="button"
                  variant="outline"
                  size="icon"
                  onClick={handleUploadClick}
                  disabled={query.isFetching || isUploading || isPasting}
                >
                  {isUploading ? <LoaderCircleIcon className="animate-spin" /> : <UploadIcon />}
                </Button>
              </TooltipTrigger>
              <TooltipContent>Upload .env file</TooltipContent>
            </Tooltip>
            <Tooltip>
              <TooltipTrigger asChild>
                <Button
                  type="button"
                  variant="outline"
                  size="icon"
                  onClick={handlePasteFromClipboard}
                  disabled={query.isFetching || isUploading || isPasting}
                >
                  {isPasting ? <LoaderCircleIcon className="animate-spin" /> : <ClipboardIcon />}
                </Button>
              </TooltipTrigger>
              <TooltipContent>Paste from clipboard</TooltipContent>
            </Tooltip>
            <input ref={fileInputRef} type="file" accept="*" onChange={handleFileUpload} className="hidden" />
          </div>
          <div className="min-h-0 flex-1 overflow-y-auto">
            {uploadError && (
              <Alert variant="destructive" className="mb-4">
                <AlertCircleIcon className="size-4" />
                <AlertDescription>{uploadError}</AlertDescription>
              </Alert>
            )}
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
            {query.isSuccess ? (
              <div className="space-y-3 py-2">
                {variables.map((variable, index) => (
                  <EnvVariableRow
                    key={`env-${index}`}
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
                {[...Array(5)].map((_, i) => (
                  <div key={i} className="flex gap-2">
                    <Skeleton className="h-9 w-48" />
                    <Skeleton className="h-9 flex-1" />
                    <Skeleton className="size-9" />
                  </div>
                ))}
              </div>
            )}
          </div>
        </Form>
        <SheetFooter className="shrink-0">
          <div className="flex items-center gap-2">
            <SheetClose asChild>
              <Button variant="outline">Cancel</Button>
            </SheetClose>
            <Button form="update-env-form" disabled={form.processing || query.isLoading || hasDuplicates} onClick={submit}>
              {(form.processing || query.isLoading) && <LoaderCircleIcon className="animate-spin" />}
              Save
            </Button>
          </div>
        </SheetFooter>
      </SheetContent>
    </Sheet>
  );
}
