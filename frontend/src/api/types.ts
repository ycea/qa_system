export type Paginated<T> = {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
};

export const PER_PAGE_OPTIONS = [30, 50, 100] as const;

export type PerPage = (typeof PER_PAGE_OPTIONS)[number];
