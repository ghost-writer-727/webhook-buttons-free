import React, { useState, Suspense } from 'react';
import { Trash2, GripVertical, Send, ChevronDown, ChevronRight } from 'lucide-react';
import { Button as ButtonType } from '../types';
import { ProButtonFields, SampleModal } from '../pro';
import { showSample } from '../utils/license';

interface ButtonProps {
  group: ButtonType;
  index: number;
  onUpdate: (index: number, group: ButtonType) => void;
  onDelete: (index: number) => void;
  onDragStart: (e: React.DragEvent, index: number) => void;
  onDragOver: (e: React.DragEvent) => void;
  onDrop: (e: React.DragEvent, dropIndex: number) => void;
  isDragging: boolean;
}

export const Button: React.FC<ButtonProps> = ({
  group,
  index,
  onUpdate,
  onDelete,
  onDragStart,
  onDragOver,
  onDrop,
  isDragging
}) => {
  // New buttons (no label yet) open expanded
  const [isExpanded, setIsExpanded] = useState(() => !group.title);
  const [showSampleModal, setShowSampleModal] = useState(false);

  const change = (patch: Partial<ButtonType>) => onUpdate(index, { ...group, ...patch });

  return (
    <>
      <div
        className={`bg-[#f0f0f1] border border-gray-200 rounded-md overflow-hidden transition-all duration-200 ${isDragging ? 'opacity-50 scale-98' : ''}`}
        draggable
        onDragStart={(e) => onDragStart(e, index)}
        onDragOver={onDragOver}
        onDrop={(e) => onDrop(e, index)}
      >
        <div className="p-4 border-b border-gray-200 flex items-center gap-2">
          <div
            className="cursor-grab active:cursor-grabbing p-1 hover:bg-[#dcdcde] rounded"
            draggable
            onDragStart={(e) => onDragStart(e, index)}
          >
            <GripVertical size={16} />
          </div>

          <button
            type="button"
            onClick={() => setIsExpanded(!isExpanded)}
            className="p-1 hover:bg-[#dcdcde] rounded text-gray-500"
          >
            {isExpanded ? <ChevronDown size={16} /> : <ChevronRight size={16} />}
          </button>

          <input
            type="text"
            value={group.title}
            onChange={(e) => change({ title: e.target.value })}
            className="flex-grow px-3 py-1.5 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#2271b1] focus:border-[#2271b1]"
            placeholder={`Button ${index + 1}`}
          />

          <div className="flex items-center gap-2">
            {showSample && SampleModal && (
              <button
                type="button"
                onClick={() => setShowSampleModal(true)}
                className="p-1 text-gray-500 hover:text-[#2271b1] hover:bg-blue-50 transition-colors"
                title="Sample data"
              >
                <Send size={16} />
              </button>
            )}
            <button
              type="button"
              onClick={() => onDelete(index)}
              title="Delete button"
              aria-label="Delete button"
              className="p-1.5 bg-red-100 text-red-600 rounded-md hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-red-500"
            >
              <Trash2 size={16} />
            </button>
          </div>
        </div>

        {isExpanded && (
          <div className="p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-[#1d2327] mb-1">When clicked</label>
              <select
                value={group.selectOption}
                onChange={(e) => change({ selectOption: e.target.value as ButtonType['selectOption'] })}
                className="w-full px-3 py-2 border border-gray-300 shadow-sm focus:outline-none focus:ring-2 focus:ring-[#2271b1] focus:border-[#2271b1] transition-colors bg-white"
              >
                <option value="Confirmation">Ask to confirm first</option>
                <option value="Just Send">Send right away</option>
              </select>
            </div>

            <div>
              <label className="block text-sm font-medium text-[#1d2327] mb-1">Webhook URL</label>
              <input
                type="text"
                value={group.url}
                onChange={(e) => change({ url: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 shadow-sm focus:outline-none focus:ring-2 focus:ring-[#2271b1] focus:border-[#2271b1] transition-colors"
                placeholder="https://"
              />
              {/^http:\/\//i.test(group.url) && !/^http:\/\/(localhost|127\.0\.0\.1)([:/]|$)/i.test(group.url) && (
                <p className="text-sm text-[#996800] mt-1" role="alert">
                  This URL is not encrypted. Order data (names, emails, phones) will travel as plain text. Use https:// if you can.
                </p>
              )}
            </div>

            <div className="mt-2">
              <label className="block text-sm font-medium text-[#1d2327] mb-1">Confirmation message</label>
              <textarea
                value={group.message}
                onChange={(e) => change({ message: e.target.value })}
                rows={3}
                className={`w-full px-3 py-2 border border-gray-300 shadow-sm focus:outline-none focus:ring-2 focus:ring-[#2271b1] focus:border-[#2271b1] transition-colors resize-none ${group.selectOption === 'Just Send' ? 'bg-gray-100 cursor-not-allowed' : ''}`}
                placeholder="Message shown before sending"
                disabled={group.selectOption === 'Just Send'}
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-[#1d2327] mb-4">Options</label>
              <label className="inline-flex items-center cursor-pointer">
                <input
                  type="checkbox"
                  checked={group.enabled}
                  onChange={(e) => change({ enabled: e.target.checked })}
                  className="form-checkbox h-4 w-4 text-[#2271b1] focus:ring-[#2271b1] border-gray-300 rounded"
                />
                <span className="ml-2 text-sm text-gray-700">Enable this button</span>
              </label>
            </div>

            {ProButtonFields && (
              <Suspense fallback={null}>
                <ProButtonFields button={group} onChange={change} />
              </Suspense>
            )}
          </div>
        )}
      </div>

      {showSampleModal && SampleModal && (
        <Suspense fallback={null}>
          <SampleModal
            isOpen={showSampleModal}
            onClose={() => setShowSampleModal(false)}
            button={group}
            buttonTitle={group.title || `Button ${index + 1}`}
          />
        </Suspense>
      )}
    </>
  );
};
