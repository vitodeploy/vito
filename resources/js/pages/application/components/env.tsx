import React, { FormEvent, ReactNode, useEffect, useMemo, useRef, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { Editor, useMonaco } from '@monaco-editor/react';
import { Sheet, SheetClose, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { Form } from '@/components/ui/form';
import { Skeleton } from '@/components/ui/skeleton';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { LoaderCircleIcon, PlusIcon, RefreshCwIcon, UploadIcon, AlertCircleIcon, ClipboardIcon, InfoIcon } from 'lucide-react';
import { Site } from '@/types/site';
import { Input } from '@/components/ui/input';
import { useInputFocus } from '@/stores/useInputFocus';
import { useAppearance } from '@/hooks/use-appearance';
import { registerDotEnvLanguage } from '@/lib/editor';
import { EnvVariable } from '@/types/env';
import EnvVariableRow from './env-variable-row';
import { generateUniqueKey } from '@/lib/env';
import { cn, rowId } from '@/lib/utils';

type ParsedVariable = { key: string; value: string; is_secret: boolean };

const defaultEnvPath = (site: Site): string => site.type_data.env_path || `${site.path}/.env`;

const toVariables = (parsed: ParsedVariable[], isNew = true): EnvVariable[] =>
  parsed.map((v) => ({
    id: rowId(),
    key: v.key,
    value: v.value,
    isSecret: v.is_secret,
    isNew,
  }));

const errorMessage = (error: unknown, fallback: string): string => {
  if (axios.isAxiosError(error)) {
    return error.response?.data?.message ?? error.response?.data?.error ?? fallback;
  }
  if (error instanceof Error) {
    return error.message;
  }
  return fallback;
};

export default function Env({ site, children }: { site: Site; children: ReactNode }) {
  const setFocused = useInputFocus((state) => state.setFocused);
  const { getActualAppearance } = useAppearance();
  const monaco = useMonaco();
  const [open, setOpen] = useState(false);
  const [variables, setVariables] = useState<EnvVariable[]>([]);
  const [rawContent, setRawContent] = useState('');
  const [mode, setMode] = useState<'variables' | 'classic'>('variables');
  const [variablesDirty, setVariablesDirty] = useState(false);
  const [isSwitching, setIsSwitching] = useState(false);
  const [canEdit, setCanEdit] = useState<boolean | undefined>(undefined);
  const [committedPath, setCommittedPath] = useState(defaultEnvPath(site));
  const [isUploading, setIsUploading] = useState(false);
  const [isPasting, setIsPasting] = useState(false);
  const [uploadError, setUploadError] = useState<string | null>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);

  useEffect(() => {
    if (monaco) {
      registerDotEnvLanguage(monaco);
    }
  }, [monaco]);

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
    path: defaultEnvPath(site),
  });

  const handleOpenChange = (isOpen: boolean) => {
    setOpen(isOpen);
    setFocused(isOpen);
    if (!isOpen) {
      setMode('variables');
      setVariables([]);
      setRawContent('');
      setVariablesDirty(false);
      setUploadError(null);
    }
  };

  const submit = (e: FormEvent) => {
    e.preventDefault();

    if (mode === 'classic') {
      form.transform(() => ({ env: rawContent, path: committedPath }));
    } else {
      form.transform(() => ({
        variables: variables.map((v) => ({
          key: v.key,
          value: v.value,
          is_secret: v.isSecret,
        })),
        path: committedPath,
      }));
    }

    form.put(route('application.update-env', { server: site.server_id, site: site.id }), {
      onSuccess: () => handleOpenChange(false),
    });
  };

  const query = useQuery({
    queryKey: ['application.env', site.server_id, site.id, committedPath],
    queryFn: async () => {
      const response = await axios.get(
        route('application.env', {
          server: site.server_id,
          site: site.id,
          env: committedPath,
        }),
      );
      return response.data;
    },
    retry: false,
    enabled: open,
    refetchOnWindowFocus: false,
    refetchOnReconnect: false,
  });

  useEffect(() => {
    if (!query.data) {
      return;
    }

    setVariables(toVariables(query.data.variables ?? [], false));
    setRawContent(query.data.env ?? '');
    setCanEdit(query.data.can_edit === true);
    setVariablesDirty(false);
  }, [query.data, query.dataUpdatedAt]);

  const queryError = useMemo(() => (query.isError ? errorMessage(query.error, 'Failed to read the .env file') : null), [query.isError, query.error]);

  const handleVariableChange = (index: number, updatedVariable: EnvVariable) => {
    setVariablesDirty(true);
    setVariables((prev) => {
      const newVariables = [...prev];
      newVariables[index] = updatedVariable;
      return newVariables;
    });
  };

  const handleVariableDelete = (index: number) => {
    setVariablesDirty(true);
    setVariables((prev) => prev.filter((_, i) => i !== index));
  };

  const handleAddVariable = () => {
    setVariablesDirty(true);
    const existingKeys = variables.map((v) => v.key);
    const newKey = generateUniqueKey(existingKeys);
    setVariables((prev) => [
      ...prev,
      {
        id: rowId(),
        key: newKey,
        value: '',
        isSecret: false,
        isNew: true, // Mark as new so user can toggle secret and see value
      },
    ]);
  };

  const parseRawContent = async (content: string) => {
    const response = await axios.post(
      route('application.parse-env', {
        server: site.server_id,
        site: site.id,
      }),
      { content },
    );

    if (response.data?.representable === false) {
      throw new Error(
        canEdit
          ? 'This file has content the variables form cannot represent, such as a value spanning several lines. Edit it in Classic mode instead.'
          : 'This file has content the variables form cannot represent, such as a value spanning several lines.',
      );
    }

    return (response.data?.variables ?? []) as ParsedVariable[];
  };

  const switchMode = async () => {
    setUploadError(null);

    if (mode === 'variables') {
      if (!variablesDirty) {
        setMode('classic');
        return;
      }

      setIsSwitching(true);
      try {
        const response = await axios.post(
          route('application.stringify-env', {
            server: site.server_id,
            site: site.id,
          }),
          {
            variables: variables.map((v) => ({ key: v.key, value: v.value })),
          },
        );
        setRawContent(response.data?.env ?? '');
        setMode('classic');
      } catch (error) {
        setUploadError(errorMessage(error, 'Failed to convert the variables to raw text'));
      } finally {
        setIsSwitching(false);
      }
      return;
    }

    setIsSwitching(true);
    try {
      const parsed = await parseRawContent(rawContent);

      const previous = new Map<string, EnvVariable[]>();
      variables.forEach((v) => {
        previous.set(v.key, [...(previous.get(v.key) ?? []), v]);
      });

      setVariables(
        parsed.map((v) => {
          const match = previous.get(v.key)?.shift();

          return {
            id: match?.id ?? rowId(),
            key: v.key,
            value: v.value,
            isSecret: match ? match.isSecret : v.is_secret,
            isNew: match ? match.isNew : true,
          };
        }),
      );
      setVariablesDirty(false);
      setMode('variables');
    } catch (error) {
      setUploadError(errorMessage(error, 'Failed to parse the raw content'));
    } finally {
      setIsSwitching(false);
    }
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
          const parsed = await parseRawContent(content);
          setVariablesDirty(true);
          setVariables(toVariables(parsed));
        } catch (error) {
          setUploadError(errorMessage(error, 'Failed to parse uploaded file'));
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

      const parsed = await parseRawContent(content);

      setVariablesDirty(true);
      setVariables(toVariables(parsed));
    } catch (error) {
      setUploadError(errorMessage(error, 'Failed to read from clipboard'));
    } finally {
      setIsPasting(false);
    }
  };

  const handleRefresh = () => {
    if (committedPath !== form.data.path) {
      setCommittedPath(form.data.path);
      return;
    }
    query.refetch();
  };

  const busy = query.isFetching || isUploading || isPasting || isSwitching;
  const pathUncommitted = form.data.path !== committedPath;

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
            <Input
              name="path"
              value={form.data.path}
              onChange={(e) => form.setData('path', e.target.value)}
              autoFocus={false}
              className="flex-1"
              disabled={canEdit === false}
            />
            <Tooltip>
              <TooltipTrigger asChild>
                <Button type="button" variant="outline" size="icon" onClick={handleRefresh} disabled={busy}>
                  <RefreshCwIcon className={query.isFetching ? 'animate-spin' : ''} />
                </Button>
              </TooltipTrigger>
              <TooltipContent>{pathUncommitted ? 'Load this path' : 'Reload from server'}</TooltipContent>
            </Tooltip>
            <Tooltip>
              <TooltipTrigger asChild>
                <Button type="button" variant="outline" size="icon" onClick={handleUploadClick} disabled={busy || mode === 'classic'}>
                  {isUploading ? <LoaderCircleIcon className="animate-spin" /> : <UploadIcon />}
                </Button>
              </TooltipTrigger>
              <TooltipContent>Upload .env file</TooltipContent>
            </Tooltip>
            <Tooltip>
              <TooltipTrigger asChild>
                <Button type="button" variant="outline" size="icon" onClick={handlePasteFromClipboard} disabled={busy || mode === 'classic'}>
                  {isPasting ? <LoaderCircleIcon className="animate-spin" /> : <ClipboardIcon />}
                </Button>
              </TooltipTrigger>
              <TooltipContent>Paste from clipboard</TooltipContent>
            </Tooltip>
            <input ref={fileInputRef} type="file" accept="*" onChange={handleFileUpload} className="hidden" />
          </div>
          <div className={cn('flex min-h-0 flex-1 flex-col gap-4', mode === 'classic' ? 'overflow-hidden' : 'overflow-y-auto')}>
            {uploadError && (
              <Alert variant="destructive" className="shrink-0">
                <AlertCircleIcon className="size-4" />
                <AlertDescription>{uploadError}</AlertDescription>
              </Alert>
            )}
            {pathUncommitted && (
              <Alert className="shrink-0">
                <InfoIcon className="size-4" />
                <AlertDescription>Load the new path before saving, using the refresh button above.</AlertDescription>
              </Alert>
            )}
            {queryError && (
              <Alert variant="destructive" className="shrink-0">
                <AlertCircleIcon className="size-4" />
                <AlertDescription>{queryError}</AlertDescription>
              </Alert>
            )}
            {form.errors && Object.keys(form.errors).length > 0 && (
              <Alert variant="destructive" className="shrink-0">
                <AlertCircleIcon className="size-4" />
                <AlertDescription>
                  {Object.values(form.errors).map((error, i) => (
                    <div key={i}>{error}</div>
                  ))}
                </AlertDescription>
              </Alert>
            )}
            {mode === 'classic' ? (
              <div className="flex min-h-0 flex-1 flex-col gap-4">
                <Alert className="shrink-0">
                  <InfoIcon className="size-4" />
                  <AlertDescription>
                    Comments are shown here, but the variables form cannot store them - they are discarded once you edit a variable or save from that
                    form.
                  </AlertDescription>
                </Alert>
                <div className="min-h-0 flex-1">
                  <Editor
                    defaultLanguage="dotenv"
                    value={rawContent}
                    onChange={(value) => setRawContent(value ?? '')}
                    theme={getActualAppearance() === 'dark' ? 'vs-dark' : 'vs'}
                    className="h-full"
                    options={{ fontSize: 14 }}
                  />
                </div>
              </div>
            ) : query.isError ? null : query.isSuccess ? (
              <fieldset disabled={isSwitching || canEdit === false} className="flex flex-col gap-3 py-2">
                {variables.map((variable, index) => (
                  <EnvVariableRow
                    key={variable.id}
                    variable={variable}
                    revealable={canEdit === true}
                    onChange={(updated) => handleVariableChange(index, updated)}
                    onDelete={() => handleVariableDelete(index)}
                    error={duplicateKeys.has(variable.key.trim()) ? 'Duplicate key' : undefined}
                  />
                ))}
                {variables.length === 0 && (
                  <p className="text-muted-foreground text-sm">
                    This file has no variables{canEdit ? '. Add one below, or use Classic mode to save it empty' : ''}.
                  </p>
                )}
                <Button type="button" variant="outline" className="w-full" onClick={handleAddVariable}>
                  <PlusIcon className="mr-2 size-4" />
                  Add Variable
                </Button>
              </fieldset>
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
          <div className="flex w-full items-center justify-between">
            <div className="flex items-center gap-2">
              <SheetClose asChild>
                <Button variant="outline">Cancel</Button>
              </SheetClose>
              <Button
                form="update-env-form"
                type="submit"
                disabled={
                  form.processing ||
                  busy ||
                  query.isError ||
                  canEdit !== true ||
                  pathUncommitted ||
                  (mode === 'variables' && (hasDuplicates || variables.length === 0))
                }
              >
                {(form.processing || busy) && <LoaderCircleIcon className="animate-spin" />}
                Save
              </Button>
            </div>
            {canEdit === true && (
              <Button type="button" variant="outline" onClick={switchMode} disabled={busy || (mode === 'variables' && query.isError)}>
                {isSwitching && <LoaderCircleIcon className="animate-spin" />}
                {mode === 'variables' ? 'Classic mode' : 'Variables mode'}
              </Button>
            )}
          </div>
        </SheetFooter>
      </SheetContent>
    </Sheet>
  );
}
