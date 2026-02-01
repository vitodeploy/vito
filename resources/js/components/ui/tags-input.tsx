'use client';

import * as React from 'react';
import { X } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

interface TagsInputProps {
  value?: string[];
  onValueChange?: (tags: string[]) => void;
  placeholder?: string;
  className?: string;
  disabled?: boolean;
  maxTags?: number;
  allowDuplicates?: boolean;
  separator?: string | RegExp;
}

export function TagsInput({
  value = [],
  onValueChange,
  placeholder = 'Add tags...',
  className,
  disabled = false,
  maxTags,
  allowDuplicates = false,
  separator = ',',
  ...props
}: TagsInputProps & React.InputHTMLAttributes<HTMLInputElement>) {
  const [inputValue, setInputValue] = React.useState('');
  const [tags, setTags] = React.useState<string[]>(value);
  const inputRef = React.useRef<HTMLInputElement>(null);

  React.useEffect(() => {
    setTags(value);
  }, [value]);

  const addTag = (tag: string) => {
    const trimmedTag = tag.trim();
    if (!trimmedTag) return;

    if (!allowDuplicates && tags.includes(trimmedTag)) return;
    if (maxTags && tags.length >= maxTags) return;

    const newTags = [...tags, trimmedTag];
    setTags(newTags);
    onValueChange?.(newTags);
    setInputValue('');
  };

  const removeTag = (indexToRemove: number) => {
    const newTags = tags.filter((_, index) => index !== indexToRemove);
    setTags(newTags);
    onValueChange?.(newTags);
  };

  const handleInputKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      addTag(inputValue);
    } else if (e.key === 'Tab' && inputValue.trim()) {
      e.preventDefault();
      addTag(inputValue);
    } else if (e.key === 'Backspace' && !inputValue && tags.length > 0) {
      removeTag(tags.length - 1);
    }
  };

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const newValue = e.target.value;

    if (typeof separator === 'string' && newValue.includes(separator)) {
      const newTags = newValue.split(separator);
      const lastTag = newTags.pop() || '';

      newTags.forEach((tag) => addTag(tag));
      setInputValue(lastTag);
    } else if (separator instanceof RegExp && separator.test(newValue)) {
      const newTags = newValue.split(separator);
      const lastTag = newTags.pop() || '';

      newTags.forEach((tag) => addTag(tag));
      setInputValue(lastTag);
    } else {
      setInputValue(newValue);
    }
  };

  const handleContainerClick = () => {
    inputRef.current?.focus();
  };

  return (
    <div className={cn('gap-2 space-y-2', disabled && 'cursor-not-allowed opacity-50', className)} onClick={handleContainerClick}>
      <Input
        ref={inputRef}
        value={inputValue}
        onChange={handleInputChange}
        onKeyDown={handleInputKeyDown}
        placeholder={tags.length === 0 ? placeholder : ''}
        disabled={disabled || (maxTags ? tags.length >= maxTags : false)}
        {...props}
      />
      {tags.map((tag, index) => (
        <Badge key={index} variant="outline" className="mr-1 gap-2">
          {tag}
          {!disabled && (
            <Button
              variant="ghost"
              size="sm"
              className="text-muted-foreground hover:text-foreground h-auto p-0!"
              onClick={(e) => {
                e.stopPropagation();
                removeTag(index);
              }}
            >
              <X className="h-3 w-3" />
            </Button>
          )}
        </Badge>
      ))}
    </div>
  );
}
