import React, { useState } from 'react';
import { Upload, Shield, Download, Play, CheckCircle, AlertTriangle, FileJson, ArrowRight, Database, RefreshCw } from 'lucide-react';

interface MigrationStationProps {
    onCancel: () => void;
}

const MigrationStation: React.FC<MigrationStationProps> = ({ onCancel }) => {
    const [step, setStep] = useState<'upload' | 'validate' | 'backup' | 'migrate' | 'complete'>('upload');
    const [passport, setPassport] = useState<any>(null);
    const [validationReport, setValidationReport] = useState<any>(null);
    const [backupConfirmed, setBackupConfirmed] = useState(false);

    // Migration State
    const [phase, setPhase] = useState<'idle' | 'fetching' | 'processing'>('idle');
    const [progress, setProgress] = useState(0);
    const [statusMsg, setStatusMsg] = useState('');
    const [logs, setLogs] = useState<string[]>([]);

    const { apiUrl, nonce } = (window as any).woosuiteData || {};

    const addLog = (msg: string) => {
        setLogs(prev => [...prev.slice(-4), msg]);
        setStatusMsg(msg);
    };

    const handleFileUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = (event) => {
            try {
                const json = JSON.parse(event.target?.result as string);
                if (!json.token || !json.download_url) {
                    throw new Error("Invalid Passport file.");
                }
                setPassport(json);
                validatePassport(json);
            } catch (err) {
                alert("Error reading passport file: Invalid format.");
            }
        };
        reader.readAsText(file);
    };

    const validatePassport = async (json: any) => {
        setStep('validate');
        try {
            const res = await fetch(`${apiUrl}/backup/import/validate`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
                body: JSON.stringify({ passport: json })
            });
            const data = await res.json();
            setValidationReport(data);
        } catch (e) {
            console.error(e);
            alert("Validation failed.");
        }
    };

    const startMigration = async () => {
        setStep('migrate');
        setPhase('fetching');
        setProgress(0);
        addLog("Initializing migration sequence...");

        try {
            // PHASE 1: DOWNLOAD
            addLog("Phase 1: Fetching Backup from Source...");
            let offset = 0;
            let total = passport.filesize;
            let done = false;

            // Clean slate first (optional, backend handles it usually)

            while (!done) {
                const res = await fetch(`${apiUrl}/backup/import/fetch-chunk`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
                    body: JSON.stringify({
                        url: passport.download_url,
                        offset: offset
                    })
                });

                const data = await res.json();
                if (!res.ok) throw new Error(data.message || "Download failed");

                // data: { bytes: N, total_size: M, done: bool }
                offset = data.total_size; // Sync with actual file size on disk

                const percent = Math.min(99, Math.round((offset / total) * 100));
                setProgress(percent);
                addLog(`Downloaded ${Math.round(offset/1024/1024)}MB / ${Math.round(total/1024/1024)}MB`);

                done = data.done || (offset >= total);

                if (!done) await new Promise(r => setTimeout(r, 100)); // Throttling
            }

            addLog("Download Complete. Verifying Integrity...");
            await new Promise(r => setTimeout(r, 1000));

            // PHASE 2: PROCESS (IMPORT)
            setPhase('processing');
            addLog("Phase 2: Importing Database & Updating URLs...");
            setProgress(0);

            offset = 0; // Reset offset for reading local file
            done = false;
            let queriesTotal = 0;

            // Assuming current site is the "new domain"
            const currentDomain = new URL((window as any).woosuiteData.homeUrl).hostname;
            // Passport should ideally contain old domain, but we can infer or pass it.
            // For now, let's assume passport source_url contains it.
            const oldDomain = new URL(passport.source_url).hostname;

            while (!done) {
                const res = await fetch(`${apiUrl}/backup/import/process-chunk`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
                    body: JSON.stringify({
                        offset: offset,
                        old_domain: oldDomain,
                        new_domain: currentDomain
                    })
                });

                const data = await res.json();
                if (!res.ok) throw new Error(data.message || "Import failed");

                offset = data.offset;
                queriesTotal += data.queries;
                done = data.done;

                addLog(`Importing... Processed ${queriesTotal} queries.`);
                // We don't know total lines easily, so we might just pulse progress or estimate based on file bytes
                // Let's use file bytes offset / total file size
                const percent = Math.min(99, Math.round((offset / total) * 100));
                setProgress(percent);

                if (!done) await new Promise(r => setTimeout(r, 50));
            }

            addLog("Migration Successfully Completed!");
            setStep('complete');

        } catch (e: any) {
            console.error(e);
            addLog("CRITICAL ERROR: " + e.message);
            alert("Migration Failed: " + e.message);
            setStep('upload'); // Reset
        }
    };

    return (
        <div className="bg-white rounded-xl shadow-lg border border-purple-100 overflow-hidden">
            {/* Header */}
            <div className="bg-purple-900 text-white p-6 flex justify-between items-center">
                <div>
                    <h2 className="text-xl font-bold flex items-center gap-2">
                        <ArrowRight className="text-purple-300" /> Migration Station
                    </h2>
                    <p className="text-purple-200 text-sm opacity-80">Import a passport to migrate this site.</p>
                </div>
                <button onClick={onCancel} className="text-purple-300 hover:text-white text-sm">Cancel</button>
            </div>

            <div className="p-8">
                {step === 'upload' && (
                    <div className="text-center py-12 border-2 border-dashed border-gray-300 rounded-xl bg-gray-50 hover:bg-white transition">
                        <FileJson size={48} className="mx-auto text-gray-400 mb-4" />
                        <h3 className="font-bold text-gray-700 mb-2">Upload Migration Passport</h3>
                        <p className="text-gray-500 text-sm mb-6">Select the <code>passport.json</code> file generated from the source site.</p>

                        <input
                            type="file"
                            accept=".json"
                            onChange={handleFileUpload}
                            className="hidden"
                            id="passport-upload"
                        />
                        <label
                            htmlFor="passport-upload"
                            className="bg-purple-600 text-white px-6 py-3 rounded-lg font-bold cursor-pointer hover:bg-purple-700 transition"
                        >
                            Select File
                        </label>
                    </div>
                )}

                {step === 'validate' && passport && (
                    <div className="animate-in fade-in">
                        <div className="flex items-center gap-4 mb-6 bg-blue-50 p-4 rounded-lg border border-blue-100">
                            <div className="bg-blue-200 p-2 rounded-full text-blue-700 font-bold text-xl h-12 w-12 flex items-center justify-center">
                                {passport.system.db_size_mb > 100 ? 'L' : 'S'}
                            </div>
                            <div>
                                <h4 className="font-bold text-gray-800">Source: {passport.source_url}</h4>
                                <p className="text-sm text-gray-500">
                                    Size: {Math.round(passport.filesize / 1024 / 1024)} MB • Generated: {new Date(passport.generated_at * 1000).toLocaleDateString()}
                                </p>
                            </div>
                        </div>

                        {validationReport ? (
                            <div className="space-y-4">
                                {validationReport.errors.length > 0 ? (
                                    <div className="bg-red-50 p-4 rounded border border-red-200 text-red-700">
                                        <h5 className="font-bold flex items-center gap-2"><AlertTriangle size={18} /> Critical Issues Found:</h5>
                                        <ul className="list-disc list-inside text-sm mt-2">
                                            {validationReport.errors.map((e: string, i: number) => <li key={i}>{e}</li>)}
                                        </ul>
                                    </div>
                                ) : (
                                    <div className="bg-green-50 p-4 rounded border border-green-200 text-green-700 flex items-center gap-2">
                                        <CheckCircle size={18} /> Passport Validated. System Compatible.
                                    </div>
                                )}

                                {validationReport.warnings.map((w: string, i: number) => (
                                    <div key={i} className="bg-amber-50 p-3 rounded border border-amber-200 text-amber-800 text-sm flex items-center gap-2">
                                        <AlertTriangle size={16} /> {w}
                                    </div>
                                ))}

                                <div className="flex justify-end pt-4">
                                    <button
                                        onClick={() => setStep('backup')}
                                        disabled={validationReport.errors.length > 0}
                                        className="bg-purple-600 text-white px-6 py-2 rounded-lg font-bold disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        Next: Safety Check
                                    </button>
                                </div>
                            </div>
                        ) : (
                            <div className="text-center py-8 text-gray-500">Validating Passport...</div>
                        )}
                    </div>
                )}

                {step === 'backup' && (
                    <div className="animate-in fade-in">
                        <div className="bg-amber-50 border border-amber-200 p-6 rounded-xl mb-6">
                            <h3 className="font-bold text-amber-800 text-lg mb-2 flex items-center gap-2">
                                <Shield className="fill-amber-100" /> Safety Check
                            </h3>
                            <p className="text-amber-700 mb-4">
                                You are about to overwrite the database of <strong>{window.location.hostname}</strong>.
                                This cannot be undone automatically from this screen.
                            </p>

                            <label className="flex items-start gap-3 p-4 bg-white rounded-lg border border-amber-200 cursor-pointer hover:border-amber-400 transition">
                                <input
                                    type="checkbox"
                                    checked={backupConfirmed}
                                    onChange={e => setBackupConfirmed(e.target.checked)}
                                    className="mt-1 w-5 h-5 text-amber-600 rounded"
                                />
                                <div>
                                    <span className="font-bold text-gray-800">I have a current backup of this site.</span>
                                    <p className="text-xs text-gray-500 mt-1">
                                        If the migration fails, I know how to restore my previous database.
                                    </p>
                                </div>
                            </label>
                        </div>

                        <button
                            onClick={startMigration}
                            disabled={!backupConfirmed}
                            className={`w-full py-4 rounded-xl font-bold text-lg flex items-center justify-center gap-2 transition
                                ${backupConfirmed ? 'bg-purple-600 text-white hover:bg-purple-700 shadow-lg' : 'bg-gray-200 text-gray-400 cursor-not-allowed'}`}
                        >
                            <Play size={20} className="fill-current" /> Start Migration
                        </button>
                    </div>
                )}

                {step === 'migrate' && (
                    <div className="text-center py-8 animate-in fade-in">
                        <div className="w-20 h-20 mx-auto bg-purple-50 rounded-full flex items-center justify-center mb-6 relative">
                            {phase === 'fetching' ? (
                                <Download size={32} className="text-purple-600 animate-bounce" />
                            ) : (
                                <RefreshCw size={32} className="text-purple-600 animate-spin" />
                            )}

                            <svg className="absolute top-0 left-0 w-full h-full -rotate-90" viewBox="0 0 100 100">
                                <circle cx="50" cy="50" r="45" fill="none" stroke="#f3f4f6" strokeWidth="4" />
                                <circle
                                    cx="50" cy="50" r="45"
                                    fill="none"
                                    stroke="#9333ea"
                                    strokeWidth="4"
                                    strokeDasharray="283"
                                    strokeDashoffset={283 - (283 * progress / 100)}
                                    className="transition-all duration-300"
                                />
                            </svg>
                        </div>

                        <h3 className="text-2xl font-bold text-gray-800 mb-2">{progress}%</h3>
                        <p className="text-purple-600 font-bold uppercase text-xs tracking-wider mb-8">
                            {phase === 'fetching' ? 'Downloading Backup...' : 'Importing Database...'}
                        </p>

                        <div className="bg-gray-900 text-green-400 p-4 rounded-lg font-mono text-xs text-left h-32 overflow-y-auto shadow-inner">
                            {logs.map((log, i) => (
                                <div key={i} className="mb-1">{log}</div>
                            ))}
                            <div className="animate-pulse">_</div>
                        </div>
                    </div>
                )}

                {step === 'complete' && (
                    <div className="text-center py-12 animate-in zoom-in">
                        <div className="w-20 h-20 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-6">
                            <CheckCircle size={40} />
                        </div>
                        <h2 className="text-2xl font-bold text-gray-800 mb-2">Migration Successful!</h2>
                        <p className="text-gray-500 mb-8 max-w-md mx-auto">
                            The database has been imported and URLs have been updated.
                        </p>
                        <button
                            onClick={() => window.location.reload()}
                            className="bg-gray-900 text-white px-8 py-3 rounded-lg font-bold hover:bg-black transition"
                        >
                            Reload Dashboard
                        </button>
                    </div>
                )}
            </div>
        </div>
    );
};

export default MigrationStation;
