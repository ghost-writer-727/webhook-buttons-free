import { useState, useEffect } from 'react';
import { SettingsUI } from './components/SettingsUI';
import { AdvancedSettings } from './components/AdvancedSettings';
import { Navigation } from './components/Navigation';

function App() {
  const [activeTab, setActiveTab] = useState<'main' | 'advanced'>('main');
  const [isWordPress, setIsWordPress] = useState<boolean>(false);
  
  // Detect if we're running in WordPress admin
  useEffect(() => {
    const isInWordPress = !!document.getElementById('wpcontent') || 
                         !!document.getElementById('wpbody') || 
                         !!document.getElementById('wpwrap');
    setIsWordPress(isInWordPress);
    
    // Only apply WordPress-specific styles if we're in WordPress
    if (isInWordPress) {
      // Hide all other elements in wpbody-content except our root
      const wpbodyContent = document.querySelector('#wpbody-content');
      if (wpbodyContent) {
        const children = Array.from(wpbodyContent.children) as HTMLElement[];
        for (const child of children) {
          // Skip our own container and essential WP elements
          if (child.id === 'webhook-buttons-admin-root' || 
              child.id === 'wpfooter' || 
              child.id === 'screen-meta' ||
              child.id === 'screen-meta-links') {
            continue;
          }
          child.style.display = 'none';
        }
      }
      
      // Add a class to the body for our custom styles
      document.body.classList.add('webhook-buttons-fullscreen-ui');
    }
    
    return () => {
      // Cleanup when component unmounts
      if (isInWordPress) {
        document.body.classList.remove('webhook-buttons-fullscreen-ui');
      }
    };
  }, []);
  
  // Use different classes based on environment
  const containerClass = isWordPress 
    ? "webhook-buttons-admin-ui-wrapper" // WordPress admin
    : "min-h-screen bg-[#f0f0f1]"; // Standalone
  
  // Apply inline styles for WordPress admin
  const wpStyles = isWordPress ? {
    minHeight: 'calc(100vh - 32px)', // Account for WP admin bar
    margin: '-10px -20px', // Offset WordPress padding
    padding: '20px',
    background: '#f0f0f1',
  } : {};

  return (
    <div className={containerClass} style={wpStyles}>
      <Navigation activeTab={activeTab} onTabChange={setActiveTab} />
      {activeTab === 'main' ? <SettingsUI /> : <AdvancedSettings />}
    </div>
  );
}

export default App;