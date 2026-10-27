/**
 * The settings bundle does not ship @wordpress/i18n. Vite marks it external and maps it to the
 * `wp.i18n` global that WordPress loads first (the script is enqueued with the 'wp-i18n' dependency).
 * These are the only members the page uses.
 */
declare module '@wordpress/i18n' {
  export function __(text: string, domain: string): string;
  export function _n(single: string, plural: string, number: number, domain: string): string;
  export function sprintf(format: string, ...args: (string | number)[]): string;
}
