import React, { useState, useEffect } from 'react';
import { ContentItem, ContentType } from '../types';
import { PenTool, Check, X, RefreshCw, Box, FileText, Layout, Play, RotateCcw, Save, Sparkles, Filter, ChevronLeft, ChevronRight, Loader, Tag, List } from 'lucide-react';

const ContentEnhancer: React.FC = () => {
  const [activeTab, setActiveTab] = useState<ContentType>('product');
  const [activeField, setActiveField] = useState<'title' | 'description' | 'short_description'>('description');
  const [tone, setTone] = useState('Professional');
  const [instructions, setInstructions] = useState('');

  // Filters & Limits
  const [limit, setLimit] = useState(20);
  const [category, setCategory] = useState<string>('');
  const [status, setStatus] = useState<string>('all');
  const [categories, setCategories] = useState<any[]>([]);

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
  const [isBulkApplying, setIsBulkApplying] = useState(false);
  const [bulkProgress, setBulkProgress] = useState({ current: 0, total: 0 });

  const { apiUrl, nonce } = (window as any).woosuiteData || {};

  useEffect(() => {
    fetchCategories();
    setPage(1); // Reset page on tab change
    setCategory(''); // Reset category on tab change
  }, [activeTab]);

  useEffect(() => {
    // Clear items immediately to prevent UI flicker/confusion when filter changes
    setItems([]);
    fetchItems();
  }, [activeTab, page, limit, category, status]);

  const fetchCategories = async () => {
      if (!apiUrl) return;
      try {
          const res = await fetch(`${apiUrl}/content/categories?type=${activeTab}`, {
              headers: { 'X-WP-Nonce': nonce }
          });
          if (res.ok) {
              const data = await res.json();
              setCategories(data);
          }
      } catch (e) { console.error(e); }
  };

  const fetchItems = async () => {
    if (!apiUrl) return;
    setLoading(true);
    try {
        let url = `${apiUrl}/content?type=${activeTab}&limit=${limit}&page=${page}`;
        if (category) url += `&category=${category}`;
        if (status !== 'all') url += `&status=${status}`;

        const res = await fetch(url, {
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

  const handleRestore = async (item: ContentItem) => {
      if (!confirm("Revert this item to its previous state?")) return;
      try {
          const res = await fetch(`${apiUrl}/content/restore`, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
              body: JSON.stringify({ id: item.id, field: activeField })
          });
          if (res.ok) {
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

  const handleBulkApply = async () => {
      if (selectedIds.length === 0) return;
      setIsBulkApplying(true);
      try {
          const res = await fetch(`${apiUrl}/content/bulk-apply`, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
              body: JSON.stringify({ ids: selectedIds, field: activeField })
          });
          if (res.ok) {
              const data = await res.json();
              // alert(`Applied ${data.applied} changes.`);
              fetchItems();
          }
      } catch (e) { console.error(e); }
      setIsBulkApplying(false);
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
      if (activeField === 'title') return item.name;
      // Use explicit separate fields from new API
      if (activeField === 'short_description') return (item as any).shortDescription;
      return item.description;
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
                    <option value="Technical">Technical</option>
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

                {/* Status Filter */}
                <select
                    value={status}
                    onChange={(e) => { setStatus(e.target.value); setPage(1); }}
                    className="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 outline-none bg-gray-50 text-gray-700"
                >
                    <option value="all">All Status</option>
                    <option value="enhanced">Enhanced (Pending)</option>
                    <option value="not_enhanced">Not Enhanced</option>
                </select>

                {/* Category Filter */}
                {categories.length > 0 && (
                    <select
                        value={category}
                        onChange={(e) => { setCategory(e.target.value); setPage(1); }}
                        className="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 outline-none bg-gray-50 text-gray-700 max-w-[150px]"
                    >
                        <option value="">All Categories</option>
                        {categories.map(cat => (
                            <option key={cat.id} value={cat.id}>{cat.name} ({cat.count})</option>
                        ))}
                    </select>
                )}

                <div className="h-6 w-px bg-gray-300 mx-1"></div>

                {/* Bulk Actions */}
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
                        title="Generate proposals for selected items"
                    >
                        <Sparkles size={16} /> Rewrite ({selectedIds.length})
                    </button>
                )}

                {isBulkApplying ? (
                    <div className="bg-green-100 text-green-700 px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
                        <Loader size={16} className="animate-spin" /> Applying...
                    </div>
                ) : (
                    <button
                        onClick={handleBulkApply}
                        disabled={selectedIds.length === 0}
                        className="bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-green-700 disabled:opacity-50 flex items-center gap-2 transition"
                        title="Apply proposed changes to selected items"
                    >
                        <Check size={16} /> Apply ({selectedIds.length})
                    </button>
                )}
            </div>
        </div>

        {/* Tabs */}
        <div className="flex gap-2 border-b border-gray-200">
            {[
                { id: 'product', label: 'Products', icon: Box },
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
                                                        {generating === item.id ? <Loader className="animate-spin" size={12} /> : <RotateCcw size={12} />}
                                                        Regenerate
                                                    </button>
                                                </>
                                            ) : (
                                                <>
                                                    <button
                                                        onClick={() => handleRewrite(item)}
                                                        disabled={generating === item.id}
                                                        className="bg-white border border-gray-300 text-gray-700 px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-gray-50 w-full flex items-center justify-center gap-1"
                                                    >
                                                        {generating === item.id ? <Loader className="animate-spin" size={12} /> : <Sparkles size={12} />}
                                                        Rewrite
                                                    </button>

                                                    {item.hasHistory && (
                                                        <button
                                                            onClick={() => handleRestore(item)}
                                                            className="text-xs text-gray-500 hover:text-red-600 flex items-center justify-center gap-1 mt-1 w-full"
                                                            title="Revert to previous version"
                                                        >
                                                            <RotateCcw size={12} /> Undo
                                                        </button>
                                                    )}
                                                </>
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

            {/* Pagination */}
             {!loading && items.length > 0 && (
                <div className="p-4 border-t border-gray-100 flex items-center justify-between bg-gray-50">
                    <div className="flex items-center gap-4 text-sm text-gray-500">
                        <span>Showing {items.length} of {totalItems} items</span>
                        <select
                            value={limit}
                            onChange={(e) => { setLimit(Number(e.target.value)); setPage(1); }}
                            className="border border-gray-300 rounded px-2 py-1 text-xs focus:ring-1 focus:ring-purple-500 outline-none bg-white"
                        >
                            <option value={20}>20 per page</option>
                            <option value={50}>50 per page</option>
                            <option value={100}>100 per page</option>
                            <option value={500}>500 per page</option>
                        </select>
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
