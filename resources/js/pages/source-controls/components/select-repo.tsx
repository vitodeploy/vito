import React, { useState, useEffect } from 'react';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Input } from '@/components/ui/input';

interface SelectRepoProps {
  sourceControlId: string;
  value: string;
  onValueChange: (value: string) => void;
  placeholder?: string;
}

export default function SelectRepo({ sourceControlId, value, onValueChange, placeholder = 'Enter repository' }: SelectRepoProps) {
  const [repos, setRepos] = useState<string[]>([]);
  const [gettingRepos, setGettingRepos] = useState(false);

  useEffect(() => {
    const fetchRepos = async () => {
      setRepos([]);

      if (!sourceControlId) {
        return;
      }

      setGettingRepos(true);

      try {
        const response = await fetch(route('source-controls.repos', { source_control: sourceControlId }));
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

    fetchRepos();
  }, [sourceControlId]);

  if (gettingRepos) {
    return <Input id="repository" type="text" value="" disabled={true} placeholder="Fetching..." />;
  }

  if (repos.length === 0 || !sourceControlId) {
    return <Input id="repository" type="text" value={value ?? ''} onChange={(e) => onValueChange(e.target.value)} placeholder={placeholder} />;
  }

  return (
    <Select value={value} onValueChange={onValueChange}>
      <SelectTrigger id="repository">
        <SelectValue placeholder="Select a repository" />
      </SelectTrigger>
      <SelectContent searchable>
        <SelectGroup>
          {repos.map((repo) => (
            <SelectItem key={`${repo}`} value={repo ?? ''}>
              {repo}
            </SelectItem>
          ))}
        </SelectGroup>
      </SelectContent>
    </Select>
  );
}
