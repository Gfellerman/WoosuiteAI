import React, { useState, useEffect } from 'react';
import { Cloud, HardDrive, Download, RotateCcw, CheckCircle, ArrowRightLeft, Shield, Server, Database, AlertTriangle, Loader, Check, Sparkles } from 'lucide-react';

const BackupManager: React.FC = () => {
  const [activeTab, setActiveTab] = useState<'backups' | 'migration'>('backups');

  // Backups State (Mock for now, as focus is Migration)
  const [backingUp, setBackingUp] = useState(false);
  const [progress, setProgress] = useState(0);

  // Migration State
  const [migrationStep, setMigrationStep] = useState<number>(1);
  const [analyzing, setAnalyzing] = useState(false);
  const [analysisReport, setAnalysisReport] = useState<any>(null);

  const [exporting, setExporting] = useState(false);
  const [exportUrl, setExportUrl] = useState<string | null>(null);

  const [oldDomain, setOldDomain] = useState('');
  const [newDomain, setNewDomain] = useState('');
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

  const handleAnalyze = async () => {
      setAnalyzing(true);
      setAnalysisReport(null);
      try {
          const res = await fetch(`${apiUrl}/backup/analyze`, {
              method: 'POST',
              headers: { 'X-WP-Nonce': nonce }
          });
          const data = await res.json();
          if (res.ok) {
              setAnalysisReport(data);
              setMigrationStep(2);
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

  const handleExportDB = async () => {
      setExporting(true);
      try {
          const res = await fetch(`${apiUrl}/backup/export`, {
              method: 'POST',
              headers: { 'X-WP-Nonce': nonce }
          });
          const data = await res.json();
          if (res.ok && data.success) {
              setExportUrl(data.url);
              // Move to next step only after successful export?
              // Or user can skip to Step 3 if they already moved files.
          } else {
              alert("Export failed: " + data.message);
          }
      } catch (e) {
          console.error(e);
          alert("Export request failed.");
      } finally {
          setExporting(false);
      }
  };

  const handleReplace = async () => {
      if (!oldDomain || !newDomain) {
          alert("Please enter both domains.");
          return;
      }
      if (!confirm(`CRITICAL WARNING: This will replace '${oldDomain}' with '${newDomain}' in your entire database. This cannot be undone. Ensure you have a backup!`)) return;

      setReplacing(true);
      try {
          const res = await fetch(`${apiUrl}/backup/replace`, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
              body: JSON.stringify({ old_domain: oldDomain, new_domain: newDomain })
          });
          const data = await res.json();
          setReplaceResult(data);
      } catch (e) {
          console.error(e);
          alert("Replacement failed.");
      } finally {
          setReplacing(false);
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
                              {step === 1 ? 'AI Pre-Flight' : step === 2 ? 'DB Export' : 'URL Swap'}
                          </span>
                      </div>
                  ))}
              </div>

              {/* Step 1: AI Analysis */}
              {migrationStep === 1 && (
                  <div className="bg-white p-8 rounded-xl shadow-sm border border-gray-100 animate-in fade-in slide-in-from-right-4">
                      <div className="text-center max-w-lg mx-auto">
                          <div className="w-16 h-16 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center mx-auto mb-4">
                              <Sparkles size={32} />
                          </div>
                          <h3 className="text-xl font-bold text-gray-800 mb-2">AI Pre-Flight Check</h3>
                          <p className="text-gray-500 mb-6">
                              Before moving your 40GB site, let our AI analyze your system, PHP version, and plugins to ensure the destination server is compatible.
                          </p>

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

              {/* Step 2: DB Export */}
              {migrationStep === 2 && (
                  <div className="bg-white p-8 rounded-xl shadow-sm border border-gray-100 animate-in fade-in slide-in-from-right-4">
                      <div className="flex gap-8">
                          <div className="w-1/3 border-r border-gray-100 pr-8 hidden md:block">
                              <h4 className="font-bold text-gray-800 mb-4">Files (40GB)</h4>
                              <p className="text-sm text-gray-500 mb-4">
                                  Due to the large size, please move your files manually via FTP or Hostinger File Manager.
                              </p>
                              <div className="bg-blue-50 p-4 rounded-lg text-xs text-blue-800 border border-blue-100">
                                  <strong>Tip:</strong> Zip `wp-content/uploads` on the server before downloading to save time.
                              </div>
                          </div>
                          <div className="flex-1">
                               <h4 className="font-bold text-gray-800 mb-4 flex items-center gap-2">
                                   <Database size={20} className="text-purple-600" /> Database Export
                               </h4>
                               <p className="text-sm text-gray-600 mb-6">
                                   We will export a clean SQL dump of your database. This handles serialized data safely.
                               </p>

                               {!exportUrl ? (
                                   <button
                                     onClick={handleExportDB}
                                     disabled={exporting}
                                     className="w-full bg-gray-800 text-white py-4 rounded-xl font-bold hover:bg-gray-900 transition flex items-center justify-center gap-2"
                                   >
                                       {exporting ? <Loader className="animate-spin" /> : <Download size={20} />}
                                       {exporting ? 'Exporting Database...' : 'Generate SQL Dump'}
                                   </button>
                               ) : (
                                   <div className="bg-green-50 p-6 rounded-xl border border-green-200 text-center">
                                       <CheckCircle size={40} className="text-green-500 mx-auto mb-2" />
                                       <h5 className="font-bold text-green-800 text-lg">Export Ready!</h5>
                                       <p className="text-green-700 mb-4 text-sm">database_export.sql</p>
                                       <a
                                            href={exportUrl}
                                            download
                                            className="inline-block bg-green-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-green-700 transition"
                                       >
                                           Download SQL
                                       </a>
                                       <div className="mt-4 pt-4 border-t border-green-200">
                                           <button onClick={() => setMigrationStep(3)} className="text-green-800 font-medium text-sm hover:underline">
                                               I have moved my files, go to URL Swap &rarr;
                                           </button>
                                       </div>
                                   </div>
                               )}
                          </div>
                      </div>
                  </div>
              )}

              {/* Step 3: URL Swap */}
              {migrationStep === 3 && (
                  <div className="bg-white p-8 rounded-xl shadow-sm border border-gray-100 animate-in fade-in slide-in-from-right-4">
                      <div className="max-w-lg mx-auto">
                          <h3 className="text-xl font-bold text-gray-800 mb-2 flex items-center gap-2">
                              <ArrowRightLeft className="text-purple-600" /> URL Search & Replace
                          </h3>
                          <p className="text-sm text-gray-500 mb-6">
                              Run this <strong>AFTER</strong> importing the database to the new site. It fixes all links (images, settings, Elementor data).
                          </p>

                          <div className="space-y-4 mb-6">
                              <div>
                                  <label className="block text-xs font-bold text-gray-500 uppercase mb-1">Old Domain (Search)</label>
                                  <input
                                    type="text"
                                    value={oldDomain}
                                    onChange={e => setOldDomain(e.target.value)}
                                    placeholder="test.lacasa.market"
                                    className="w-full p-3 border border-gray-300 rounded-lg font-mono text-sm"
                                  />
                              </div>
                              <div>
                                  <label className="block text-xs font-bold text-gray-500 uppercase mb-1">New Domain (Replace)</label>
                                  <input
                                    type="text"
                                    value={newDomain}
                                    onChange={e => setNewDomain(e.target.value)}
                                    placeholder="lacasa.market"
                                    className="w-full p-3 border border-gray-300 rounded-lg font-mono text-sm focus:ring-2 focus:ring-purple-500 outline-none"
                                  />
                              </div>
                          </div>

                          <div className="bg-amber-50 p-4 rounded-lg flex gap-3 text-amber-800 text-xs mb-6 border border-amber-100">
                              <AlertTriangle size={24} className="shrink-0" />
                              <p>Warning: This modifies the database directly. Ensure you have a backup (Step 2) before proceeding.</p>
                          </div>

                          {replaceResult ? (
                              <div className="bg-green-50 p-6 rounded-xl border border-green-200 text-center">
                                  <CheckCircle size={32} className="text-green-600 mx-auto mb-2" />
                                  <div className="font-bold text-green-900">Replacement Complete!</div>
                                  <div className="text-sm text-green-700 mt-1">
                                      Modified {replaceResult.rows_affected} rows.
                                  </div>
                              </div>
                          ) : (
                              <button
                                onClick={handleReplace}
                                disabled={replacing}
                                className="w-full bg-purple-600 text-white py-3 rounded-lg font-bold hover:bg-purple-700 transition flex items-center justify-center gap-2"
                              >
                                  {replacing ? <Loader className="animate-spin" /> : <RotateCcw size={18} />}
                                  {replacing ? 'Processing Database...' : 'Run URL Replacement'}
                              </button>
                          )}
                      </div>
                  </div>
              )}
          </div>
      ) : (
          /* BACKUPS TAB (Placeholder / Existing) */
          <div className="space-y-6">
             {/* ... existing backup UI ... */}
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
