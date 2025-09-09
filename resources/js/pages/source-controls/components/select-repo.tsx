import React, { useState, useEffect } from 'react';

import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { RefreshCw } from 'lucide-react';
import { Combobox } from '@/components/ui/combobox';

interface SelectRepoProps {
  sourceControlId: string;
  value: string;
  onValueChange: (value: string) => void;
  placeholder?: string;
}

export default function SelectRepo({ sourceControlId, value, onValueChange, placeholder = 'Enter repository' }: SelectRepoProps) {
  const [repos, setRepos] = useState<string[]>([]);
  const [gettingRepos, setGettingRepos] = useState(false);

  const refresh = async () => {
    fetchRepos(false);
  };

  const fetchRepos = async (useCache: boolean = true) => {
    setRepos([]);

    if (!sourceControlId) {
      return;
    }

    setGettingRepos(true);

    const routeName: string = useCache ? 'source-controls.repos' : 'source-controls.repos.nocache';

    try {
      const response = await fetch(route(routeName, { source_control: sourceControlId }));
      const data = await response.json();
      setRepos(data);

      if (data.length > 0 && !data.includes(value)) {
        onValueChange('');
      }
    } catch (error) {
      console.error('Failed to fetch repos:', error);
      setRepos([]);
    } finally {
      setGettingRepos(false);
    }
  };

  useEffect(() => {
    fetchRepos();
  }, [sourceControlId]);

  const comboboxItems = repos.map((repo) => ({
    value: repo,
    label: repo,
  }));

  return (
    <div className="flex items-center gap-2">
      {gettingRepos && <Input id="repository" type="text" value="" disabled={true} placeholder="Fetching..." />}
      {!gettingRepos && (repos.length === 0 || !sourceControlId) && (
        <Input id="repository" type="text" value={value ?? ''} onChange={(e) => onValueChange(e.target.value)} placeholder={placeholder} />
      )}
      {!gettingRepos && repos.length !== 0 && sourceControlId && (
        <Combobox
          items={comboboxItems}
          value={value}
          searchText="Filter repositories..."
          noneFoundText="No repositories found..."
          onValueChange={onValueChange}
        />
      )}
      <Button variant="outline" type="button" disabled={gettingRepos || !sourceControlId} onClick={refresh}>
        <RefreshCw className={gettingRepos ? 'animate-spin' : ''} />
      </Button>
    </div>
  );
}
