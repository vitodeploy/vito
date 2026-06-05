import { useEffect, useMemo, useState, FormEvent } from 'react';
import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { CircleCheckIcon, CircleIcon, LoaderCircleIcon, SearchIcon, TrashIcon, TriangleAlertIcon } from 'lucide-react';
import { Site } from '@/types/site';
import { ToolingDescriptor } from '@/types';
import { SiteToolingStatus } from '@/types/site-tooling';

type Props = {
  site: Site;
  tools: ToolingDescriptor[];
  installedVersions: Record<string, string | null>;
  statuses: Record<string, SiteToolingStatus>;
  requiredTooling: Record<string, string>;
  siblingCount: number;
  onSubmit?: () => void;
};

export default function ToolingTable({ site, tools, installedVersions, statuses, requiredTooling, siblingCount, onSubmit }: Props) {
  const [query, setQuery] = useState('');

  const filtered = useMemo(() => {
    const q = query.trim().toLowerCase();
    if (q === '') return tools;
    return tools.filter((t) => t.label.toLowerCase().includes(q) || t.description.toLowerCase().includes(q));
  }, [tools, query]);

  return (
    <div className="space-y-3">
      <div className="relative max-w-xs">
        <SearchIcon className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
        <Input
          type="search"
          value={query}
          onChange={(e) => setQuery(e.target.value)}
          placeholder="Search tools…"
          aria-label="Search tools"
          className="pl-9"
        />
      </div>

      <div className="relative overflow-hidden rounded-md border shadow-xs">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead className="w-[180px]">Tool</TableHead>
              <TableHead>Description</TableHead>
              <TableHead className="w-[180px]">Version</TableHead>
              <TableHead className="w-[180px] text-right">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {filtered.length === 0 ? (
              <TableRow>
                <TableCell colSpan={4} className="text-muted-foreground py-6 text-center">
                  No tools match "{query}".
                </TableCell>
              </TableRow>
            ) : (
              filtered.map((tool) => (
                <ToolingRow
                  key={tool.id}
                  site={site}
                  tool={tool}
                  installedVersion={installedVersions[tool.id] ?? null}
                  status={statuses[tool.id] ?? null}
                  requiredBy={requiredTooling[tool.id] ?? null}
                  siblingCount={siblingCount}
                  onSubmit={onSubmit}
                />
              ))
            )}
          </TableBody>
        </Table>
      </div>
    </div>
  );
}

function ToolingRow({
  site,
  tool,
  installedVersion,
  status,
  requiredBy,
  siblingCount,
  onSubmit,
}: {
  site: Site;
  tool: ToolingDescriptor;
  installedVersion: string | null;
  status: SiteToolingStatus;
  requiredBy: string | null;
  siblingCount: number;
  onSubmit?: () => void;
}) {
  const [selected, setSelected] = useState<string>(installedVersion ?? tool.supported_versions[0] ?? '');
  const [submittingInstall, setSubmittingInstall] = useState(false);
  const [submittingUninstall, setSubmittingUninstall] = useState(false);
  const [confirmOpen, setConfirmOpen] = useState(false);
  const submitting = submittingInstall || submittingUninstall;

  useEffect(() => {
    setSelected(installedVersion ?? tool.supported_versions[0] ?? '');
  }, [installedVersion, tool.supported_versions]);

  const installing = status === 'installing';
  const uninstalling = status === 'uninstalling';
  const busy = installing || uninstalling;
  const installFailed = status === 'install_failed';
  const uninstallFailed = status === 'uninstall_failed';

  const installed = installedVersion !== null;
  const required = requiredBy !== null;
  const primaryDisabled = submitting || busy || selected === '' || selected === installedVersion;
  const uninstallDisabled = submitting || busy || !installed || required;

  const submitInstall = (e: FormEvent) => {
    e.preventDefault();
    if (primaryDisabled) return;
    setSubmittingInstall(true);
    onSubmit?.();
    router.post(
      route('site-tooling.install', { server: site.server_id, site: site.id, tool: tool.id }),
      { version: selected },
      {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => setSubmittingInstall(false),
      },
    );
  };

  const submitUninstall = () => {
    if (uninstallDisabled) return;
    setSubmittingUninstall(true);
    setConfirmOpen(false);
    onSubmit?.();
    router.delete(route('site-tooling.uninstall', { server: site.server_id, site: site.id, tool: tool.id }), {
      preserveScroll: true,
      preserveState: true,
      onFinish: () => setSubmittingUninstall(false),
    });
  };

  return (
    <TableRow>
      <TableCell className="align-middle font-medium">
        <span className="inline-flex items-center gap-2">
          {tool.label}
          {installed ? (
            <CircleCheckIcon className="text-success size-4" aria-label={`${tool.label} is installed`} />
          ) : (
            <CircleIcon className="text-muted-foreground/40 size-4" aria-label={`${tool.label} is not installed`} />
          )}
          {(installFailed || uninstallFailed) && (
            <TriangleAlertIcon
              className="text-destructive size-4"
              aria-label={installFailed ? `${tool.label} install failed` : `${tool.label} uninstall failed`}
            />
          )}
        </span>
      </TableCell>
      <TableCell className="text-muted-foreground py-3 whitespace-normal">{tool.description}</TableCell>
      <TableCell className="align-middle">
        <Select value={selected} onValueChange={setSelected} disabled={busy || submitting}>
          <SelectTrigger id={`${tool.id}-version`} className="w-[160px]">
            <SelectValue placeholder="Select version" />
          </SelectTrigger>
          <SelectContent>
            <SelectGroup>
              {tool.supported_versions.map((v) => (
                <SelectItem key={v} value={v}>
                  {`${tool.label} ${v}`}
                </SelectItem>
              ))}
            </SelectGroup>
          </SelectContent>
        </Select>
      </TableCell>
      <TableCell className="align-middle">
        <form onSubmit={submitInstall} className="flex items-center justify-end gap-2">
          <Button
            type="submit"
            size="sm"
            disabled={primaryDisabled}
            aria-busy={installing}
            aria-label={installing ? `Installing ${tool.label}` : undefined}
          >
            {installing || submittingInstall ? <LoaderCircleIcon className="size-4 animate-spin" /> : 'Install'}
          </Button>
          {required ? (
            <Tooltip>
              <TooltipTrigger asChild>
                <span tabIndex={0}>
                  <Button type="button" variant="outline" size="icon" disabled aria-label={`Uninstall ${tool.label}`}>
                    <TrashIcon className="text-destructive size-4" />
                  </Button>
                </span>
              </TooltipTrigger>
              <TooltipContent>Required by {requiredBy} site type</TooltipContent>
            </Tooltip>
          ) : (
            <Button
              type="button"
              variant="outline"
              size="icon"
              disabled={uninstallDisabled}
              onClick={() => setConfirmOpen(true)}
              aria-busy={uninstalling}
              aria-label={uninstalling ? `Uninstalling ${tool.label}` : `Uninstall ${tool.label}`}
            >
              {uninstalling || submittingUninstall ? (
                <LoaderCircleIcon className="text-destructive size-4 animate-spin" />
              ) : (
                <TrashIcon className="text-destructive size-4" />
              )}
            </Button>
          )}
        </form>

        <Dialog open={confirmOpen} onOpenChange={setConfirmOpen}>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Uninstall {tool.label}</DialogTitle>
              <DialogDescription>This will remove {tool.label} from the isolated user.</DialogDescription>
            </DialogHeader>
            <div className="space-y-2 p-4 text-sm">
              {siblingCount > 0 && (
                <p>
                  This isolated user is shared with <strong>{siblingCount}</strong> other site{siblingCount === 1 ? '' : 's'}. Uninstalling will
                  affect them all.
                </p>
              )}
              <p>Are you sure you want to uninstall {tool.label}?</p>
            </div>
            <DialogFooter className="gap-2">
              <DialogClose asChild>
                <Button variant="outline">Cancel</Button>
              </DialogClose>
              <Button variant="destructive" disabled={uninstallDisabled} onClick={submitUninstall}>
                {(uninstalling || submitting) && <LoaderCircleIcon className="size-4 animate-spin" />}
                Uninstall
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      </TableCell>
    </TableRow>
  );
}
