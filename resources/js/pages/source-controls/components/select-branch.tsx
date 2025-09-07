import React, { useState, useEffect } from 'react';
import { Input } from '@/components/ui/input';
import { Combobox } from '@/components/ui/combobox';
import { Button } from '@/components/ui/button';
import { RefreshCw } from 'lucide-react';

interface SelectBranchProps {
  sourceControlId: string;
  repository: string;
  value: string;
  onValueChange: (value: string) => void;
  placeholder?: string;
}

export default function SelectBranch({ sourceControlId, repository, value, onValueChange, placeholder = 'Enter branch' }: SelectBranchProps) {
  const [branches, setBranches] = useState<string[]>([]);
  const [gettingBranches, setGettingBranches] = useState(false);

  const refresh = async () => {
    fetchBranches(false);
  };

  const fetchBranches = async (useCache: boolean = true) => {
    setBranches([]);

    if (!sourceControlId || !repository) {
      return;
    }

    setGettingBranches(true);

    const routeName: string = useCache ? 'source-controls.branches' : 'source-controls.branches.nocache';

    try {
      const response = await fetch(
        route(routeName, {
          source_control: sourceControlId,
          repo: repository,
        }),
      );
      const data = await response.json();
      setBranches(data);

      if (data.length === 1) {
        onValueChange(data[0]);
      } else if (data.length > 0 && !data.includes(value)) {
        onValueChange('');
      }
    } catch (error) {
      console.error('Failed to fetch branches:', error);
      setBranches([]);
    } finally {
      setGettingBranches(false);
    }
  };

  useEffect(() => {
    fetchBranches();
  }, [sourceControlId, repository]);

  const comboboxItems = branches.map((branch) => ({
    value: branch,
    label: branch,
  }));

  return (
    <div className="flex items-center gap-2">
      {gettingBranches && <Input id="branch" type="text" value="" disabled={true} placeholder="Fetching..." />}
      {!gettingBranches && (branches.length === 0 || !sourceControlId || !repository) && (
        <Input id="branch" type="text" value={value ?? ''} onChange={(e) => onValueChange(e.target.value)} placeholder={placeholder} />
      )}
      {!gettingBranches && branches.length !== 0 && sourceControlId && repository && (
        <Combobox
          items={comboboxItems}
          value={value}
          searchText="Filter branches..."
          noneFoundText="No branches found..."
          onValueChange={onValueChange}
        />
      )}
      <Button variant="outline" type="button" disabled={gettingBranches || !sourceControlId || !repository} onClick={refresh}>
        <RefreshCw className={gettingBranches ? 'animate-spin' : ''} />
      </Button>
    </div>
  );
}
