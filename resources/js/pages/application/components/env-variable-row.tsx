import React, { useState } from 'react';
import { EyeIcon, EyeOffIcon, LockIcon, TrashIcon, UnlockIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { AutoGrowTextarea } from '@/components/ui/auto-grow-textarea';
import { EnvVariable } from '@/types/env';
import { cn } from '@/lib/utils';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';

interface EnvVariableRowProps {
  variable: EnvVariable;
  onChange: (variable: EnvVariable) => void;
  onDelete: () => void;
  error?: string;
}

export default function EnvVariableRow({ variable, onChange, onDelete, error }: EnvVariableRowProps) {
  const [showValue, setShowValue] = useState(false);
  const isMultiLine = variable.value.includes('\n');

  // Existing secrets (not new) cannot be shown - they weren't sent from server
  const isExistingSecret = variable.isSecret && !variable.isNew;

  const handleKeyChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    onChange({ ...variable, key: e.target.value });
  };

  const handleValueChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    onChange({ ...variable, value: e.target.value });
  };

  const toggleSecret = () => {
    // Only new variables can toggle their secret status
    if (variable.isNew) {
      onChange({ ...variable, isSecret: !variable.isSecret });
    }
  };

  const renderValueInput = () => {
    // Existing secret: show placeholder, user must enter new value to change
    if (isExistingSecret) {
      return (
        <Input type="password" value={variable.value} onChange={handleValueChange} placeholder="Enter new value to change..." className="flex-1" />
      );
    }

    // New secret with toggle visibility
    if (variable.isSecret && variable.isNew) {
      if (!showValue) {
        return (
          <div className="relative flex-1">
            <Input type="password" value={variable.value} onChange={handleValueChange} placeholder="Enter value..." className="pr-10" />
            <button
              type="button"
              className="text-muted-foreground hover:text-foreground absolute top-0 right-0 flex h-9 w-9 items-center justify-center"
              onClick={() => setShowValue(true)}
              aria-label="Show value"
            >
              <EyeIcon className="size-4" aria-hidden="true" />
            </button>
          </div>
        );
      }

      return (
        <div className="relative flex-1">
          <AutoGrowTextarea value={variable.value} onChange={handleValueChange} placeholder="Enter value..." className="pr-10" />
          <button
            type="button"
            className="text-muted-foreground hover:text-foreground absolute top-1 right-0 flex h-9 w-9 items-center justify-center"
            onClick={() => setShowValue(false)}
            aria-label="Hide value"
          >
            <EyeOffIcon className="size-4" aria-hidden="true" />
          </button>
        </div>
      );
    }

    // Non-secret value - use AutoGrowTextarea for multiline support
    return <AutoGrowTextarea value={variable.value} onChange={handleValueChange} placeholder="Enter value..." className="flex-1" />;
  };

  const renderSecretToggle = () => {
    // Only show toggle for new variables
    if (!variable.isNew) {
      return null;
    }

    return (
      <Tooltip>
        <TooltipTrigger asChild>
          <Button
            type="button"
            variant="ghost"
            size="icon"
            onClick={toggleSecret}
            className={cn('shrink-0', variable.isSecret ? 'text-warning' : 'text-muted-foreground')}
          >
            {variable.isSecret ? <LockIcon className="size-4" /> : <UnlockIcon className="size-4" />}
          </Button>
        </TooltipTrigger>
        <TooltipContent>{variable.isSecret ? 'Secret (click to make normal)' : 'Normal (click to make secret)'}</TooltipContent>
      </Tooltip>
    );
  };

  const renderKeyInput = () => {
    const isExisting = !variable.isNew;
    const hasError = !!error;

    if (isExisting && variable.isSecret) {
      return (
        <div className={cn('relative', isMultiLine ? 'w-full sm:w-72' : 'w-72')}>
          <Input value={variable.key} onChange={handleKeyChange} placeholder="KEY" className="pr-9 font-mono" disabled />
          <Tooltip>
            <TooltipTrigger asChild>
              <div className="text-muted-foreground absolute top-0 right-0 flex h-9 w-9 items-center justify-center">
                <LockIcon className="size-4" />
              </div>
            </TooltipTrigger>
            <TooltipContent>This is a secret variable</TooltipContent>
          </Tooltip>
        </div>
      );
    }

    return (
      <div className={cn(isMultiLine ? 'w-full sm:w-72' : 'w-72')}>
        <Input
          value={variable.key}
          onChange={handleKeyChange}
          placeholder="KEY"
          className={cn('font-mono', hasError && 'border-destructive')}
          disabled={isExisting}
          aria-invalid={hasError}
        />
        {hasError && <p className="text-destructive mt-1 text-xs">{error}</p>}
      </div>
    );
  };

  return (
    <div className={cn('flex items-start gap-2', isMultiLine && 'flex-col sm:flex-row')}>
      {renderKeyInput()}
      {renderValueInput()}
      {renderSecretToggle()}
      <Button type="button" variant="ghost" size="icon" onClick={onDelete} className="text-muted-foreground hover:text-destructive shrink-0">
        <TrashIcon className="size-4" />
      </Button>
    </div>
  );
}
