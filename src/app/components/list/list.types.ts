// list.types.ts

export type FieldType = 'text' | 'number' | 'date' | 'boolean' | 'currency' | 'percentage' | 'email' | 'phone' | 'custom';
export type ActionColor = 'primary' | 'secondary' | 'success' | 'danger' | 'warning' | 'info';
export type ViewMode = 'card' | 'table';

export type FieldFormatter = (value: any, item: any) => string;
export type FieldColorizer = (value: any, item: any) => string;
export type FieldIconizer = (value: any, item: any) => string;

export interface ListField {
  key: string;
  label: string;
  type?: FieldType;
  formatter?: FieldFormatter;
  color?: FieldColorizer;
  icon?: FieldIconizer;
  visible?: boolean | ((item: any) => boolean);
  width?: string;
  sortable?: boolean;
  isTitleCard?: boolean;
  isSubtitleCard?: boolean;
}

export interface ListAction {
  label: string;
  action: (item?: any) => void | Promise<void>;
  icon?: string;
  color?: ActionColor;
  visible?: boolean | ((item?: any) => boolean);
  confirm?: boolean;
  loading?: boolean;
}

export interface CardDisplayConfig {
  imageField?: string;
  compact?: boolean;
}

export interface TableDisplayConfig {
  striped?: boolean;
  hover?: boolean;
  bordered?: boolean;
  resizable?: boolean;
  reorderable?: boolean;
  stickyHeader?: boolean;
  columnFilters?: boolean;
}

export interface ListConfig {
  fields?: ListField[];
  display: {
    title: string;
    subtitle?: string;
  };
  actions?: ListAction[];
  itemActions?: ListAction[];
  card?: CardDisplayConfig;
  table?: TableDisplayConfig;
  searchable?: boolean;
  exportable?: boolean;
  defaultView?: ViewMode;
}
