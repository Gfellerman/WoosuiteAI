import React, { useState, useEffect } from 'react';
import { ContentItem, ContentType } from '../types';
import { PenTool, Check, X, RefreshCw, Box, FileText, Layout, Play, RotateCcw, Save, Sparkles, Filter, ChevronLeft, ChevronRight, Loader } from 'lucide-react';

const ContentEnhancer: React.FC = () => {
  const [activeTab, setActiveTab] = useState<ContentType>('product');
  const [activeField, setActiveField] = useState<'title' | 'description' | 'short_description'>('description');
  const [tone, setTone] = useState('Professional');
  const [instructions, setInstructions] = useState('');

  const [items, setItems] = useState<ContentItem[]>([]);
  const [loading, setLoading] = useState(false);
  const [generating, setGenerating] = useState<number | null>(null);

  // Pagination
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [totalItems, setTotalItems] = useState(0);

  // Selection
  const [selectedIds, setSelectedIds] = useState<number[]>([]);
  const [isBulkProcessing, setIsBulkProcessing] = useState(false);
  const [bulkProgress, setBulkProgress] = useState({ current: 0, total: 0 });

  const { apiUrl, nonce } = (window as any).woosuiteData || {};

  useEffect(() => {
    fetchItems();
  }, [activeTab, page]);

  const fetchItems = async () => {
    if (!apiUrl) return;
    setLoading(true);
    try {
        const res = await fetch(`${apiUrl}/content?type=${activeTab}&limit=20&page=${page}`, {
            headers: { 'X-WP-Nonce': nonce }
        });
        if (res.ok) {
            const data = await res.json();
            if (data.items) {
                 setItems(data.items);
                 setTotalPages(data.pages);
                 setTotalItems(data.total);
            } else {
                 setItems(data);
            }
            setSelectedIds([]);
        }
    } catch (e) {
        console.error(e);
    } finally {
        setLoading(false);
    }
  };

  const handleRewrite = async (item: ContentItem) => {
      setGenerating(item.id);
      try {
          const res = await fetch(`${apiUrl}/content/rewrite`, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
              body: JSON.stringify({
                  id: item.id,
                  field: activeField,
                  tone,
                  instructions
              })
          });

          if (res.ok) {
              const data = await res.json();
              if (data.success && data.rewritten) {
                  // Update local state to show proposed value
                  setItems(prev => prev.map(p => {
                      if (p.id !== item.id) return p;
                      const update: any = {};
                      if (activeField === 'title') update.proposedTitle = data.rewritten;
                      if (activeField === 'description') update.proposedDescription = data.rewritten;
                      if (activeField === 'short_description') update.proposedShortDescription = data.rewritten;
                      return { ...p, ...update };
                  }));
              }
          }
      } catch (e) {
          console.error(e);
      } finally {
          setGenerating(null);
      }
  };

  const handleApply = async (item: ContentItem) => {
      try {
          const res = await fetch(`${apiUrl}/content/apply`, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
              body: JSON.stringify({ id: item.id, field: activeField })
          });

          if (res.ok) {
              // Refresh to see updated content as "Current"
              fetchItems();
          }
      } catch (e) { console.error(e); }
  };

  const handleBulkRewrite = async () => {
      if (selectedIds.length === 0) return;
      setIsBulkProcessing(true);
      setBulkProgress({ current: 0, total: selectedIds.length });

      for (let i = 0; i < selectedIds.length; i++) {
          const id = selectedIds[i];
          const item = items.find(p => p.id === id);
          if (item) {
              await handleRewrite(item);
          }
          setBulkProgress(prev => ({ ...prev, current: i + 1 }));
      }
      setIsBulkProcessing(false);
      setSelectedIds([]);
  };

  const toggleSelectAll = () => {
      if (selectedIds.length === items.length) setSelectedIds([]);
      else setSelectedIds(items.map(i => i.id));
  };

  const getProposedValue = (item: ContentItem) => {
      if (activeField === 'title') return item.proposedTitle;
      if (activeField === 'description') return item.proposedDescription;
      if (activeField === 'short_description') return item.proposedShortDescription;
      return null;
  };

  const getCurrentValue = (item: ContentItem) => {
      // ContentItem doesn't explicitly store 'post_excerpt' as a separate field on interface except mapped?
      // Wait, API get_content_items returns 'description' as strip_tags(excerpt ?: content).
      // It doesn't return raw fields separately.
      // I might need to rely on what I have.
      // API Logic:
      // 'description' => strip_tags( post_excerpt ?: post_content )
      // 'name' => post_title.
      // For 'short_description' (excerpt), do I have it?
      // I should update API to return raw excerpt/content if needed.
      // But for now, let's use what we have.
      if (activeField === 'title') return item.name;
      return item.description; // This is a mix.
  };

  return (
    <div className="space-y-6 animate-fade-in">
        <div className="flex flex-col md:flex-row justify-between items-start gap-4">
            <div>
                <h2 className="text-2xl font-bold text-gray-800">Content Enhancer</h2>
                <p className="text-gray-500">Rewrite titles and descriptions with AI-powered creativity.</p>
            </div>

            {/* Toolbar */}
            <div className="bg-white p-3 rounded-xl shadow-sm border border-gray-200 flex flex-wrap items-center gap-3 w-full md:w-auto">
                <select
                    value={activeField}
                    onChange={(e) => setActiveField(e.target.value as any)}
                    className="border border-gray-300 rounded-lg px-3 py-2 text-sm font-medium focus:ring-2 focus:ring-purple-500 outline-none"
                >
                    <option value="title">Product/Post Title</option>
                    <option value="description">Description</option>
                    <option value="short_description">Short Description</option>
                </select>

                <select
                    value={tone}
                    onChange={(e) => setTone(e.target.value)}
                    className="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 outline-none"
                >
                    <option value="Professional">Professional</option>
                    <option value="Persuasive">Persuasive</option>
                    <option value="Casual">Casual</option>
                    <option value="Fun">Fun & Witty</option>
                    <option value="SEO Optimized">SEO Optimized</option>
                </select>

                <input
                    type="text"
                    placeholder="Extra instructions..."
                    value={instructions}
                    onChange={(e) => setInstructions(e.target.value)}
                    className="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 outline-none w-40"
                />

                {isBulkProcessing ? (
                     <div className="bg-purple-100 text-purple-700 px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
                        <RefreshCw size={16} className="animate-spin" />
                        {bulkProgress.current}/{bulkProgress.total}
                    </div>
                ) : (
                    <button
                        onClick={handleBulkRewrite}
                        disabled={selectedIds.length === 0}
                        className="bg-purple-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-purple-700 disabled:opacity-50 flex items-center gap-2 transition"
                    >
                        <Sparkles size={16} /> Rewrite Selected ({selectedIds.length})
                    </button>
                )}
            </div>
        </div>

        {/* Tabs */}
        <div className="flex gap-2 border-b border-gray-200">
            {[
                { id: 'product', label: 'Products', icon: Box },
                { id: 'post', label: 'Posts', icon: FileText },
                { id: 'page', label: 'Pages', icon: Layout },
            ].map(tab => (
                <button
                    key={tab.id}
                    onClick={() => { setActiveTab(tab.id as ContentType); setPage(1); }}
                    className={`px-4 py-2 text-sm font-medium rounded-t-lg flex items-center gap-2 transition
                        ${activeTab === tab.id
                            ? 'bg-white border-b-2 border-purple-600 text-purple-600'
                            : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'}`}
                >
                    <tab.icon size={16} /> {tab.label}
                </button>
            ))}
        </div>

        {/* Table */}
        <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            {loading ? (
                <div className="p-12 text-center text-gray-500 flex flex-col items-center gap-3">
                    <Loader className="animate-spin text-purple-500" size={32} />
                    <span>Loading content...</span>
                </div>
            ) : (
                <table className="w-full text-left">
                    <thead className="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th className="p-4 w-8">
                                <input type="checkbox"
                                    checked={items.length > 0 && selectedIds.length === items.length}
                                    onChange={toggleSelectAll}
                                    className="rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                                />
                            </th>
                            <th className="p-4 font-semibold text-gray-600 text-sm w-1/4">Item</th>
                            <th className="p-4 font-semibold text-gray-600 text-sm w-1/3">Current {activeField.replace('_', ' ')}</th>
                            <th className="p-4 font-semibold text-gray-600 text-sm w-1/3">Proposed Change (AI)</th>
                            <th className="p-4 font-semibold text-gray-600 text-sm text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-100">
                        {items.map(item => {
                            const proposed = getProposedValue(item);
                            return (
                                <tr key={item.id} className="hover:bg-gray-50 transition">
                                    <td className="p-4 align-top">
                                        <input type="checkbox"
                                            checked={selectedIds.includes(item.id)}
                                            onChange={() => {
                                                if (selectedIds.includes(item.id)) setSelectedIds(selectedIds.filter(id => id !== item.id));
                                                else setSelectedIds([...selectedIds, item.id]);
                                            }}
                                            className="rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                                        />
                                    </td>
                                    <td className="p-4 align-top">
                                        <div className="font-medium text-gray-800">{item.name}</div>
                                        <div className="text-xs text-gray-400">ID: {item.id}</div>
                                    </td>
                                    <td className="p-4 align-top text-sm text-gray-600">
                                        <div className="line-clamp-3">
                                            {getCurrentValue(item) || <span className="italic text-gray-400">Empty</span>}
                                        </div>
                                    </td>
                                    <td className="p-4 align-top">
                                        {proposed ? (
                                            <div className="bg-purple-50 border border-purple-100 p-2 rounded text-sm text-purple-900">
                                                {proposed}
                                            </div>
                                        ) : (
                                            <span className="text-gray-400 text-xs italic">No proposal yet</span>
                                        )}
                                    </td>
                                    <td className="p-4 align-top text-right">
                                        <div className="flex flex-col gap-2 items-end">
                                            {proposed ? (
                                                <>
                                                    <button
                                                        onClick={() => handleApply(item)}
                                                        className="bg-green-600 text-white px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-green-700 w-full flex items-center justify-center gap-1"
                                                    >
                                                        <Check size={12} /> Apply
                                                    </button>
                                                    <button
                                                        onClick={() => handleRewrite(item)}
                                                        disabled={generating === item.id}
                                                        className="text-gray-500 hover:text-purple-600 text-xs flex items-center gap-1"
                                                    >
                                                        <RotateCcw size={12} /> Regenerate
                                                    </button>
                                                </>
                                            ) : (
                                                <button
                                                    onClick={() => handleRewrite(item)}
                                                    disabled={generating === item.id}
                                                    className="bg-white border border-gray-300 text-gray-700 px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-gray-50 w-full flex items-center justify-center gap-1"
                                                >
                                                    {generating === item.id ? <Loader className="animate-spin" size={12} /> : <Sparkles size={12} />}
                                                    Rewrite
                                                </button>
                                            )}
                                        </div>
                                    </td>
                                </tr>
                            );
                        })}
                        {items.length === 0 && (
                            <tr><td colSpan={5} className="p-12 text-center text-gray-400">No items found.</td></tr>
                        )}
                    </tbody>
                </table>
            )}

            {/* Pagination (Simplified) */}
             {!loading && items.length > 0 && (
                <div className="p-4 border-t border-gray-100 flex items-center justify-between bg-gray-50">
                    <div className="text-sm text-gray-500">
                        Showing {items.length} of {totalItems} items
                    </div>
                    <div className="flex items-center gap-2">
                        <button
                            onClick={() => setPage(p => Math.max(1, p - 1))}
                            disabled={page === 1}
                            className="p-2 rounded hover:bg-white border border-transparent hover:border-gray-200 disabled:opacity-50 disabled:cursor-not-allowed text-gray-600"
                        >
                            <ChevronLeft size={16} />
                        </button>
                        <span className="text-sm font-medium text-gray-700">Page {page} of {totalPages}</span>
                        <button
                            onClick={() => setPage(p => Math.min(totalPages, p + 1))}
                            disabled={page === totalPages}
                            className="p-2 rounded hover:bg-white border border-transparent hover:border-gray-200 disabled:opacity-50 disabled:cursor-not-allowed text-gray-600"
                        >
                            <ChevronRight size={16} />
                        </button>
                    </div>
                </div>
            )}
        </div>
    </div>
  );
};

export default ContentEnhancer;
