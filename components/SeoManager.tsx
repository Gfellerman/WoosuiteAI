import React, { useState } from 'react';
import { Product } from '../types';
import { generateSeoMeta } from '../services/geminiService';
import { Sparkles, Check, AlertCircle, RefreshCw, Bot } from 'lucide-react';

interface SeoManagerProps {
  products: Product[];
  onUpdateProduct: (product: Product) => void;
}

const SeoManager: React.FC<SeoManagerProps> = ({ products, onUpdateProduct }) => {
  const [generating, setGenerating] = useState<number | null>(null);
  const { apiUrl, nonce } = window.woosuiteData || {};

  const handleGenerate = async (product: Product) => {
    setGenerating(product.id);
    try {
      const result = await generateSeoMeta(product);

      // Save to Backend
      if (apiUrl && nonce) {
          try {
              const saveRes = await fetch(`${apiUrl}/products/${product.id}`, {
                  method: 'POST',
                  headers: {
                      'Content-Type': 'application/json',
                      'X-WP-Nonce': nonce
                  },
                  body: JSON.stringify({
                      metaTitle: result.title,
                      metaDescription: result.description,
                      llmSummary: result.llmSummary
                  })
              });

              if (!saveRes.ok) {
                  console.warn("Failed to persist SEO data to backend.");
              }
          } catch (err) {
              console.error("API Error:", err);
          }
      }

      // Update UI
      onUpdateProduct({
        ...product,
        metaTitle: result.title,
        metaDescription: result.description,
        llmSummary: result.llmSummary
      });
    } catch (e) {
      console.error(e);
      alert("Failed to generate SEO data. Check API Key in Settings.");
    } finally {
      setGenerating(null);
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <div>
            <h2 className="text-2xl font-bold text-gray-800">AI SEO Manager</h2>
            <p className="text-gray-500">Optimize meta tags and generate LLM-ready data (GEO).</p>
        </div>
        <div className="flex gap-2">
            <button className="bg-white border border-gray-300 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition">
                Sitemap Settings
            </button>
             <button className="bg-purple-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-purple-700 transition shadow-sm">
                Bulk Optimize
            </button>
        </div>
      </div>

      <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table className="w-full text-left">
          <thead className="bg-gray-50 border-b border-gray-100">
            <tr>
              <th className="p-4 font-semibold text-gray-600 text-sm">Product</th>
              <th className="p-4 font-semibold text-gray-600 text-sm">Status</th>
              <th className="p-4 font-semibold text-gray-600 text-sm">LLM Optimization (GEO)</th>
              <th className="p-4 font-semibold text-gray-600 text-sm text-right">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-100">
            {products.map((product) => (
              <tr key={product.id} className="hover:bg-gray-50 transition">
                <td className="p-4 align-top w-1/4">
                  <div className="font-medium text-gray-800">{product.name}</div>
                  <div className="text-xs text-gray-500 mt-1 line-clamp-1">{product.description}</div>
                </td>
                <td className="p-4 align-top w-1/6">
                  {product.metaTitle ? (
                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                      <Check size={12} className="mr-1" /> Optimized
                    </span>
                  ) : (
                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                      <AlertCircle size={12} className="mr-1" /> Missing
                    </span>
                  )}
                </td>
                <td className="p-4 align-top">
                   {product.llmSummary ? (
                       <div className="space-y-2">
                           <div className="text-xs font-semibold text-gray-500 flex items-center gap-1">
                               <Bot size={12} /> AI/LLM Summary:
                           </div>
                           <div className="text-sm text-gray-700 bg-gray-50 p-2 rounded border border-gray-100">
                               {product.llmSummary}
                           </div>
                           <div className="text-xs text-blue-600 truncate">{product.metaTitle}</div>
                       </div>
                   ) : (
                       <div className="text-sm text-gray-400 italic">No AI-optimized data yet...</div>
                   )}
                </td>
                <td className="p-4 align-top text-right w-1/6">
                  <button
                    onClick={() => handleGenerate(product)}
                    disabled={generating === product.id}
                    className={`inline-flex items-center justify-center px-3 py-1.5 rounded-lg text-sm font-medium transition
                        ${generating === product.id 
                            ? 'bg-gray-100 text-gray-400 cursor-not-allowed' 
                            : 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100'
                        }`}
                  >
                    {generating === product.id ? (
                      <RefreshCw size={14} className="animate-spin mr-1.5" />
                    ) : (
                      <Sparkles size={14} className="mr-1.5" />
                    )}
                    {generating === product.id ? 'Thinking...' : 'Generate'}
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
};

export default SeoManager;
