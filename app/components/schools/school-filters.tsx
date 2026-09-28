import * as React from 'react';
import { Search } from 'lucide-react';
import { LoadingButton } from '@/components/ui/loading-button';
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';

interface SchoolFiltersProps {
  indexUrl: string;
  search: string;
  union: string;
  includeInactive: boolean;
  perPage: number;
  unions: string[];
}

const PER_PAGE_OPTIONS = [10, 15, 25, 50, 100];

export function SchoolFilters({
  indexUrl,
  search,
  union,
  includeInactive,
  perPage,
  unions,
}: SchoolFiltersProps) {
  const [searchValue, setSearchValue] = React.useState(search);
  const [unionValue, setUnionValue] = React.useState(union);
  const [inactive, setInactive] = React.useState(includeInactive);
  const [perPageValue, setPerPageValue] = React.useState(perPage);
  const [isNavigating, setIsNavigating] = React.useState(false);

  const navigate = React.useCallback(
    (overrides: {
      search?: string;
      union?: string;
      includeInactive?: boolean;
      perPage?: number;
    } = {}) => {
      const params = new URLSearchParams();
      const nextSearch = overrides.search ?? searchValue;
      const nextUnion = overrides.union ?? unionValue;
      const nextInactive = overrides.includeInactive ?? inactive;
      const nextPerPage = overrides.perPage ?? perPageValue;

      if (nextSearch) {
        params.set('search', nextSearch);
      }
      if (nextUnion) {
        params.set('union', nextUnion);
      }
      if (nextInactive) {
        params.set('include_inactive', '1');
      }
      if (nextPerPage !== 15) {
        params.set('per_page', String(nextPerPage));
      }

      const query = params.toString();
      setIsNavigating(true);
      window.location.href = indexUrl + (query ? `?${query}` : '');
    },
    [indexUrl, searchValue, unionValue, inactive, perPageValue]
  );

  const hasActiveFilters =
    searchValue !== '' || unionValue !== '' || inactive || perPage !== 15;

  return (
    <form
      method="get"
      action={indexUrl}
      onSubmit={(event) => {
        event.preventDefault();
        navigate({ search: searchValue });
      }}
      className="mt-8 flex flex-wrap items-center gap-3"
    >
      <div className="relative min-w-0 flex-1">
        <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
        <Input
          name="search"
          aria-label="Search schools"
          maxLength={120}
          value={searchValue}
          onChange={(event) => setSearchValue(event.target.value)}
          placeholder="Search code, name, or EMIS"
          className="pl-9"
        />
      </div>

      <Select
        value={unionValue || '__all__'}
        onValueChange={(value) => {
          const next = value === '__all__' ? '' : value;
          setUnionValue(next);
          navigate({ union: next });
        }}
      >
        <SelectTrigger className="w-auto min-w-44" aria-label="Filter by Union">
          <SelectValue placeholder="All Unions" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="__all__">All Unions</SelectItem>
          {unions.map((option) => (
            <SelectItem key={option} value={option}>
              {option}
            </SelectItem>
          ))}
        </SelectContent>
      </Select>

      <Select
        value={String(perPageValue)}
        onValueChange={(value) => {
          const next = Number(value);
          setPerPageValue(next);
          navigate({ perPage: next });
        }}
      >
        <SelectTrigger className="w-auto min-w-28" aria-label="Rows per page">
          <SelectValue />
        </SelectTrigger>
        <SelectContent>
          {PER_PAGE_OPTIONS.map((option) => (
            <SelectItem key={option} value={String(option)}>
              {option} / page
            </SelectItem>
          ))}
        </SelectContent>
      </Select>

      <label className="flex h-9 items-center gap-2 rounded-md border border-border bg-card px-3 text-sm font-medium text-foreground">
        <Switch
          checked={inactive}
          onCheckedChange={(checked) => {
            setInactive(checked);
            navigate({ includeInactive: checked });
          }}
          aria-label="Include inactive schools"
        />
        Include inactive
      </label>

      <LoadingButton type="submit" isLoading={isNavigating}>Search</LoadingButton>

      {hasActiveFilters && (
        <a
          href={indexUrl}
          className="self-center px-2 py-3 text-sm font-semibold text-muted-foreground hover:underline"
        >
          Clear
        </a>
      )}
    </form>
  );
}
