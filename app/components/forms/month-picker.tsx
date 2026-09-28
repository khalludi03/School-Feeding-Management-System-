import * as React from 'react';
import { cn } from '@/lib/utils';
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';

interface Props {
  name: string;
  defaultValue?: string; // YYYY-MM format
  error?: string;
  id?: string;
}

const MONTHS = [
  { value: '01', en: 'January', bn: 'জানুয়ারি' },
  { value: '02', en: 'February', bn: 'ফেব্রুয়ারি' },
  { value: '03', en: 'March', bn: 'মার্চ' },
  { value: '04', en: 'April', bn: 'এপ্রিল' },
  { value: '05', en: 'May', bn: 'মে' },
  { value: '06', en: 'June', bn: 'জুন' },
  { value: '07', en: 'July', bn: 'জুলাই' },
  { value: '08', en: 'August', bn: 'আগস্ট' },
  { value: '09', en: 'September', bn: 'সেপ্টেম্বর' },
  { value: '10', en: 'October', bn: 'অক্টোবর' },
  { value: '11', en: 'November', bn: 'নভেম্বর' },
  { value: '12', en: 'December', bn: 'ডিসেম্বর' },
];

export function MonthPicker({ name, defaultValue, error, id = 'month-select' }: Props) {
  const defaultYear = defaultValue ? defaultValue.split('-')[0] : '';
  const defaultMonth = defaultValue ? defaultValue.split('-')[1] : '';

  const [year, setYear] = React.useState(defaultYear);
  const [month, setMonth] = React.useState(defaultMonth);

  const currentYear = new Date().getFullYear();
  const years = Array.from({ length: 10 }, (_, i) => (currentYear - 5 + i).toString());

  const value = year && month ? `${year}-${month}` : '';

  return (
    <div className="flex flex-col space-y-2">
      <Label htmlFor={id} className={error ? 'text-destructive' : ''}>Month</Label>
      <div className="flex gap-2">
        <Select value={month} onValueChange={setMonth}>
          <SelectTrigger 
            className={cn('flex-1 h-11', error && 'border-destructive focus:ring-destructive')}
            aria-describedby={error ? `${id}-error` : undefined}
          >
            <SelectValue placeholder="Select month..." />
          </SelectTrigger>
          <SelectContent>
            {MONTHS.map((m) => (
              <SelectItem key={m.value} value={m.value}>
                <div className="flex justify-between gap-4 w-full">
                    <span>{m.en}</span>
                    <span className="text-muted-foreground text-xs" lang="bn">{m.bn}</span>
                </div>
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
        <Select value={year} onValueChange={setYear}>
          <SelectTrigger 
            className={cn('w-[120px] h-11', error && 'border-destructive focus:ring-destructive')}
          >
            <SelectValue placeholder="Year" />
          </SelectTrigger>
          <SelectContent>
            {years.map((y) => (
              <SelectItem key={y} value={y}>{y}</SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>
      <input type="hidden" name={name} id={id} value={value} required />
      {error && (
        <p id={`${id}-error`} className="text-[0.8rem] font-medium text-destructive">
          {error}
        </p>
      )}
    </div>
  );
}
