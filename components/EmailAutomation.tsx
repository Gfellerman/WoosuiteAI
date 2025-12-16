import React, { useState } from 'react';
import { generateMarketingContent } from '../services/geminiService';
import { Mail, Plus, Play, Pause, Trash2, Send, Sparkles, Megaphone } from 'lucide-react';
import { EmailRule } from '../types';

const mockRules: EmailRule[] = [
    { id: '1', name: 'Welcome Series', trigger: 'new_customer', subject: 'Welcome to the family!', body: 'Hi [Name], thanks for joining...', active: true },
    { id: '2', name: 'Order Confirmation', trigger: 'order_completed', subject: 'We received your order', body: 'Hi [Name], your order #[Order_ID] is confirmed...', active: true },
    { id: '3', name: 'Abandoned Cart Recovery', trigger: 'abandoned_cart', subject: 'Did you forget something?', body: 'Hi [Name], you left items in your cart...', active: false },
];

const EmailAutomation: React.FC = () => {
  const [activeTab, setActiveTab] = useState<'rules' | 'marketing'>('rules');
  const [rules, setRules] = useState<EmailRule[]>(mockRules);
  const [marketingTopic, setMarketingTopic] = useState('');
  const [marketingAudience, setMarketingAudience] = useState('');
  const [generatedContent, setGeneratedContent] = useState('');
  const [generating, setGenerating] = useState(false);

  const toggleRule = (id: string) => {
    setRules(rules.map(r => r.id === id ? { ...r, active: !r.active } : r));
  };

  const handleGenerateCampaign = async () => {
    if (!marketingTopic || !marketingAudience) return;
    setGenerating(true);
    try {
        const content = await generateMarketingContent(marketingTopic, marketingAudience, 'email');
        setGeneratedContent(content);
    } catch (e) {
        console.error(e);
    } finally {
        setGenerating(false);
    }
  };

  return (
    <div className="space-y-6">
       <div className="flex justify-between items-center">
        <div>
            <h2 className="text-2xl font-bold text-gray-800">Email & Marketing</h2>
            <p className="text-gray-500">Automate transactional emails and run AI-powered campaigns.</p>
        </div>
        <div className="flex bg-white p-1 rounded-lg border border-gray-200 shadow-sm">
            <button 
                onClick={() => setActiveTab('rules')}
                className={`px-4 py-2 text-sm font-medium rounded-md transition ${activeTab === 'rules' ? 'bg-purple-100 text-purple-700' : 'text-gray-600 hover:text-gray-900'}`}
            >
                Automation Rules
            </button>
            <button 
                onClick={() => setActiveTab('marketing')}
                className={`px-4 py-2 text-sm font-medium rounded-md transition ${activeTab === 'marketing' ? 'bg-purple-100 text-purple-700' : 'text-gray-600 hover:text-gray-900'}`}
            >
                Marketing Campaigns
            </button>
        </div>
      </div>

      {activeTab === 'rules' ? (
        <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
             <div className="p-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                 <h3 className="font-semibold text-gray-700">Active Rules</h3>
                 <button className="text-purple-600 text-sm font-medium flex items-center gap-1 hover:bg-purple-50 px-3 py-1.5 rounded transition">
                     <Plus size={16} /> Create Rule
                 </button>
             </div>
             <table className="w-full text-left">
                 <thead className="bg-white border-b border-gray-100">
                     <tr>
                         <th className="p-4 text-sm font-semibold text-gray-600">Rule Name</th>
                         <th className="p-4 text-sm font-semibold text-gray-600">Trigger</th>
                         <th className="p-4 text-sm font-semibold text-gray-600">Status</th>
                         <th className="p-4 text-sm font-semibold text-gray-600 text-right">Actions</th>
                     </tr>
                 </thead>
                 <tbody className="divide-y divide-gray-100">
                     {rules.map(rule => (
                         <tr key={rule.id} className="hover:bg-gray-50">
                             <td className="p-4">
                                 <div className="font-medium text-gray-800">{rule.name}</div>
                                 <div className="text-xs text-gray-500 truncate max-w-xs">{rule.subject}</div>
                             </td>
                             <td className="p-4">
                                 <span className="bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded-full font-mono">
                                     {rule.trigger}
                                 </span>
                             </td>
                             <td className="p-4">
                                 <button onClick={() => toggleRule(rule.id)} className={`flex items-center gap-1 text-xs font-medium px-2 py-1 rounded-full border transition ${rule.active ? 'bg-green-50 text-green-700 border-green-200' : 'bg-gray-50 text-gray-500 border-gray-200'}`}>
                                     {rule.active ? <Play size={10} fill="currentColor" /> : <Pause size={10} fill="currentColor" />}
                                     {rule.active ? 'Active' : 'Paused'}
                                 </button>
                             </td>
                             <td className="p-4 text-right">
                                 <button className="text-gray-400 hover:text-red-500 transition">
                                     <Trash2 size={16} />
                                 </button>
                             </td>
                         </tr>
                     ))}
                 </tbody>
             </table>
        </div>
      ) : (
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100 h-fit">
                <div className="flex items-center gap-2 mb-6 text-purple-600">
                    <Megaphone size={24} />
                    <h3 className="font-bold text-lg">New Campaign</h3>
                </div>
                
                <div className="space-y-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Campaign Topic</label>
                        <input 
                            type="text" 
                            className="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-purple-200 outline-none"
                            placeholder="e.g., Black Friday Sale, New Product Launch"
                            value={marketingTopic}
                            onChange={(e) => setMarketingTopic(e.target.value)}
                        />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Target Audience</label>
                        <select 
                            className="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-purple-200 outline-none"
                            value={marketingAudience}
                            onChange={(e) => setMarketingAudience(e.target.value)}
                        >
                            <option value="">Select Audience...</option>
                            <option value="All Customers">All Customers</option>
                            <option value="VIP Customers">VIP Customers (Spent $500+)</option>
                            <option value="New Subscribers">New Subscribers (Last 30 days)</option>
                            <option value="Inactive Users">Inactive Users (No purchase 90 days)</option>
                        </select>
                    </div>
                    <button 
                        onClick={handleGenerateCampaign}
                        disabled={generating || !marketingTopic || !marketingAudience}
                        className="w-full bg-purple-600 text-white py-2.5 rounded-lg font-medium hover:bg-purple-700 transition flex items-center justify-center gap-2 disabled:opacity-50"
                    >
                        {generating ? <Sparkles className="animate-spin" size={18} /> : <Sparkles size={18} />}
                        Generate Email Content
                    </button>
                </div>
            </div>

            <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100 min-h-[400px] flex flex-col">
                <h3 className="font-semibold text-gray-800 mb-4">Preview</h3>
                {generatedContent ? (
                    <>
                        <div className="flex-1 bg-gray-50 p-4 rounded-lg border border-gray-200 text-sm whitespace-pre-wrap font-sans text-gray-700 mb-4 overflow-y-auto max-h-[400px]">
                            {generatedContent}
                        </div>
                        <div className="flex gap-3 justify-end">
                            <button className="text-gray-500 hover:text-gray-700 text-sm font-medium">Discard</button>
                            <button 
                                className="bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-green-700 flex items-center gap-2"
                                onClick={() => alert("Campaign scheduled!")}
                            >
                                <Send size={16} /> Schedule Send
                            </button>
                        </div>
                    </>
                ) : (
                    <div className="flex-1 flex flex-col items-center justify-center text-gray-400 border-2 border-dashed border-gray-100 rounded-lg">
                        <Mail size={48} className="mb-2 opacity-20" />
                        <p>Campaign preview will appear here</p>
                    </div>
                )}
            </div>
        </div>
      )}
    </div>
  );
};

export default EmailAutomation;
