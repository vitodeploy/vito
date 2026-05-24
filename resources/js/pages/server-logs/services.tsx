import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import axios from 'axios';
import { BookOpenIcon, CogIcon, DownloadIcon, EraserIcon, LoaderCircleIcon, RefreshCwIcon } from 'lucide-react';
import { Server } from '@/types/server';
import ServerLayout from '@/layouts/server/layout';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Combobox } from '@/components/ui/combobox';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { ScrollArea, ScrollBar } from '@/components/ui/scroll-area';
import FormSuccessful from '@/components/form-successful';

type CatalogueItem = {
  key: string;
  service_label: string;
  label: string;
  display_target: string;
  source: 'file' | 'journal';
};

const LINE_OPTIONS = ['100', '200', '500', '1000', '2000'] as const;
type LineOption = (typeof LINE_OPTIONS)[number];

export default function ServiceLogs() {
  const page = usePage<{
    title: string;
    server: Server;
    catalogue: CatalogueItem[];
  }>();

  const { catalogue, server } = page.props;

  const itemsByKey = useMemo(() => Object.fromEntries(catalogue.map((c) => [c.key, c])), [catalogue]);

  const [selectedKey, setSelectedKey] = useState<string>('');
  const [lines, setLines] = useState<LineOption>('100');
  const [search, setSearch] = useState<string>('');
  const [debouncedSearch, setDebouncedSearch] = useState<string>('');
  const [content, setContent] = useState<string>('');
  const [displayTarget, setDisplayTarget] = useState<string>('');
  const [isLoading, setIsLoading] = useState(false);
  const [isDownloading, setIsDownloading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const abortRef = useRef<AbortController | null>(null);

  const selected = selectedKey ? itemsByKey[selectedKey] : undefined;

  useEffect(() => {
    const t = setTimeout(() => setDebouncedSearch(search), 300);
    return () => clearTimeout(t);
  }, [search]);

  const fetchLog = useCallback(() => {
    if (!selectedKey) return;

    abortRef.current?.abort();
    const controller = new AbortController();
    abortRef.current = controller;

    setIsLoading(true);
    setError(null);
    setContent('');
    setDisplayTarget('');

    axios
      .post(
        route('logs.services.read', { server: server.id }),
        { key: selectedKey, lines: Number(lines), search: debouncedSearch || null },
        { signal: controller.signal },
      )
      .then((response) => {
        if (controller.signal.aborted) return;
        setContent(response.data.content ?? '');
        setDisplayTarget(response.data.display_target ?? '');
      })
      .catch((err: unknown) => {
        if (axios.isCancel(err)) return;
        if (axios.isAxiosError(err)) {
          const msg = err.response?.data?.message || err.response?.data?.error || err.message;
          setError(msg || 'Failed to read log');
        } else {
          setError('Failed to read log');
        }
      })
      .finally(() => {
        if (!controller.signal.aborted) {
          setIsLoading(false);
        }
      });
  }, [selectedKey, lines, debouncedSearch, server.id]);

  useEffect(() => {
    fetchLog();
    return () => abortRef.current?.abort();
  }, [fetchLog]);

  const downloadLog = useCallback(async () => {
    if (!selectedKey || isDownloading) return;
    setIsDownloading(true);
    try {
      const response = await axios.get(route('logs.services.download', { server: server.id, key: selectedKey }), {
        responseType: 'blob',
      });

      const disposition = response.headers['content-disposition'] as string | undefined;
      const match = disposition?.match(/filename="?([^";]+)"?/);
      const filename = match?.[1] ?? `${selectedKey}.log`;

      const url = URL.createObjectURL(response.data as Blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = filename;
      document.body.appendChild(link);
      link.click();
      link.remove();
      URL.revokeObjectURL(url);
    } catch (err) {
      if (axios.isAxiosError(err)) {
        let msg = err.message;
        const data = err.response?.data;
        if (data instanceof Blob) {
          const text = await data.text();
          try {
            const parsed = JSON.parse(text);
            msg = parsed.message || parsed.error || msg;
          } catch {
            msg = text || msg;
          }
        } else if (data?.message || data?.error) {
          msg = data.message || data.error;
        }
        setError(msg || 'Download failed');
      } else {
        setError('Download failed');
      }
    } finally {
      setIsDownloading(false);
    }
  }, [selectedKey, isDownloading, server.id]);

  const comboItems = useMemo(
    () =>
      catalogue.map((c) => ({
        value: c.key,
        label: `[${c.service_label}] ${c.label} (${c.display_target})`,
      })),
    [catalogue],
  );

  return (
    <ServerLayout>
      <Head title={`Service logs - ${server.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Service logs" description="View, search, refresh and download log files from installed services." />
          <a href="https://vitodeploy.com/docs/servers/logs" target="_blank" rel="noreferrer">
            <Button variant="outline">
              <BookOpenIcon />
              <span className="hidden lg:block">Docs</span>
            </Button>
          </a>
        </HeaderContainer>

        {catalogue.length === 0 ? (
          <Card>
            <CardContent className="text-muted-foreground p-10 text-center text-sm">
              No services with logs are installed on this server yet.{' '}
              <Link href={route('services', { server: server.id })} className="text-foreground underline underline-offset-4">
                Manage services
              </Link>
              .
            </CardContent>
          </Card>
        ) : (
          <Card className="overflow-hidden">
            <CardHeader className="gap-3">
              <div className="flex w-full items-center gap-2">
                <CogIcon className="text-muted-foreground size-4 shrink-0" aria-hidden="true" />
                <div className="flex flex-1">
                  <Combobox
                    items={comboItems}
                    value={selectedKey}
                    placeholder="Select a service log to view its contents..."
                    searchText="Search logs..."
                    noneFoundText="No matching log"
                    onValueChange={setSelectedKey}
                  />
                </div>
              </div>
              <div className="flex flex-wrap items-center justify-end gap-2">
                <Input
                  placeholder="Search log contents..."
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                  className="min-w-0 flex-1 md:max-w-xs"
                  disabled={!selectedKey}
                  aria-label="Search log contents"
                />
                <Select value={lines} onValueChange={(v) => setLines(v as LineOption)}>
                  <SelectTrigger className="w-32" aria-label="Line count">
                    <SelectValue placeholder="Lines" />
                  </SelectTrigger>
                  <SelectContent>
                    {LINE_OPTIONS.map((n) => (
                      <SelectItem key={n} value={n}>
                        {n} lines
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                <Button variant="outline" size="icon" onClick={fetchLog} disabled={!selectedKey || isLoading} title="Refresh" aria-label="Refresh">
                  {isLoading ? <LoaderCircleIcon className="animate-spin" /> : <RefreshCwIcon />}
                </Button>
                <Button
                  variant="outline"
                  size="icon"
                  onClick={downloadLog}
                  disabled={!selectedKey || isDownloading}
                  title="Download"
                  aria-label="Download log"
                >
                  {isDownloading ? <LoaderCircleIcon className="animate-spin" /> : <DownloadIcon />}
                </Button>
                {selected && selected.source === 'file' ? (
                  <ClearButton key={selected.key} serverId={server.id} logKey={selected.key} target={selected.display_target} onCleared={fetchLog} />
                ) : (
                  <Button variant="outline" size="icon" disabled title="Journal logs cannot be cleared" aria-label="Clear log">
                    <EraserIcon />
                  </Button>
                )}
              </div>
            </CardHeader>

            <CardContent className="p-0">
              <div className="bg-muted/40 text-muted-foreground border-b px-4 py-2 font-mono text-xs">
                {displayTarget || (selectedKey ? ' ' : 'No log selected')}
              </div>
              <LogViewer isLoading={isLoading} error={error} hasSelection={!!selectedKey} content={content} searchActive={!!debouncedSearch} />
            </CardContent>
          </Card>
        )}
      </Container>
    </ServerLayout>
  );
}

function LogViewer({
  isLoading,
  error,
  hasSelection,
  content,
  searchActive,
}: {
  isLoading: boolean;
  error: string | null;
  hasSelection: boolean;
  content: string;
  searchActive: boolean;
}) {
  const showCentered = !hasSelection || (hasSelection && isLoading) || (hasSelection && !error && content === '');

  if (showCentered || (hasSelection && error)) {
    return (
      <div className="bg-accent/30 text-accent-foreground flex h-[60vh] min-h-[400px] w-full items-center justify-center">
        {!hasSelection && <span className="text-muted-foreground text-sm">Pick a log from the dropdown above to view its contents.</span>}
        {hasSelection && isLoading && (
          <div className="text-muted-foreground flex items-center gap-2 text-sm">
            <LoaderCircleIcon className="size-4 animate-spin" />
            <span>Loading log…</span>
          </div>
        )}
        {hasSelection && !isLoading && error && <span className="text-destructive text-sm">Error: {error}</span>}
        {hasSelection && !isLoading && !error && content === '' && (
          <span className="text-muted-foreground text-sm">{searchActive ? 'No matches found.' : 'Log is empty.'}</span>
        )}
      </div>
    );
  }

  return (
    <ScrollArea className="bg-accent/30 text-accent-foreground h-[60vh] min-h-[400px] w-full">
      <div className="p-4 font-mono text-sm whitespace-pre-wrap">{content}</div>
      <ScrollBar orientation="vertical" />
      <ScrollBar orientation="horizontal" />
    </ScrollArea>
  );
}

function ClearButton({ serverId, logKey, target, onCleared }: { serverId: number; logKey: string; target: string; onCleared: () => void }) {
  const [open, setOpen] = useState(false);
  const form = useForm({ key: logKey });

  const submit = () => {
    form.post(route('logs.services.clear', { server: serverId }), {
      preserveScroll: true,
      onSuccess: () => {
        setOpen(false);
        onCleared();
      },
    });
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button variant="outline" size="icon" title="Clear log" aria-label="Clear log">
          <EraserIcon />
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Clear log</DialogTitle>
          <DialogDescription className="sr-only">Clear log file contents</DialogDescription>
        </DialogHeader>
        <div className="space-y-2 p-4">
          <p>
            Are you sure you want to clear <strong className="font-mono">{target}</strong>?
          </p>
          <p className="text-muted-foreground text-sm">
            This truncates the file contents but preserves the file itself, its ownership and permissions.
          </p>
        </div>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button disabled={form.processing} onClick={submit}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            <FormSuccessful successful={form.recentlySuccessful} />
            Clear
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
