import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import App from './App.tsx';
import './index.css';

// Function to initialize the app in WordPress admin or standalone
function init(container: HTMLElement) {
  createRoot(container).render(
    <StrictMode>
      <App />
    </StrictMode>
  );
}

// Auto-initialize if running in development mode or standalone
if (import.meta.env.DEV || document.getElementById('webhook-buttons-settings-root')) {
  const rootElement = document.getElementById('webhook-buttons-settings-root');
  if (rootElement) {
    init(rootElement);
  }
}

// Export for WordPress integration
export { init };

// Make the init function available globally for WordPress
if (typeof window !== 'undefined') {
  // @ts-expect-error - Adding to window object
  window.WebhookButtonsSettingsUI = { init };
}
