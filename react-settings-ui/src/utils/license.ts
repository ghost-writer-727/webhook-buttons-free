/**
 * What this store may do, as the server reports it (see includes/classes/License.php).
 * Everything in the Lite build works without limits; Pro is extra code that the Lite build does not have.
 */
import { __ } from '@wordpress/i18n';
import { hasPro } from '../pro';

interface LicenseInfo {
  channel: 'freemius' | 'marketplace' | 'lite';
  pro: boolean;
  has_pro: boolean;
  upgrade_url: string | null;
}

const fromServer = (window as unknown as { webhookButtonsSettings?: { license?: LicenseInfo } }).webhookButtonsSettings?.license;

export const license: LicenseInfo = fromServer ?? { channel: 'freemius', pro: false, has_pro: hasPro, upgrade_url: null };

/** Sample sends need the Pro panel code and an active license. */
export const showSample = license.pro && hasPro;

export const proNotice = license.pro
  ? null
  : hasPro
    ? __('Pro options are off because there is no active license. Your saved Pro settings are kept.', 'webhook-buttons')
    : __('This is the free version. Pro adds custom fields, flat data, staff notes, user access control, sample sends and auth headers.', 'webhook-buttons');
