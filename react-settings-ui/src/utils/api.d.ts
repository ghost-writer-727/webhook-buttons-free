import { SettingsData, AdvancedSettings } from '../types';

export interface WordPressWindow extends Window {
  wp_ajax_url?: string;
  wp_rest_nonce?: string;
}

export interface ApiResponse {
  success: boolean;
  data: {
    metaBoxes?: SettingsData['metaBoxes'];
    advanced?: AdvancedSettings;
    message?: string;
  };
}

export interface SettingsPayload {
  metaBoxes?: SettingsData['metaBoxes'];
  advanced?: AdvancedSettings;
}

export function sendSettingsToEndpoint(data: SettingsPayload): Promise<ApiResponse>;
export function fetchSettings(): Promise<ApiResponse>;
