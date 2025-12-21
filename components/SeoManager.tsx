import React, { useState, useEffect } from 'react';
import { ContentItem, ContentType } from '../types';
import { Sparkles, Check, AlertCircle, RefreshCw, Bot, FileText, Image as ImageIcon, Box, Layout, Settings, ExternalLink, ChevronLeft, ChevronRight, Filter, X, Loader, Play, Ban, Trash2, RotateCw, RotateCcw, AlertTriangle, PieChart, Eye, Search } from 'lucide-react';

const SeoManager: React.FC = () => {
  const [activeTab, setActiveTab] = useState<ContentType>('product');
  const [items, setItems] = useState<ContentItem[]>([]);
  const [loading, setLoading] = useState(false);
  const [generating, setGenerating] = useState<number | null>(null);

  // Pagination
  const [page, setPage] = useState(1);
  const [limit, setLimit] = useState(20);
  const [totalPages, setTotalPages] = useState(1);
  const [totalItems, setTotalItems] = useState(0);

  // Filters
  const [showUnoptimized, setShowUnoptimized] = useState(false);

  // Bulk Optimization (Selection)
  const [selectedIds, setSelectedIds] = useState<number[]>([]);

  // Background Batch (Server Side)
  const [batchStatus, setBatchStatus] = useState<any>(null);
  const [showProcessModal, setShowProcessModal] = useState(false);
  const [showStartModal, setShowStartModal] = useState(false);
  const [rewriteTitles, setRewriteTitles] = useState(false);

  // Sitemap
  const [showSitemapModal, setShowSitemapModal] = useState(false);

  // Scan / Health
  const [showScanModal, setShowScanModal] = useState(false);
  const [scanResult, setScanResult] = useState<any>(null);
  const [scanning, setScanning] = useState(false);

  // Client Side Batch (Small Selections)
  const [isClientBatch, setIsClientBatch] = useState(false);
  const [clientBatchProgress, setClientBatchProgress] = useState({ current: 0, total: 0, failed: 0 });

  // Preview
  const [previewItem, setPreviewItem] = useState<ContentItem | null>(null);

  const { apiUrl, nonce, homeUrl } = (window as any).woosuiteData || {};

  // Initial Poll Check
  useEffect(() => {
      checkBatchStatus();
  }, []);

  // Poll when running or paused
  useEffect(() => {
      let interval: any;
      if (batchStatus?.status === 'running' || batchStatus?.status === 'paused') {
          interval = setInterval(checkBatchStatus, 3000);
      }
      // If finished, refresh data once
      if (batchStatus?.status === 'complete') {
          fetchItems();
      }
      return () => clearInterval(interval);
  }, [batchStatus?.status]);

  useEffect(() => {
    fetchItems();
  }, [activeTab, page, showUnoptimized, limit]);

  const checkBatchStatus = async () => {
      if (!apiUrl) return;
      try {
          const res = await fetch(`${apiUrl}/seo/batch-status`, {
              headers: { 'X-WP-Nonce': nonce }
          });
          if (res.ok) {
              const data = await res.json();
              setBatchStatus(data);
          }
      } catch (e) { console.error(e); }
  };

  const handleScan = async () => {
      setScanning(true);
      setShowScanModal(true);
      try {
          const res = await fetch(`${apiUrl}/seo/scan`, {
              headers: { 'X-WP-Nonce': nonce }
          });
          if (res.ok) {
              const data = await res.json();
              setScanResult(data);
          }
      } catch (e) { console.error(e); }
      setScanning(false);
  };

  const fetchItems = async () => {
    if (!apiUrl) return;
    setLoading(true);
    try {
        let url = `${apiUrl}/content?type=${activeTab}&limit=${limit}&page=${page}`;
        if (showUnoptimized) {
            url += '&filter=unoptimized';
        }

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

  const startBackgroundBatch = async (ids: number[] = []) => {
      if (!apiUrl) return;
      try {
          const res = await fetch(`${apiUrl}/seo/batch`, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
              body: JSON.stringify({
                  rewriteTitles,
                  type: activeTab,
                  ids: ids
              })
          });
          if (res.ok) {
              await checkBatchStatus();
              setShowProcessModal(true);
              setShowStartModal(false);
              fetchItems();
          }
      } catch (e) {
          console.error("Failed to start batch", e);
      }
  };

  const resumeBatch = async () => {
      if (!apiUrl) return;
      try {
          const res = await fetch(`${apiUrl}/seo/batch/resume`, {
              method: 'POST',
              headers: { 'X-WP-Nonce': nonce }
          });
          if (res.ok) {
              await checkBatchStatus();
          }
      } catch (e) { console.error(e); }
  };

  const stopBackgroundBatch = async () => {
      if (!apiUrl) return;
      try {
          await fetch(`${apiUrl}/seo/batch/stop`, {
              method: 'POST',
              headers: { 'X-WP-Nonce': nonce }
          });
          // Update status immediately for UI responsiveness
          setBatchStatus((prev: any) => ({ ...prev, status: 'stopped', message: 'Stopping...' }));
      } catch (e) { console.error(e); }
  };

  const resetBackgroundBatch = async () => {
      if (!apiUrl) return;
      if (!confirm("Are you sure? This will force the process status to 'idle' and CLEAR all error flags. Use this if the process is stuck.")) return;
      try {
          await fetch(`${apiUrl}/seo/batch/reset`, {
              method: 'POST',
              headers: { 'X-WP-Nonce': nonce }
          });
          checkBatchStatus();
          fetchItems(); // Refresh list to remove error badges
      } catch (e) { console.error(e); }
  };

  const handleTabChange = (tab: ContentType) => {
      if (tab === activeTab) return;
      setActiveTab(tab);
      setPage(1);
      setSelectedIds([]);
  };

  const handleGenerate = async (item: ContentItem) => {
    setGenerating(item.id);
    try {
      const res = await fetch(`${apiUrl}/seo/generate/${item.id}`, {
           method: 'POST',
           headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
           body: JSON.stringify({})
      });

      if (!res.ok) {
           const err = await res.json().catch(() => ({ message: res.statusText }));
           throw new Error(err.message || 'Server error');
      }

      const json = await res.json();
      if (json.success && json.data) {
           const updates = mapResultToItem(json.data, item.type);
           setItems(prev => prev.map(p => p.id === item.id ? { ...p, ...updates } : p));
      } else {
           throw new Error(json.message || 'Unknown error');
      }

    } catch (e: any) {
      console.error(e);
      // alert(`Generation Failed: ${e.message}`);
      setItems(prev => prev.map(p => p.id === item.id ? { ...p, lastError: e.message } : p));
    } finally {
      setGenerating(null);
    }
  };

  const mapResultToItem = (result: any, type: ContentType) => {
      const updates: any = { lastError: undefined, hasHistory: true }; // Clear error, set history
      if (type === 'image') {
          updates.altText = result.altText;
          updates.name = result.title;
      } else {
          updates.metaTitle = result.title;
          updates.metaDescription = result.description;
          updates.llmSummary = result.llmSummary;
      }
      return updates;
  };

  const handleBulkOptimize = async () => {
      if (selectedIds.length === 0) return;

      if (selectedIds.length < 50) {
          startClientBatch();
      } else {
          await startBackgroundBatch(selectedIds);
          setSelectedIds([]);
      }
  };

  const startClientBatch = async () => {
      setIsClientBatch(true);
      setClientBatchProgress({ current: 0, total: selectedIds.length, failed: 0 });

      for (let i = 0; i < selectedIds.length; i++) {
          const id = selectedIds[i];
          const item = items.find(p => p.id === id);
          if (item) {
             try {
                const res = await fetch(`${apiUrl}/seo/generate/${id}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
                    body: JSON.stringify({ rewriteTitle: false })
                });
                if (!res.ok) throw new Error("Failed");

                const json = await res.json();
                if (json.success && json.data) {
                    const updates = mapResultToItem(json.data, item.type);
                    setItems(prev => prev.map(p => p.id === id ? { ...p, ...updates } : p));
                }
             } catch (e) {
                 setClientBatchProgress(prev => ({ ...prev, failed: prev.failed + 1 }));
             }
          }
          setClientBatchProgress(prev => ({ ...prev, current: i + 1 }));
      }
      setIsClientBatch(false);
      setSelectedIds([]);
  };

  const handleRestore = async (item: ContentItem) => {
      if (!confirm("Undo last changes?")) return;
      try {
          const res = await fetch(`${apiUrl}/content/restore`, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
              body: JSON.stringify({ id: item.id, field: 'all' })
          });
          if (res.ok) {
              fetchItems();
          }
      } catch (e) { console.error(e); }
  };

  const toggleSelectAllPage = () => {
      if (selectedIds.length === items.length) {
          setSelectedIds([]);
      } else {
          setSelectedIds(items.map(i => i.id));
      }
  };

  return (
    <div className="space-y-6">

      {/* Background Process Banner */}
      {(batchStatus?.status === 'running' || batchStatus?.status === 'paused') && (
          <div className={`rounded-lg p-4 text-white shadow-md flex items-center justify-between ${batchStatus.status === 'paused' ? 'bg-amber-500' : 'bg-gradient-to-r from-purple-500 to-indigo-600'}`}>
              <div className="flex items-center gap-3">
                  {batchStatus.status === 'paused' ? <Loader className="animate-spin" /> : <RefreshCw className="animate-spin" />}
                  <div>
                      <div className="font-bold">
                          {batchStatus.status === 'paused' ? 'Optimization Paused (Rate Limit)' : 'Background Optimization Running'}
                      </div>
                      <div className="text-sm opacity-90">
                          {batchStatus.status === 'paused'
                            ? 'Hit API limit. Auto-resuming in ~60 seconds...'
                            : `WooSuite AI is optimizing ${batchStatus.total} items in the background.`}
                      </div>
                  </div>
              </div>
              <div className="flex gap-2">
                  <button
                    onClick={() => setShowProcessModal(true)}
                    className="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg text-sm font-medium transition"
                  >
                      Show Progress
                  </button>
                  <button
                    onClick={stopBackgroundBatch}
                    className="bg-red-500/80 hover:bg-red-500 px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2"
                  >
                      <Ban size={16} /> Stop
                  </button>
              </div>
          </div>
      )}

      {/* Force Reset Banner (If stuck or stopped) */}
      {batchStatus?.status !== 'idle' && batchStatus?.status !== 'running' && batchStatus?.status !== 'paused' && (
           <div className="bg-gray-100 rounded-lg p-3 flex justify-between items-center text-sm text-gray-600">
               <span>Process status: <strong>{batchStatus?.status}</strong></span>
               <div className="flex gap-3">
                   <button onClick={resumeBatch} className="text-purple-600 hover:underline text-xs font-bold">
                       Resume Process
                   </button>
                   <button onClick={resetBackgroundBatch} className="text-red-600 hover:underline text-xs">
                       Force Reset Status
                   </button>
               </div>
           </div>
      )}

      {/* Stuck Detection Banner */}
      {batchStatus?.status === 'running' && (Date.now() / 1000 - batchStatus.last_updated > 120) && (
           <div className="bg-amber-50 border border-amber-200 rounded-lg p-3 flex justify-between items-center text-sm text-amber-800 mb-4">
               <div className="flex items-center gap-2">
                   <AlertTriangle size={16} />
                   <span>Process seems stuck (no updates for 2+ mins). WP Cron might be inactive.</span>
               </div>
               <button onClick={resumeBatch} className="bg-amber-100 hover:bg-amber-200 text-amber-900 px-3 py-1 rounded text-xs font-medium transition">
                   Resume / Kickstart
               </button>
           </div>
      )}

      {/* Header & Controls */}
      <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h2 className="text-2xl font-bold text-gray-800">AI SEO Manager</h2>
            <p className="text-gray-500">Optimize for Traditional Search (Google, Bing) and AI Search (ChatGPT, Gemini).</p>
        </div>
        <div className="flex gap-2">
            <button
                onClick={handleScan}
                className="bg-white border border-gray-300 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition flex items-center gap-2 text-purple-700">
                <PieChart size={16} /> Scan Website
            </button>
            <button
                onClick={() => setShowSitemapModal(true)}
                className="bg-white border border-gray-300 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition flex items-center gap-2">
                <Layout size={16} /> Sitemap
            </button>
            <a
                href={`${homeUrl}/llms.txt`}
                target="_blank"
                rel="noopener noreferrer"
                className="bg-white border border-gray-300 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition flex items-center gap-2 text-gray-700"
            >
                <FileText size={16} /> llms.txt
            </a>

            {/* Action Buttons */}
            {selectedIds.length > 0 ? (
                <button
                    onClick={handleBulkOptimize}
                    className="px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm flex items-center gap-2 bg-purple-600 text-white hover:bg-purple-700"
                >
                    <Sparkles size={16} /> Optimize Selected ({selectedIds.length})
                </button>
            ) : (
                // Background Bulk Trigger
                <button
                    onClick={() => setShowStartModal(true)}
                    disabled={batchStatus?.status === 'running' || batchStatus?.status === 'paused'}
                    className={`px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm flex items-center gap-2 border
                        ${(batchStatus?.status === 'running' || batchStatus?.status === 'paused')
                            ? 'bg-gray-100 text-gray-400 cursor-not-allowed border-gray-200'
                            : 'bg-indigo-50 text-indigo-700 border-indigo-200 hover:bg-indigo-100'
                        }`}
                >
                    {(batchStatus?.status === 'running' || batchStatus?.status === 'paused') ? <RefreshCw size={16} className="animate-spin" /> : <Play size={16} />}
                    {batchStatus?.status === 'paused' ? 'Resuming...' : 'Optimize All Content'}
                </button>
            )}
        </div>
      </div>

      {/* Tabs & Filters */}
      <div className="flex flex-col sm:flex-row justify-between items-end border-b border-gray-200 pb-1 gap-4">
          <div className="flex gap-2 overflow-x-auto w-full sm:w-auto">
            {[
                { id: 'product', label: 'Products', icon: Box },
                { id: 'post', label: 'Posts', icon: FileText },
                { id: 'page', label: 'Pages', icon: Layout },
                { id: 'image', label: 'Images', icon: ImageIcon }
            ].map(tab => (
                <button
                    key={tab.id}
                    onClick={() => handleTabChange(tab.id as ContentType)}
                    className={`px-4 py-2 text-sm font-medium rounded-t-lg flex items-center gap-2 transition whitespace-nowrap
                        ${activeTab === tab.id
                            ? 'bg-white border-b-2 border-purple-600 text-purple-600'
                            : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'}`}
                >
                    <tab.icon size={16} /> {tab.label}
                </button>
            ))}
          </div>

          <div className="flex items-center gap-2 pb-2">
              <button onClick={() => fetchItems()} className="p-2 text-gray-500 hover:text-purple-600 transition" title="Refresh List">
                  <RotateCw size={16} className={loading ? 'animate-spin' : ''} />
              </button>
              <button
                onClick={() => setShowUnoptimized(!showUnoptimized)}
                className={`flex items-center gap-2 text-sm px-3 py-1.5 rounded-full border transition
                    ${showUnoptimized
                        ? 'bg-amber-50 border-amber-200 text-amber-700 font-medium'
                        : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'}`}
              >
                  <Filter size={14} />
                  {showUnoptimized ? 'Showing: Unoptimized' : 'Filter: All Items'}
                  {showUnoptimized && <X size={12} className="ml-1 opacity-60" />}
              </button>
          </div>
      </div>

      {/* Content Table */}
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
                    onChange={toggleSelectAllPage}
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
                      if (item.lastError) {
                          return (
                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 cursor-help" title={item.lastError}>
                              <AlertTriangle size={12} className="mr-1" /> Error
                            </span>
                          );
                      }

                      const analysis = [];
                      if (activeTab === 'image') {
                          if (item.altText && item.altText.length > 5) analysis.push({ label: 'Alt Text Present', pass: true });
                          else analysis.push({ label: 'Missing Alt Text', pass: false });
                      } else {
                          // Title Check
                          if (item.metaTitle && item.metaTitle.length >= 30 && item.metaTitle.length <= 60) analysis.push({ label: 'Title Length (30-60)', pass: true });
                          else if (item.metaTitle && item.metaTitle.length > 60) analysis.push({ label: 'Title too long (>60)', pass: false });
                          else analysis.push({ label: 'Title too short or missing', pass: false });

                          // Desc Check
                          if (item.metaDescription && item.metaDescription.length >= 120 && item.metaDescription.length <= 160) analysis.push({ label: 'Meta Desc Length (120-160)', pass: true });
                          else if (item.metaDescription && item.metaDescription.length > 160) analysis.push({ label: 'Meta Desc too long', pass: false });
                          else analysis.push({ label: 'Meta Desc too short/missing', pass: false });
                      }

                      const isOptimized = analysis.every(a => a.pass);

                      return (
                        <div className="group relative flex items-center">
                            {isOptimized ? (
                                <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 cursor-help">
                                    <Check size={12} className="mr-1" /> Good
                                </span>
                            ) : (
                                <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 cursor-help">
                                    <AlertCircle size={12} className="mr-1" /> Improvements
                                </span>
                            )}

                            {/* Analysis Tooltip */}
                            <div className="absolute left-0 top-full mt-2 w-64 bg-white border border-gray-200 shadow-xl rounded-lg p-3 z-50 hidden group-hover:block">
                                <h4 className="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">SEO Analysis</h4>
                                <ul className="space-y-1">
                                    {analysis.map((check, idx) => (
                                        <li key={idx} className="flex items-start gap-2 text-xs">
                                            {check.pass ? (
                                                <Check size={12} className="text-green-500 mt-0.5" />
                                            ) : (
                                                <X size={12} className="text-red-500 mt-0.5" />
                                            )}
                                            <span className={check.pass ? 'text-gray-600' : 'text-red-600 font-medium'}>
                                                {check.label}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        </div>
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
                        disabled={generating === item.id}
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

                    <button
                        onClick={() => setPreviewItem(item)}
                        className="text-xs text-gray-500 hover:text-purple-600 flex items-center justify-center gap-1 mt-1 w-full"
                    >
                        <Eye size={12} /> View Data
                    </button>

                    {item.hasHistory && (
                        <button
                            onClick={() => handleRestore(item)}
                            className="text-xs text-gray-500 hover:text-red-600 flex items-center justify-center gap-1 mt-1 w-full"
                        >
                            <RotateCcw size={12} /> Undo
                        </button>
                    )}

                    {item.permalink && activeTab !== 'image' && (
                    <a
                        href={`https://metatags.io/?url=${encodeURIComponent(item.permalink)}`}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="inline-flex items-center justify-center px-3 py-1.5 rounded-lg text-sm font-medium text-gray-600 bg-gray-50 hover:bg-gray-100 hover:text-gray-900 transition w-full border border-gray-200"
                        title="Verify Meta Tags"
                    >
                        <ExternalLink size={14} className="mr-1.5" /> Verify
                    </a>
                    )}
                  </div>
                </td>
              </tr>
            ))}
            {items.length === 0 && (
                <tr>
                    <td colSpan={5} className="p-12 text-center text-gray-400">
                        {showUnoptimized ? 'No unoptimized items found. Good job!' : 'No content found for this type.'}
                    </td>
                </tr>
            )}
          </tbody>
        </table>
        )}

        {/* Pagination Controls */}
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

      {/* Start Modal */}
      {showStartModal && (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
              <div className="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                  <h3 className="text-xl font-bold mb-4">Start Optimization</h3>
                  <div className="space-y-4">
                      <p className="text-gray-600">
                          This process will optimize all <strong>{totalItems}</strong> <span className="capitalize">{activeTab}s</span> in the background.
                          <br/>
                          {activeTab === 'product' && <span className="text-sm text-purple-600 font-medium">✨ Includes automatic optimization of Product Images!</span>}
                      </p>

                      <div className="bg-gray-50 p-4 rounded-lg border border-gray-200">
                          <label className="flex items-start gap-3 cursor-pointer">
                              <input
                                type="checkbox"
                                checked={rewriteTitles}
                                onChange={(e) => setRewriteTitles(e.target.checked)}
                                className="mt-1 rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                              />
                              <div>
                                  <div className="font-medium text-gray-800">Simplify Product Names</div>
                                  <div className="text-xs text-gray-500">
                                      Use AI to rewrite and shorten product titles (Max 6 words).
                                      <br/><span className="text-red-500 font-semibold">Warning: This overwrites your product titles.</span>
                                  </div>
                              </div>
                          </label>
                      </div>
                  </div>
                  <div className="mt-6 flex justify-end gap-3">
                      <button
                        onClick={() => setShowStartModal(false)}
                        className="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200"
                      >
                        Cancel
                      </button>
                      <button
                        onClick={startBackgroundBatch}
                        className="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 flex items-center gap-2"
                      >
                        <Play size={16} /> Start Batch
                      </button>
                  </div>
              </div>
          </div>
      )}

      {/* Scan Modal */}
      {showScanModal && (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
               <div className="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                   <h3 className="text-xl font-bold mb-4">Website SEO Health</h3>

                   {scanning ? (
                       <div className="p-8 flex flex-col items-center gap-4">
                           <Loader className="animate-spin text-purple-600" size={32} />
                           <span className="text-gray-600">Scanning content...</span>
                       </div>
                   ) : scanResult ? (
                       <div className="space-y-6">
                           <div className="flex items-center justify-between bg-gray-50 p-4 rounded-lg">
                               <div>
                                   <div className="text-3xl font-bold text-gray-900">{scanResult.score}/100</div>
                                   <div className="text-sm text-gray-500">Overall Optimization Score</div>
                               </div>
                               <div className={`p-3 rounded-full ${scanResult.score >= 80 ? 'bg-green-100 text-green-600' : 'bg-amber-100 text-amber-600'}`}>
                                   <PieChart size={24} />
                               </div>
                           </div>

                           <div className="space-y-3">
                               <div className="flex justify-between items-center text-sm border-b border-gray-100 pb-2">
                                   <span className="font-medium text-gray-700">Products</span>
                                   <span className="text-red-500 font-bold">{scanResult.details.product.missing} unoptimized</span>
                               </div>
                               <div className="flex justify-between items-center text-sm border-b border-gray-100 pb-2">
                                   <span className="font-medium text-gray-700">Images</span>
                                   <span className="text-red-500 font-bold">{scanResult.details.image.missing} unoptimized</span>
                               </div>
                               <div className="flex justify-between items-center text-sm border-b border-gray-100 pb-2">
                                   <span className="font-medium text-gray-700">Posts</span>
                                   <span className="text-red-500 font-bold">{scanResult.details.post.missing} unoptimized</span>
                               </div>
                           </div>

                           <div className="bg-blue-50 text-blue-800 p-3 rounded text-xs">
                               <span className="font-bold">Tip:</span> Use the "Optimize All Content" button to fix these issues automatically.
                           </div>
                       </div>
                   ) : (
                       <div className="text-red-500">Scan failed. Please try again.</div>
                   )}

                   <div className="mt-6 flex justify-end">
                       <button
                            onClick={() => setShowScanModal(false)}
                            className="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200"
                       >
                           Close
                       </button>
                   </div>
               </div>
          </div>
      )}

      {/* Preview Modal */}
      {previewItem && (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
               <div className="bg-white rounded-xl shadow-xl max-w-lg w-full p-6 overflow-y-auto max-h-[90vh]">
                   <div className="flex justify-between items-center mb-4">
                        <h3 className="text-xl font-bold">SEO Data Preview</h3>
                        <button onClick={() => setPreviewItem(null)} className="text-gray-400 hover:text-gray-600"><X size={20}/></button>
                   </div>

                   <div className="space-y-4 text-sm">
                       <div>
                           <div className="font-semibold text-gray-500 mb-1">Post Title</div>
                           <div className="p-2 bg-gray-50 border border-gray-200 rounded text-gray-800">{previewItem.name}</div>
                       </div>

                       {previewItem.metaTitle && (
                           <div>
                               <div className="font-semibold text-gray-500 mb-1">Meta Title</div>
                               <div className="p-2 bg-gray-50 border border-gray-200 rounded text-gray-800">{previewItem.metaTitle}</div>
                           </div>
                       )}

                       {previewItem.metaDescription && (
                           <div>
                               <div className="font-semibold text-gray-500 mb-1">Meta Description</div>
                               <div className="p-2 bg-gray-50 border border-gray-200 rounded text-gray-800">{previewItem.metaDescription}</div>
                           </div>
                       )}

                       {previewItem.llmSummary && (
                           <div>
                               <div className="font-semibold text-gray-500 mb-1">AI Summary</div>
                               <div className="p-2 bg-gray-50 border border-gray-200 rounded text-gray-800">{previewItem.llmSummary}</div>
                           </div>
                       )}

                       {previewItem.altText && (
                           <div>
                               <div className="font-semibold text-gray-500 mb-1">Alt Text</div>
                               <div className="p-2 bg-gray-50 border border-gray-200 rounded text-gray-800">{previewItem.altText}</div>
                           </div>
                       )}
                   </div>

                   <div className="mt-6 flex justify-end">
                       <button
                            onClick={() => setPreviewItem(null)}
                            className="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200"
                       >
                           Close
                       </button>
                   </div>
               </div>
          </div>
      )}

      {/* Client Batch Modal */}
      {isClientBatch && (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
               <div className="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                   <h3 className="text-xl font-bold mb-4">Optimizing Selected Items...</h3>
                   <div className="space-y-4">
                       <div className="flex items-center gap-3">
                            <RefreshCw className="animate-spin text-purple-600" size={24} />
                            <div>
                                <div className="font-semibold text-gray-900">Processing...</div>
                                <div className="text-sm text-gray-500">Please do not close this tab.</div>
                            </div>
                       </div>
                       <div>
                           <div className="flex justify-between text-sm mb-1">
                               <span>Progress</span>
                               <span>{Math.round((clientBatchProgress.current / clientBatchProgress.total) * 100)}%</span>
                           </div>
                           <div className="h-2 bg-gray-100 rounded-full overflow-hidden flex">
                               <div
                                 className="h-full bg-purple-600 transition-all duration-300 ease-out"
                                 style={{ width: `${((clientBatchProgress.current - clientBatchProgress.failed) / clientBatchProgress.total) * 100}%` }}
                               />
                               {clientBatchProgress.failed > 0 && (
                                   <div
                                     className="h-full bg-red-500 transition-all duration-300 ease-out"
                                     style={{ width: `${(clientBatchProgress.failed / clientBatchProgress.total) * 100}%` }}
                                   />
                               )}
                           </div>
                           <div className="flex justify-between text-xs mt-1 text-gray-500">
                                <span>Processed: {clientBatchProgress.current}</span>
                                {clientBatchProgress.failed > 0 && <span className="text-red-600 font-semibold">{clientBatchProgress.failed} Failed</span>}
                                <span>Total: {clientBatchProgress.total}</span>
                           </div>
                       </div>
                   </div>
               </div>
          </div>
      )}

      {/* Progress Modal */}
      {showProcessModal && batchStatus && (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
               <div className="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                   <div className="flex justify-between items-center mb-4">
                       <h3 className="text-xl font-bold">Background Optimization</h3>
                       <button onClick={() => setShowProcessModal(false)} className="text-gray-400 hover:text-gray-600">
                           <X size={20} />
                       </button>
                   </div>

                   <div className="space-y-4">
                       <div className="flex items-center gap-4">
                            <div className={`p-3 rounded-full ${batchStatus.status === 'running' ? 'bg-purple-100 text-purple-600' : batchStatus.status === 'paused' ? 'bg-amber-100 text-amber-600' : 'bg-green-100 text-green-600'}`}>
                                {batchStatus.status === 'running' ? <RefreshCw className="animate-spin" size={24} /> : batchStatus.status === 'paused' ? <Loader className="animate-spin" size={24} /> : <Check size={24} />}
                            </div>
                            <div>
                                <div className="font-semibold text-gray-900 capitalize">{batchStatus.status}</div>
                                <div className="text-sm text-gray-500">{batchStatus.message}</div>
                            </div>
                       </div>

                       {batchStatus.total > 0 && (
                           <div>
                               <div className="flex justify-between text-sm mb-1">
                                   <span>Progress</span>
                                   <span>{Math.round((batchStatus.processed / batchStatus.total) * 100)}%</span>
                               </div>
                               <div className="h-2 bg-gray-100 rounded-full overflow-hidden flex">
                                   <div
                                     className="h-full bg-purple-600 transition-all duration-500 ease-out"
                                     style={{ width: `${((batchStatus.processed - (batchStatus.failed || 0)) / batchStatus.total) * 100}%` }}
                                   />
                                   {batchStatus.failed > 0 && (
                                       <div
                                         className="h-full bg-red-500 transition-all duration-500 ease-out"
                                         style={{ width: `${(batchStatus.failed / batchStatus.total) * 100}%` }}
                                       />
                                   )}
                               </div>
                               <div className="flex justify-between text-xs mt-1 text-gray-500">
                                    <span>Processed: {batchStatus.processed}</span>
                                    {batchStatus.failed > 0 && <span className="text-red-600 font-semibold">{batchStatus.failed} Failed</span>}
                                    <span>Total: {batchStatus.total}</span>
                               </div>
                           </div>
                       )}

                       {batchStatus.status === 'running' && (
                           <div className="flex justify-center gap-2 mt-4">
                               <button
                                    onClick={stopBackgroundBatch}
                                    className="bg-red-50 text-red-600 hover:bg-red-100 px-4 py-1.5 rounded-lg text-sm font-medium flex items-center gap-2 transition"
                               >
                                   <Ban size={14} /> Stop
                               </button>
                               <button
                                    onClick={resetBackgroundBatch}
                                    className="bg-gray-50 text-gray-600 hover:bg-gray-100 px-4 py-1.5 rounded-lg text-sm font-medium flex items-center gap-2 transition"
                               >
                                   <Trash2 size={14} /> Force Reset
                               </button>
                           </div>
                       )}
                   </div>

                   <div className="mt-6 flex justify-end">
                       <button
                            onClick={() => setShowProcessModal(false)}
                            className="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200"
                       >
                           Close
                       </button>
                   </div>
               </div>
          </div>
      )}

      {/* Sitemap Modal */}
      {showSitemapModal && (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
              <div className="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                  <h3 className="text-xl font-bold mb-4">Sitemap Settings</h3>
                  <div className="space-y-4">
                      <div className="flex items-center justify-between">
                          <span className="font-medium text-gray-700">Enable Custom Sitemap</span>
                          <div className="bg-green-100 text-green-800 px-3 py-1 rounded text-sm font-medium">Active</div>
                      </div>
                      <div className="bg-gray-50 p-3 rounded border border-gray-200 break-all text-sm text-gray-600 font-mono flex items-center justify-between">
                          <span>{homeUrl ? `${homeUrl}/sitemap.xml` : '/sitemap.xml'}</span>
                          <a
                            href={homeUrl ? `${homeUrl}/sitemap.xml` : '/sitemap.xml'}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="text-purple-600 hover:text-purple-800"
                            title="Open Sitemap"
                          >
                              <ExternalLink size={16} />
                          </a>
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
