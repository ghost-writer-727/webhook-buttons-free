import React from 'react';
import { __ } from '@wordpress/i18n';
import { Send, Settings2 } from 'lucide-react';

interface NavigationProps {
  activeTab: 'main' | 'advanced';
  onTabChange: (tab: 'main' | 'advanced') => void;
}

export const Navigation: React.FC<NavigationProps> = ({ activeTab, onTabChange }) => {
  return (
    <div className="bg-white border-b border-gray-200 shadow-sm">
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav className="flex space-x-8">
          <button
            onClick={() => onTabChange('main')}
            className={`py-4 px-1 border-b-2 font-medium text-sm transition-colors ${
              activeTab === 'main'
                ? 'border-[#2271b1] text-[#2271b1]'
                : 'border-transparent text-gray-500 hover:text-[#1d2327] hover:border-gray-300'
            }`}
          >
            <div className="flex items-center gap-2">
              <Send size={18} />
              {__('Buttons', 'webhook-buttons')}
            </div>
          </button>
          <button
            onClick={() => onTabChange('advanced')}
            className={`py-4 px-1 border-b-2 font-medium text-sm transition-colors ${
              activeTab === 'advanced'
                ? 'border-[#2271b1] text-[#2271b1]'
                : 'border-transparent text-gray-500 hover:text-[#1d2327] hover:border-gray-300'
            }`}
          >
            <div className="flex items-center gap-2">
              <Settings2 size={18} />
              {__('Advanced settings', 'webhook-buttons')}
            </div>
          </button>
        </nav>
      </div>
    </div>
  );
};