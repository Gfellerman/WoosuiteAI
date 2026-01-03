import React, { useState, useEffect } from 'react';
import { Cloud, HardDrive, Download, RotateCcw, CheckCircle, ArrowRightLeft, Shield, Server, Database, AlertTriangle, Loader, Check, Sparkles, AlertOctagon } from 'lucide-react';
import DeepLinkScanner from './Migration/DeepLinkScanner';
import MigrationStation from './Migration/MigrationStation';

const BackupManager: React.FC = () => {
  const [activeTab, setActiveTab] = useState<'backups' | 'migration'>('backups');

  // Backups State (Mock for now, as focus is Migration)
  const [backingUp, setBackingUp] = useState(false);
  const [progress, setProgress] = useState(0);

  // Migration State
  const [migrationMode, setMigrationMode] = useState<'export' | 'import'>('export');
  const [migrationStep, setMigrationStep] = useState<number>(1);
  const [analyzing, setAnalyzing] = useState(false);
  const [analysisReport, setAnalysisReport] = useState<any>(null);

  const [exporting, setExporting] = useState(false);
  const [exportProgress, setExportProgress] = useState<string>('');
  const [exportUrl, setExportUrl] = useState<string | null>(null);

  const [oldDomain, setOldDomain] = useState('');
  const [newDomain, setNewDomain] = useState('');

  // New State for Advanced Export
  const [doReplace, setDoReplace] = useState(false);
  const [backupConfirmed, setBackupConfirmed] = useState(false);

  const [replacing, setReplacing] = useState(false);
  const [replaceResult, setReplaceResult] = useState<any>(null);

  const { apiUrl, nonce, homeUrl } = (window as any).woosuiteData || {};

  // Auto-fill domain
  useEffect(() => {
      if (homeUrl) {
          const hostname = new URL(homeUrl).hostname;
          if (activeTab === 'migration' && !oldDomain) {
              setOldDomain(hostname);
          }
      }
  }, [homeUrl, activeTab]);

  // Auto-toggle replace if domains are different
  useEffect(() => {
      if (activeTab === 'migration' && oldDomain && newDomain && oldDomain !== newDomain) {
          setDoReplace(true);
      }
  }, [oldDomain, newDomain, activeTab]);

  const handleAnalyze = async () => {
      setAnalyzing(true);
      setAnalysisReport(null);
      try {
          const res = await fetch(`${apiUrl}/backup/analyze`, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
              body: JSON.stringify({ old_domain: oldDomain, new_domain: newDomain })
          });
          const data = await res.json();
          if (res.ok) {
              setAnalysisReport(data);
          } else {
              alert("Analysis failed: " + data.message);
          }
      } catch (e) {
          console.error(e);
          alert("Network error during analysis.");
      } finally {
          setAnalyzing(false);
      }
  };

  const handleChunkedExport = async () => {
      try {
          // 1. Get Tables
          setExportProgress("Fetching table list...");
          const tablesRes = await fetch(`${apiUrl}/backup/tables`, {
              headers: { 'X-WP-Nonce': nonce }
          });
          const tablesData = await tablesRes.json();

          if ( !tablesData.tables ) throw new Error("Could not fetch tables.");

          const tables: {name: string, rows: number}[] = tablesData.tables;
          // let totalRows = tables.reduce((acc, t) => acc + t.rows, 0); // Unused for now
          // let exportedRows = 0;

          // 2. Iterate and Chunk
          for (const table of tables) {
              const limit = 1000;
              let offset = 0;

              let hasMore = true;
              while (hasMore) {
                  // Update UI
                  setExportProgress(`Exporting ${table.name} (${offset}/${table.rows})...`);

                  const chunkRes = await fetch(`${apiUrl}/backup/export/chunk`, {
                       method: 'POST',
                       headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
                       body: JSON.stringify({
                           table: table.name,
                           offset: offset,
                           limit: limit,
                           // PASS REPLACEMENT PARAMS
                           search: doReplace ? oldDomain : '',
                           replace: doReplace ? newDomain : ''
                       })
                  });

                  if (!chunkRes.ok) {
                      const err = await chunkRes.json();
                      throw new Error(err.message || "Chunk failed");
                  }

                  const chunkData = await chunkRes.json();
                  const count = chunkData.count;

                  // exportedRows += count;
                  offset += count;

                  if (count < limit) hasMore = false;

                  // Small delay to prevent server overload
                  await new Promise(r => setTimeout(r, 50));
              }
          }

          // 3. Finalize
          setExportProgress("Finalizing export file...");
          const finRes = await fetch(`${apiUrl}/backup/export/finalize`, {
               method: 'POST',
               headers: { 'X-WP-Nonce': nonce }
          });
          const finData = await finRes.json();

          setExportUrl(finData.result.url);
          setExporting(false);
          setExportProgress('');

      } catch (e: any) {
          console.error(e);
          alert("Export Error: " + e.message);
          setExporting(false);
          setExportProgress('');
      }
  };

  const handleExportDB = async () => {
      if (doReplace && !backupConfirmed) {
          alert("Please confirm that you have created a backup of the DESTINATION site before proceeding with replacement.");
          return;
      }

      setExporting(true);
      setExportProgress('Initializing...');
      try {
          // 1. Start Process (Pass replace params)
          const res = await fetch(`${apiUrl}/backup/export`, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
              body: JSON.stringify({
                  replace: doReplace,
                  old_domain: oldDomain,
                  new_domain: newDomain
              })
          });
          const data = await res.json();

          // Special handling for legacy errors or explicit "not found" messages
          if (!res.ok) {
              if (data.message && data.message.includes('mysqldump not found')) {
                 console.warn("Forcing PHP Chunked Mode due to missing mysqldump.");
                 await handleChunkedExport();
                 return;
              }

              alert("Start failed: " + (data.message || "Unknown error"));
              setExporting(false);
              return;
          }

          // CHECK FOR PHP FALLBACK SIGNAL (This should happen if doReplace is true)
          if (data.method === 'php_chunked') {
              // Switch to Chunked Export Mode
              await handleChunkedExport();
              return;
          }

          // 2. Standard Polling (mysqldump)
          const pollInterval = setInterval(async () => {
              try {
                  const statusRes = await fetch(`${apiUrl}/backup/export/status`, {
                      headers: { 'X-WP-Nonce': nonce }
                  });
                  const statusData = await statusRes.json();

                  if (statusData.status === 'complete') {
                      clearInterval(pollInterval);
                      setExportUrl(statusData.url);
                      setExporting(false);
                  } else if (statusData.status === 'failed') {
                      clearInterval(pollInterval);
                      alert("Export failed: " + statusData.message);
                      setExporting(false);
                  }
                  // Continue polling if 'processing' or 'starting'
              } catch (e) {
                  console.error("Polling error", e);
              }
          }, 5000); // Check every 5s

      } catch (e) {
          console.error(e);
          alert("Network error.");
          setExporting(false);
      }
  };

  return (
    <div className="space-y-6">
       <div className="flex justify-between items-center border-b border-gray-200 pb-6">
        <div>
            <h2 className="text-2xl font-bold text-gray-800">Backups & Migration</h2>
            <p className="text-gray-500">Secure cloud backups and site migration tools.</p>
        </div>
        <div className="flex bg-gray-100 p-1 rounded-lg">
            <button
                onClick={() => setActiveTab('backups')}
                className={`px-4 py-2 rounded-md text-sm font-medium transition ${activeTab === 'backups' ? 'bg-white shadow-sm text-gray-800' : 'text-gray-500 hover:text-gray-700'}`}
            >
                Cloud Backups
            </button>
            <button
                onClick={() => setActiveTab('migration')}
                className={`px-4 py-2 rounded-md text-sm font-medium transition flex items-center gap-2 ${activeTab === 'migration' ? 'bg-white shadow-sm text-purple-700' : 'text-gray-500 hover:text-gray-700'}`}
            >
                <ArrowRightLeft size={16} /> Site Migration
            </button>
        </div>
      </div>

      {activeTab === 'migration' ? (
          <div className="max-w-4xl mx-auto">

              {/* Mode Toggle */}
              <div className="flex justify-center mb-8">
                  <div className="bg-gray-100 p-1 rounded-lg inline-flex shadow-inner">
                      <button
                          onClick={() => setMigrationMode('export')}
                          className={`px-6 py-2 rounded-md text-sm font-bold transition flex items-center gap-2 ${migrationMode === 'export' ? 'bg-white shadow text-purple-700' : 'text-gray-500 hover:text-gray-700'}`}
                      >
                          <Database size={16} /> Export (Source Site)
                      </button>
                      <button
                          onClick={() => setMigrationMode('import')}
                          className={`px-6 py-2 rounded-md text-sm font-bold transition flex items-center gap-2 ${migrationMode === 'import' ? 'bg-white shadow text-purple-700' : 'text-gray-500 hover:text-gray-700'}`}
                      >
                          <Download size={16} /> Import (Destination Site)
                      </button>
                  </div>
              </div>

              {migrationMode === 'import' ? (
                  <MigrationStation onCancel={() => setMigrationMode('export')} />
              ) : (
                  <>
              {/* Progress Stepper */}
              <div className="flex items-center justify-between mb-8 relative">
                  <div className="absolute left-0 top-1/2 w-full h-0.5 bg-gray-200 -z-10"></div>

                  {[1, 2, 3].map((step) => (
                      <div key={step}
                        onClick={() => setMigrationStep(step)}
                        className={`flex flex-col items-center gap-2 cursor-pointer bg-white px-2 ${migrationStep >= step ? 'text-purple-600' : 'text-gray-400'}`}>
                          <div className={`w-10 h-10 rounded-full flex items-center justify-center font-bold text-lg border-2 transition-all
                              ${migrationStep >= step ? 'bg-purple-600 text-white border-purple-600' : 'bg-white border-gray-300'}`}>
                              {step}
                          </div>
                          <span className="text-sm font-medium">
                              {step === 1 ? 'AI Pre-Flight' : step === 2 ? 'DB Export' : 'Instructions'}
                          </span>
                      </div>
                  ))}
              </div>

              {/* Step 1: AI Analysis (Now Shared Inputs) */}
              {migrationStep === 1 && (
                  <div className="bg-white p-8 rounded-xl shadow-sm border border-gray-100 animate-in fade-in slide-in-from-right-4">
                      <div className="text-center max-w-lg mx-auto">
                          <div className="w-16 h-16 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center mx-auto mb-4">
                              <Sparkles size={32} />
                          </div>
                          <h3 className="text-xl font-bold text-gray-800 mb-2">AI Pre-Flight Check</h3>
                          <p className="text-gray-500 mb-6">
                              Configure your migration domains below. Our AI will analyze your system for compatibility issues.
                          </p>

                          {/* Domain Inputs for AI Context & Global State */}
                          <div className="grid grid-cols-2 gap-4 mb-6 text-left max-w-md mx-auto">
                              <div>
                                  <label className="block text-xs font-bold text-gray-500 uppercase mb-1">Current Domain (Test)</label>
                                  <input
                                    type="text"
                                    value={oldDomain}
                                    onChange={e => setOldDomain(e.target.value)}
                                    placeholder="test.site.com"
                                    className="w-full p-2 border border-gray-300 rounded text-sm bg-gray-50"
                                    readOnly
                                  />
                              </div>
                              <div>
                                  <label className="block text-xs font-bold text-gray-500 uppercase mb-1">Target Domain (Live)</label>
                                  <input
                                    type="text"
                                    value={newDomain}
                                    onChange={e => setNewDomain(e.target.value)}
                                    placeholder="livesite.com"
                                    className="w-full p-2 border border-gray-300 rounded text-sm focus:border-purple-500 outline-none"
                                  />
                              </div>
                          </div>

                          {!analysisReport ? (
                              <button
                                onClick={handleAnalyze}
                                disabled={analyzing}
                                className="bg-purple-600 text-white px-8 py-3 rounded-lg font-bold hover:bg-purple-700 transition shadow-lg shadow-purple-200 flex items-center gap-2 mx-auto"
                              >
                                  {analyzing ? <Loader className="animate-spin" /> : <Shield size={20} />}
                                  {analyzing ? 'Analyzing System...' : 'Run Compatibility Scan'}
                              </button>
                          ) : (
                              <div className="text-left mt-6 bg-gray-50 p-6 rounded-xl border border-gray-200">
                                  {/* Report Details */}
                                  <div className="flex justify-between items-center mb-4">
                                      <h4 className="font-bold text-gray-800 flex items-center gap-2"><CheckCircle className="text-green-500"/> System Analysis</h4>
                                      <span className={`px-3 py-1 rounded-full text-xs font-bold uppercase ${analysisReport.risk === 'low' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'}`}>
                                          Risk: {analysisReport.risk}
                                      </span>
                                  </div>
                                  <div className="prose prose-sm text-gray-600 mb-4">
                                      {analysisReport.summary}
                                  </div>

                                  <h5 className="font-semibold text-gray-700 text-sm mb-2">AI Recommendations:</h5>
                                  <ul className="space-y-2 mb-6">
                                      {analysisReport.recommendations?.map((rec: string, i: number) => (
                                          <li key={i} className="flex items-start gap-2 text-sm bg-white p-2 rounded border border-gray-100">
                                              <Server size={14} className="text-blue-500 mt-0.5" /> {rec}
                                          </li>
                                      ))}
                                  </ul>

                                  <div className="flex justify-end">
                                      <button onClick={() => setMigrationStep(2)} className="text-purple-600 font-bold hover:text-purple-800 flex items-center gap-1">
                                          Proceed to Export <ArrowRightLeft size={16} />
                                      </button>
                                  </div>
                              </div>
                          )}
                      </div>
                  </div>
              )}

              {/* Step 2: DB Export (The Main Hub) */}
              {migrationStep === 2 && (
                  <div className="bg-white p-8 rounded-xl shadow-sm border border-gray-100 animate-in fade-in slide-in-from-right-4">
                      <div className="flex gap-8">
                          <div className="w-1/3 border-r border-gray-100 pr-8 hidden md:block">
                              <h4 className="font-bold text-gray-800 mb-4">Step 2: Database Export</h4>
                              <p className="text-sm text-gray-500 mb-4">
                                  This tool generates a SQL file containing all your products, posts, and settings.
                              </p>

                              <div className="bg-blue-50 p-4 rounded-lg text-xs text-blue-800 border border-blue-100 mb-4">
                                  <strong>Files (Images/Uploads):</strong>
                                  <br/>
                                  {analysisReport?.uploads_size_mb > 0
                                     ? `Estimated Size: ${analysisReport.uploads_size_mb} MB.`
                                     : `Estimated Size: Unknown.`}
                                  <br/>
                                  You must move `wp-content/uploads` manually via FTP.
                              </div>

                              <div className="text-xs text-gray-400">
                                  Current Source: <span className="font-mono text-gray-600">{oldDomain}</span>
                              </div>
                          </div>

                          <div className="flex-1">
                               <h4 className="font-bold text-gray-800 mb-4 flex items-center gap-2">
                                   <Database size={20} className="text-purple-600" /> Export Configuration
                               </h4>

                               {/* ADVANCED EXPORT OPTIONS */}
                               <div className="bg-gray-50 p-5 rounded-xl border border-gray-200 mb-6">
                                    <div className="flex items-center justify-between mb-2">
                                        <label className="font-bold text-sm text-gray-700 flex items-center gap-2">
                                            <input
                                                type="checkbox"
                                                checked={doReplace}
                                                onChange={e => setDoReplace(e.target.checked)}
                                                className="w-4 h-4 text-purple-600 rounded"
                                            />
                                            Replace Domains in Export File
                                        </label>
                                        <span className="text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full font-bold">Recommended</span>
                                    </div>
                                    <p className="text-xs text-gray-500 mb-4 ml-6">
                                        If checked, we will search for <strong>{oldDomain}</strong> and replace it with <strong>{newDomain}</strong> inside the SQL file.
                                        This makes the file ready for immediate import on the live site without further actions.
                                    </p>

                                    {doReplace && (
                                        <div className="bg-amber-50 p-4 rounded-lg border border-amber-200 ml-6 animate-in fade-in">
                                            <div className="flex gap-3">
                                                <AlertOctagon className="text-amber-600 shrink-0" size={20} />
                                                <div>
                                                    <h5 className="text-sm font-bold text-amber-800">Safety Check Required</h5>
                                                    <p className="text-xs text-amber-700 mt-1 mb-2">
                                                        You are about to generate a file that will <strong>overwrite</strong> your live site's database.
                                                        If anything goes wrong during import on the live site, you will need a backup to restore it.
                                                        <br/><br/>
                                                        <strong>We cannot backup the live site from here.</strong> You must do it manually.
                                                    </p>
                                                    <label className="flex items-start gap-2 cursor-pointer">
                                                        <input
                                                            type="checkbox"
                                                            checked={backupConfirmed}
                                                            onChange={e => setBackupConfirmed(e.target.checked)}
                                                            className="mt-1 w-4 h-4 text-amber-600 rounded border-amber-400 focus:ring-amber-500"
                                                        />
                                                        <span className="text-xs font-bold text-amber-900">
                                                            I confirm I have logged into {newDomain || 'the destination site'} and created a full database backup there.
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    )}
                               </div>

                               {!exportUrl ? (
                                   <div className="space-y-4">
                                       <button
                                         onClick={handleExportDB}
                                         disabled={exporting || (doReplace && !backupConfirmed)}
                                         className={`w-full py-4 rounded-xl font-bold transition flex items-center justify-center gap-2
                                            ${exporting || (doReplace && !backupConfirmed)
                                                ? 'bg-gray-300 text-gray-500 cursor-not-allowed'
                                                : 'bg-gray-900 text-white hover:bg-black shadow-lg shadow-gray-200'}`}
                                       >
                                           {exporting ? <Loader className="animate-spin" /> : <Download size={20} />}
                                           {exporting ? (exportProgress || 'Processing...') : (doReplace ? 'Generate Migration-Ready SQL' : 'Generate Standard SQL Dump')}
                                       </button>
                                       {exporting && (
                                           <div className="text-xs text-center text-gray-500 animate-pulse">
                                               Processing large database in chunks... Please do not close this tab.
                                           </div>
                                       )}
                                   </div>
                               ) : (
                                   <div className="bg-green-50 p-6 rounded-xl border border-green-200 text-center animate-in zoom-in-95">
                                       <CheckCircle size={40} className="text-green-500 mx-auto mb-2" />
                                       <h5 className="font-bold text-green-800 text-lg">Export Complete!</h5>
                                       <p className="text-green-700 mb-4 text-sm">
                                            {doReplace ? 'migration_ready_export.sql' : 'database_export.sql'}
                                       </p>
                                       <a
                                            href={exportUrl}
                                            download
                                            className="inline-block bg-green-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-green-700 transition"
                                       >
                                           Download SQL File ({doReplace ? 'Ready for Import' : 'Raw Backup'})
                                       </a>
                                       <div className="mt-4 pt-4 border-t border-green-200">
                                           <button onClick={() => setMigrationStep(3)} className="text-green-800 font-medium text-sm hover:underline">
                                               Next: View Import Instructions &rarr;
                                           </button>
                                       </div>
                                   </div>
                               )}
                          </div>
                      </div>
                  </div>
              )}

              {/* Step 3: Instructions (Formerly URL Swap) */}
              {migrationStep === 3 && (
                  <React.Fragment>
                  <div className="bg-white p-8 rounded-xl shadow-sm border border-gray-100 animate-in fade-in slide-in-from-right-4">
                      <div className="max-w-2xl mx-auto">
                          <h3 className="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                              <CheckCircle className="text-purple-600" /> Migration Instructions
                          </h3>

                          <div className="space-y-6 text-gray-600">
                              <div className="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                  <h4 className="font-bold text-gray-800 mb-2">If you used "Replace Domains in Export":</h4>
                                  <ol className="list-decimal list-inside space-y-2 text-sm">
                                      <li>Go to your destination site (<strong>{newDomain}</strong>).</li>
                                      <li>Ensure you have a backup there (as confirmed in Step 2).</li>
                                      <li>Import the SQL file using PHPMyAdmin, WP-CLI, or a generic import plugin.</li>
                                      <li><strong>Done!</strong> Your site should work immediately.</li>
                                  </ol>
                              </div>

                              <div className="bg-white p-4 rounded-lg border border-gray-200">
                                  <h4 className="font-bold text-gray-800 mb-2">If you generated a Standard Dump (Raw):</h4>
                                  <p className="text-sm mb-2">The file contains links to <strong>{oldDomain}</strong>. After importing, the site will likely redirect you back here.</p>
                                  <p className="text-sm">You must run a "Search & Replace" tool on the destination site manually to fix the links.</p>
                              </div>

                              <div className="text-center pt-4">
                                   <button
                                      onClick={() => setMigrationStep(1)}
                                      className="text-purple-600 font-bold hover:text-purple-800 text-sm"
                                   >
                                       Start Over
                                   </button>
                              </div>
                          </div>
                      </div>
                  </div>

                  {/* Step 4: AI Deep Scan (Optional) */}
                  <DeepLinkScanner oldDomain={oldDomain} newDomain={newDomain} />
              </React.Fragment>
              )}
              </>
            )}
          </div>
      ) : (
          /* BACKUPS TAB (Placeholder) */
          <div className="space-y-6">
             <div className="bg-white p-12 text-center rounded-xl border border-gray-200">
                 <Cloud size={48} className="text-gray-300 mx-auto mb-4" />
                 <h3 className="text-lg font-medium text-gray-700">Daily Cloud Backups</h3>
                 <p className="text-gray-500">Your automated backups are running safely in the background.</p>
             </div>
          </div>
      )}
    </div>
  );
};

export default BackupManager;
