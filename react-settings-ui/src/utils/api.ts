import { SettingsData } from '../types';

// Define interface for WordPress window globals
interface WordPressWindow extends Window {
  webhookButtonsSettings?: {
    wp_rest_nonce: string;
    api_url: string;
    api_namespace: string;
  };
}

// Type for API response
export interface ApiResponse {
  success: boolean;
  settings?: SettingsData;
  message?: string;
}

// Get settings from WordPress global variable
const wpWindow = window as unknown as WordPressWindow;
const NONCE = wpWindow.webhookButtonsSettings?.wp_rest_nonce || '';
const API_URL = wpWindow.webhookButtonsSettings?.api_url || '';
const API_NAMESPACE = wpWindow.webhookButtonsSettings?.api_namespace || 'webhook-buttons/v1';
const UPDATE_ENDPOINT = '/update-settings';
const FETCH_ENDPOINT = '/fetch-settings';

/**
 * Sends settings data to the WordPress REST API endpoint
 * @param data The settings data to send
 * @returns A promise that resolves when the request is complete
 */
export const sendSettingsToEndpoint = async (data: SettingsData): Promise<ApiResponse> => {
  // Get the WordPress REST API URL from webhookButtonsSettings
  // If we're in WordPress admin, use the api_url global variable
  // Otherwise, use a default URL for development
  const apiUrl = typeof window !== 'undefined' && API_URL 
    ? `${API_URL}${API_NAMESPACE}${UPDATE_ENDPOINT}`
    : `/wp-json/${API_NAMESPACE}${UPDATE_ENDPOINT}`;
  
  try {
    // Get the nonce from WordPress if available
    const nonce = typeof window !== 'undefined' && NONCE 
      ? NONCE 
      : '';
    
    const response = await fetch(apiUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': nonce,
      },
      body: JSON.stringify(data),
      credentials: 'same-origin', // Include cookies for WordPress authentication
    });
    
    if (!response.ok) {
      throw new Error(`API request failed with status ${response.status}`);
    }

    const result = await response.json() as ApiResponse;

    return result;
  } catch (error: unknown) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    console.error('Error sending settings to endpoint:', errorMessage);
    throw error;
  }
};

/**
 * Fetches settings data from the WordPress REST API endpoint
 * @returns A promise that resolves with the settings data
 */
export const fetchSettings = async (): Promise<ApiResponse> => {
  // Construct the proper WordPress REST API URL
  // The API_URL from WordPress already includes the /wp-json/ part
  const apiUrl = typeof window !== 'undefined' && API_URL 
    ? `${API_URL}${API_NAMESPACE}${FETCH_ENDPOINT}`
    : `/wp-json/${API_NAMESPACE}${FETCH_ENDPOINT}`;
  try {
    // Get the nonce from WordPress if available
    const nonce = typeof window !== 'undefined' && NONCE 
      ? NONCE 
      : '';
    
    const response = await fetch(apiUrl, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': nonce,
      },
      credentials: 'same-origin', // Include cookies for WordPress authentication
    });
    
    if (!response.ok) {
      throw new Error(`API request failed with status ${response.status}`);
    }
    
    const result = await response.json() as ApiResponse;
    
    return result;
  } catch (error: unknown) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    console.error('Error fetching settings:', errorMessage);
    throw error;
  }
};

/** POST JSON to one of this plugin's REST routes. */
export const postJson = async (endpoint: string, body: unknown) => {
  const apiUrl = API_URL ? `${API_URL}${API_NAMESPACE}${endpoint}` : `/wp-json/${API_NAMESPACE}${endpoint}`;
  const response = await fetch(apiUrl, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': NONCE },
    credentials: 'same-origin',
    body: JSON.stringify(body),
  });
  if (!response.ok) throw new Error(`API request failed with status ${response.status}`);
  return response.json();
};
