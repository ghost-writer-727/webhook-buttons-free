/**
 * Stand-in for @wordpress/i18n when the page runs outside WordPress (`npm run dev`).
 * The production build never includes this file; vite.config.ts maps the import to `wp.i18n`.
 */
export const __ = (text: string, _domain: string) => text;
export const _n = (single: string, plural: string, number: number, _domain: string) => (number === 1 ? single : plural);
export const sprintf = (format: string, ...args: (string | number)[]) => {
  let i = 0;
  return format.replace(/%[sd]/g, () => String(args[i++] ?? ''));
};
