export type OptionSource =
    'suppliers' | 'manufacturers' | 'locations' | 'departments' | 'categories' | 'asset-models' | 'components';

/** Endpoint of a master-data source served by OptionSearchController. */
export function optionsUrl(source: OptionSource): string {
    return `/api/options/${source}`;
}
