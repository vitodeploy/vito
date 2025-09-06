import React, { useState, useEffect } from 'react';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Input } from '@/components/ui/input';

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

  useEffect(() => {
    const fetchBranches = async () => {
      setBranches([]);

      if (!sourceControlId || !repository) {
        return;
      }

      setGettingBranches(true);

      try {
        const response = await fetch(
          route('source-controls.branches', {
            source_control: sourceControlId,
            repo: repository,
          }),
        );
        const data = await response.json();
        setBranches(data);

        if (data.length > 0 && !data.includes(value)) {
          onValueChange('');
        }
      } catch (error) {
        console.error('Failed to fetch branches:', error);
        setBranches([]);
      } finally {
        setGettingBranches(false);
      }
    };

    fetchBranches();
  }, [sourceControlId, repository]);

  if (gettingBranches) {
    return <Input id="branch" type="text" value="" disabled={true} placeholder="Fetching..." />;
  }

  if (branches.length === 0 || !sourceControlId || !repository) {
    return <Input id="branch" type="text" value={value ?? ''} onChange={(e) => onValueChange(e.target.value)} placeholder={placeholder} />;
  }

  return (
    <Select value={value} onValueChange={onValueChange}>
      <SelectTrigger id="branch">
        <SelectValue placeholder="Select a branch" />
      </SelectTrigger>
      <SelectContent searchable>
        <SelectGroup>
          {branches.map((branch) => (
            <SelectItem key={`${branch}`} value={branch ?? ''}>
              {branch}
            </SelectItem>
          ))}
        </SelectGroup>
      </SelectContent>
    </Select>
  );
}
