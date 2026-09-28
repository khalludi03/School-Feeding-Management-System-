import * as React from 'react';
import { Check, ChevronsUpDown } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import {
  Command,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
} from '@/components/ui/command';
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover';
import { Label } from '@/components/ui/label';

interface School {
  id: number;
  code: string;
  bangla_name: string;
  emis_code: string | null;
}

interface Props {
  name: string;
  schools: School[];
  defaultValue?: string;
  error?: string;
  id?: string;
}

export function SchoolCombobox({ name, schools, defaultValue, error, id = 'school-select' }: Props) {
  const [open, setOpen] = React.useState(false);
  const [value, setValue] = React.useState(defaultValue || '');

  const customFilter = (val: string, search: string) => {
    if (!search) return 1;
    const s = schools.find((x) => x.id.toString() === val);
    if (!s) return 0;
    const term = search.toLowerCase();
    if (s.code.toLowerCase().includes(term)) return 1;
    if (s.bangla_name.includes(term)) return 1;
    if (s.emis_code && s.emis_code.toLowerCase().includes(term)) return 1;
    return 0;
  };

  const selectedSchool = schools.find((s) => s.id.toString() === value);

  return (
    <div className="flex flex-col space-y-2">
      <Label htmlFor={id} className={error ? 'text-destructive' : ''}>School</Label>
      <Popover open={open} onOpenChange={setOpen}>
        <PopoverTrigger asChild>
          <Button
            variant="outline"
            role="combobox"
            aria-expanded={open}
            aria-describedby={error ? `${id}-error` : undefined}
            className={cn(
              'w-full justify-between h-11 px-3 font-normal',
              !value && 'text-muted-foreground',
              error && 'border-destructive focus-visible:ring-destructive'
            )}
          >
            <span className="truncate">
                {selectedSchool ? `${selectedSchool.code} - ${selectedSchool.bangla_name}` : 'Select a school...'}
            </span>
            <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
          </Button>
        </PopoverTrigger>
        <PopoverContent className="w-full p-0 sm:w-[400px]" align="start">
          <Command filter={customFilter}>
            <CommandInput placeholder="Search code, EMIS or name..." />
            <CommandList>
              <CommandEmpty>No school found.</CommandEmpty>
              <CommandGroup>
                {schools.map((school) => (
                  <CommandItem
                    key={school.id}
                    value={school.id.toString()}
                    onSelect={(currentValue) => {
                      setValue(currentValue === value ? '' : currentValue);
                      setOpen(false);
                    }}
                  >
                    <Check
                      className={cn(
                        'mr-2 h-4 w-4',
                        value === school.id.toString() ? 'opacity-100' : 'opacity-0'
                      )}
                    />
                    <div className="flex flex-col">
                        <span className="font-semibold">{school.code}</span>
                        <span className="text-muted-foreground text-sm" lang="bn">{school.bangla_name}</span>
                    </div>
                  </CommandItem>
                ))}
              </CommandGroup>
            </CommandList>
          </Command>
        </PopoverContent>
      </Popover>
      <input type="hidden" name={name} id={id} value={value} required />
      {error && (
        <p id={`${id}-error`} className="text-[0.8rem] font-medium text-destructive">
          {error}
        </p>
      )}
    </div>
  );
}
