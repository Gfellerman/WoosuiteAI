import React, { useState, useEffect } from 'react';
import { ContentItem, ContentType } from '../types';
import { generateSeoMeta, generateImageSeo } from '../services/geminiService';
import { Sparkles, Check, AlertCircle, RefreshCw, Bot, FileText, Image as ImageIcon, Box, Layout, Settings, ExternalLink, ChevronLeft, ChevronRight } from 'lucide-react';

const SeoManager: React.FC = () => {
  const [activeTab, setActiveTab] = useState<ContentType>('product');
  const [items, setItems] = useState<ContentItem[]>([]);
  const [loading, setLoading] = useState(false);
  const [generating, setGenerating] = useState<number | null>(null);

  // Pagination
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [totalItems, setTotalItems] = useState(0);

  // Bulk Optimization
  const [selectedIds, setSelectedIds] = useState<number[]>([]);
  const [selectAllServer, setSelectAllServer] = useState(false);
  const [isBulkOptimizing, setIsBulkOptimizing] = useState(false);
  const [bulkProgress, setBulkProgress] = useState({ current: 0, total: 0 });

  // Sitemap
  const [showSitemapModal, setShowSitemapModal] = useState(false);
  // Default to true as per plan, but in real app fetch from settings
  const [sitemapEnabled, setSitemapEnabled] = useState(true);

  const { apiUrl, nonce, homeUrl } = window.woosuiteData || {};

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

  const handleTabChange = (tab: ContentType) => {
      if (tab === activeTab) return;
      setActiveTab(tab);
      setPage(1);
  };

  const handleGenerate = async (item: ContentItem) => {
    setGenerating(item.id);
    try {
      let result;
      // Image Handling
      if (item.type === 'image' && item.imageUrl) {
          result = await generateImageSeo(item.imageUrl, item.name);
      } else {
          // Content Handling
          result = await generateSeoMeta(item);
      }

      // Save to Backend
      await saveItem(item, result);

      // Update Local State
      const updates = mapResultToItem(result, item.type);
      setItems(prev => prev.map(p => p.id === item.id ? { ...p, ...updates } : p));
    } catch (e) {
      console.error(e);
    } finally {
      setGenerating(null);
    }
  };

  const saveItem = async (item: ContentItem, data: any) => {
      // Map data to API params
      const payload: any = {};

      if (item.type === 'image') {
          if (data.altText) payload.altText = data.altText;
          if (data.title) payload.title = data.title;
      } else {
          if (data.title) payload.metaTitle = data.title;
          if (data.description) payload.metaDescription = data.description;
          if (data.llmSummary) payload.llmSummary = data.llmSummary;
      }

      await fetch(`${apiUrl}/content/${item.id}`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
          body: JSON.stringify(payload)
      });
  };

  const mapResultToItem = (result: any, type: ContentType) => {
      if (type === 'image') {
          return { altText: result.altText, name: result.title }; // Note: name maps to post_title
      }
      return { metaTitle: result.title, metaDescription: result.description, llmSummary: result.llmSummary };
  };

  // Bulk Logic
  const handleBulkOptimize = async () => {
      if (selectedIds.length === 0) return;
      setIsBulkOptimizing(true);
      setBulkProgress({ current: 0, total: selectedIds.length });

      for (let i = 0; i < selectedIds.length; i++) {
          const id = selectedIds[i];
          const item = items.find(p => p.id === id);
          if (item) {
              await handleGenerate(item);
          }
          setBulkProgress(prev => ({ ...prev, current: i + 1 }));
      }

      setIsBulkOptimizing(false);
      setSelectedIds([]); // Clear selection
  };

  const handleBulkOptimizeAll = async () => {
      setIsBulkOptimizing(true);
      setBulkProgress({ current: 0, total: totalItems });

      let processedCount = 0;

      // Iterate all pages
      for (let p = 1; p <= totalPages; p++) {
          try {
              // Fetch page p
              const res = await fetch(`${apiUrl}/content?type=${activeTab}&limit=20&page=${p}`, {
                    headers: { 'X-WP-Nonce': nonce }
              });
              if (!res.ok) continue;

              const data = await res.json();
              // Handle both response structures (array or object with items)
              const pageItems = data.items || data;

              if (Array.isArray(pageItems)) {
                  for (const item of pageItems) {
                      await handleGenerate(item);
                      processedCount++;
                      setBulkProgress(prev => ({ ...prev, current: processedCount }));
                  }
              }
          } catch (e) {
              console.error(`Failed to process page ${p}`, e);
          }
      }

      setIsBulkOptimizing(false);
      setSelectAllServer(false);
      setSelectedIds([]);
      fetchItems(); // Refresh current view
  };

  const toggleSelectAll = () => {
      if (selectAllServer) {
          setSelectAllServer(false);
          setSelectedIds([]);
      } else if (selectedIds.length === items.length) {
          setSelectedIds([]);
      } else {
          setSelectedIds(items.map(i => i.id));
      }
  };

  const handleSelectAllServer = () => {
      setSelectAllServer(true);
      setSelectedIds(items.map(i => i.id)); // Visual consistency
  };

  return (
    <div className="space-y-6">
      {/* Header & Controls */}
      <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h2 className="text-2xl font-bold text-gray-800">AI SEO Manager</h2>
            <p className="text-gray-500">Optimize for Traditional Search (Google, Bing) and AI Search (ChatGPT, Gemini).</p>
        </div>
        <div className="flex gap-2">
            <button
                onClick={() => setShowSitemapModal(true)}
                className="bg-white border border-gray-300 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition flex items-center gap-2">
                <Settings size={16} /> Sitemap Settings
            </button>
            <a
                href={`${homeUrl}/llms.txt`}
                target="_blank"
                rel="noopener noreferrer"
                className="bg-white border border-gray-300 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition flex items-center gap-2 text-gray-700"
            >
                <FileText size={16} /> View llms.txt
            </a>
            {isBulkOptimizing ? (
                 <div className="bg-purple-100 text-purple-700 px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
                     <RefreshCw size={16} className="animate-spin" />
                     Processing {bulkProgress.current}/{bulkProgress.total}
                 </div>
            ) : (
                <button
                    onClick={selectAllServer ? handleBulkOptimizeAll : handleBulkOptimize}
                    disabled={selectedIds.length === 0 && !selectAllServer}
                    className={`px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm flex items-center gap-2
                        ${(selectedIds.length === 0 && !selectAllServer) ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-purple-600 text-white hover:bg-purple-700'}`}
                >
                    <Sparkles size={16} /> Bulk Optimize ({selectAllServer ? totalItems : selectedIds.length})
                </button>
            )}
        </div>
      </div>

      {/* Tabs */}
      <div className="flex gap-2 border-b border-gray-200 pb-1">
          {[
              { id: 'product', label: 'Products', icon: Box },
              { id: 'post', label: 'Posts', icon: FileText },
              { id: 'page', label: 'Pages', icon: Layout },
              { id: 'image', label: 'Images', icon: ImageIcon }
          ].map(tab => (
              <button
                key={tab.id}
                onClick={() => handleTabChange(tab.id as ContentType)}
                className={`px-4 py-2 text-sm font-medium rounded-t-lg flex items-center gap-2 transition
                    ${activeTab === tab.id
                        ? 'bg-white border-b-2 border-purple-600 text-purple-600'
                        : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'}`}
              >
                  <tab.icon size={16} /> {tab.label}
              </button>
          ))}
      </div>

      {/* Content Table */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        {loading ? (
            <div className="p-8 text-center text-gray-500">Loading content...</div>
        ) : (
        <table className="w-full text-left">
          <thead className="bg-gray-50 border-b border-gray-100">
            {selectedIds.length === items.length && items.length > 0 && !selectAllServer && totalItems > items.length && (
              <tr>
                <td colSpan={5} className="p-2 bg-purple-50 text-center border-b border-purple-100">
                    <span className="text-sm text-purple-800">
                        All {items.length} items on this page are selected.
                        <button onClick={handleSelectAllServer} className="ml-2 font-bold underline hover:text-purple-900">
                            Select all {totalItems} items in {activeTab}
                        </button>
                    </span>
                </td>
              </tr>
            )}
            {selectAllServer && (
              <tr>
                <td colSpan={5} className="p-2 bg-purple-100 text-center border-b border-purple-200">
                    <span className="text-sm text-purple-900 font-medium">
                        All {totalItems} items are selected.
                        <button onClick={toggleSelectAll} className="ml-2 underline text-purple-700">Clear selection</button>
                    </span>
                </td>
              </tr>
            )}
            <tr>
              <th className="p-4 w-8">
                  <input type="checkbox"
                    checked={(items.length > 0 && selectedIds.length === items.length) || selectAllServer}
                    onChange={toggleSelectAll}
                    className="rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                  />
              </th>
              <th className="p-4 font-semibold text-gray-600 text-sm">Item</th>
              <th className="p-4 font-semibold text-gray-600 text-sm">SEO Status</th>
              <th className="p-4 font-semibold text-gray-600 text-sm">
                  {activeTab === 'image' ? 'AI Alt Text' : 'LLM Optimization (GEO)'}
              </th>
              <th className="p-4 font-semibold text-gray-600 text-sm text-right">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-100">
            {items.map((item) => (
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
                <td className="p-4 align-top w-1/4">
                  <div className="flex gap-3">
                      {activeTab === 'image' && item.imageUrl && (
                          <img src={item.imageUrl} alt={item.altText} className="w-12 h-12 rounded object-cover border border-gray-200" />
                      )}
                      <div>
                          <div className="font-medium text-gray-800">{item.name}</div>
                          <div className="text-xs text-gray-500 mt-1 line-clamp-1">{item.description || 'No description'}</div>
                      </div>
                  </div>
                </td>
                <td className="p-4 align-top w-1/6">
                  {/* Status Check Logic */}
                  {(() => {
                      const isOptimized = activeTab === 'image'
                        ? (item.altText && item.altText.length > 5)
                        : (item.metaDescription && item.metaDescription.length > 10);

                      return isOptimized ? (
                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                          <Check size={12} className="mr-1" /> Optimized
                        </span>
                      ) : (
                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                          <AlertCircle size={12} className="mr-1" /> Missing
                        </span>
                      );
                  })()}
                </td>
                <td className="p-4 align-top">
                   {activeTab === 'image' ? (
                       <div className="text-sm text-gray-700 italic">
                           {item.altText || <span className="text-gray-400">No Alt Text</span>}
                       </div>
                   ) : (
                       item.llmSummary ? (
                           <div className="space-y-1">
                               <div className="text-xs font-semibold text-gray-500 flex items-center gap-1">
                                   <Bot size={12} /> AI/LLM Summary:
                               </div>
                               <div className="text-sm text-gray-700 bg-gray-50 p-2 rounded border border-gray-100">
                                   {item.llmSummary}
                               </div>
                           </div>
                       ) : (
                           <div className="text-sm text-gray-400 italic">No AI-optimized data yet...</div>
                       )
                   )}
                </td>
                <td className="p-4 align-top text-right w-1/6">
                  <div className="flex flex-col gap-2 items-end">
                    <button
                        onClick={() => handleGenerate(item)}
                        disabled={generating === item.id || isBulkOptimizing}
                        className={`inline-flex items-center justify-center px-3 py-1.5 rounded-lg text-sm font-medium transition w-full
                            ${generating === item.id
                                ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                : 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100'
                            }`}
                    >
                        {generating === item.id ? (
                        <RefreshCw size={14} className="animate-spin mr-1.5" />
                        ) : (
                        <Sparkles size={14} className="mr-1.5" />
                        )}
                        Generate
                    </button>

                    {item.permalink && activeTab !== 'image' && (
                    <a
                        href={`https://metatags.io/?url=${encodeURIComponent(item.permalink)}`}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="inline-flex items-center justify-center px-3 py-1.5 rounded-lg text-sm font-medium text-gray-600 bg-gray-50 hover:bg-gray-100 hover:text-gray-900 transition w-full border border-gray-200"
                        title="Verify Meta Tags (Requires Public URL)"
                    >
                        <ExternalLink size={14} className="mr-1.5" /> Verify
                    </a>
                    )}

                    {activeTab === 'image' && item.permalink && (
                    <a
                        href={item.permalink}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="inline-flex items-center justify-center px-3 py-1.5 rounded-lg text-sm font-medium text-gray-600 bg-gray-50 hover:bg-gray-100 hover:text-gray-900 transition w-full border border-gray-200"
                    >
                        <ExternalLink size={14} className="mr-1.5" /> View
                    </a>
                    )}
                  </div>
                </td>
              </tr>
            ))}
            {items.length === 0 && (
                <tr>
                    <td colSpan={5} className="p-8 text-center text-gray-400">
                        No content found for this type.
                    </td>
                </tr>
            )}
          </tbody>
        </table>
        )}

        {/* Pagination Controls */}
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
                    <span className="text-sm font-medium text-gray-700">
                        Page {page} of {totalPages}
                    </span>
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

      {/* Sitemap Modal */}
      {showSitemapModal && (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
              <div className="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                  <h3 className="text-xl font-bold mb-4">Sitemap Settings</h3>
                  <div className="space-y-4">
                      <div className="flex items-center justify-between">
                          <span className="font-medium text-gray-700">Enable Custom Sitemap</span>
                          {/* Toggle Display - Since we defaulted to Enabled in backend, just showing Enabled */}
                          <div className="bg-green-100 text-green-800 px-3 py-1 rounded text-sm font-medium">Active</div>
                      </div>
                      <div className="bg-gray-50 p-3 rounded border border-gray-200 break-all text-sm text-gray-600 font-mono">
                          {homeUrl ? `${homeUrl}/sitemap.xml` : '/sitemap.xml'}
                      </div>
                      <p className="text-xs text-gray-500">
                          This sitemap includes all Products, Posts, Pages, and Images. It is automatically added to your robots.txt.
                      </p>
                  </div>
                  <div className="mt-6 flex justify-end">
                      <button
                        onClick={() => setShowSitemapModal(false)}
                        className="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">
                        Close
                      </button>
                  </div>
              </div>
          </div>
      )}

    </div>
  );
};

export default SeoManager;
