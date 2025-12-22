import React, { useState } from 'react';
import { Product } from '../types';
import { Search, Sliders, Zap } from 'lucide-react';

interface AiSearchProps {
  products: Product[];
}

const AiSearch: React.FC<AiSearchProps> = ({ products }) => {
  const [query, setQuery] = useState('');
  const [results, setResults] = useState<Product[]>([]);
  const [loading, setLoading] = useState(false);
  const [searched, setSearched] = useState(false);

  const handleSearch = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!query.trim()) return;

    setLoading(true);
    setSearched(true);

    // Mock Delay
    setTimeout(() => {
        setResults([]);
        setLoading(false);
        alert("AI Search Simulator is temporarily disabled during the engine migration.");
    }, 1000);
  };

  return (
    <div className="space-y-6">
       <div className="flex justify-between items-center">
        <div>
            <h2 className="text-2xl font-bold text-gray-800">AI Search Engine</h2>
            <p className="text-gray-500">Semantic search configuration and testing playground.</p>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="lg:col-span-1 space-y-4">
             <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h3 className="font-semibold text-gray-800 mb-4 flex items-center gap-2">
                    <Sliders size={18} /> Configuration
                </h3>
                <div className="space-y-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Search Threshold</label>
                        <input type="range" className="w-full accent-purple-600" min="0" max="100" defaultValue="75" />
                        <div className="flex justify-between text-xs text-gray-400">
                            <span>Loose</span>
                            <span>Strict</span>
                        </div>
                    </div>
                    <div className="flex items-center justify-between">
                         <span className="text-sm text-gray-700">Synonym Matching</span>
                         <input type="checkbox" defaultChecked className="toggle-checkbox accent-purple-600" />
                    </div>
                     <div className="flex items-center justify-between">
                         <span className="text-sm text-gray-700">Typo Tolerance</span>
                         <input type="checkbox" defaultChecked className="toggle-checkbox accent-purple-600" />
                    </div>
                </div>
             </div>
        </div>

        <div className="lg:col-span-2">
            <div className="bg-white p-8 rounded-xl shadow-sm border border-gray-100 min-h-[400px]">
                <h3 className="font-semibold text-gray-800 mb-6 flex items-center gap-2">
                    <Zap size={18} className="text-yellow-500" /> Live Simulator
                </h3>
                
                <form onSubmit={handleSearch} className="relative mb-8">
                    <input 
                        type="text" 
                        value={query}
                        onChange={(e) => setQuery(e.target.value)}
                        placeholder="Try searching: 'something warm for winter' or 'gadgets for phone'" 
                        className="w-full pl-12 pr-4 py-3 rounded-lg border border-gray-200 focus:border-purple-500 focus:ring-2 focus:ring-purple-200 outline-none transition"
                    />
                    <Search className="absolute left-4 top-3.5 text-gray-400" size={20} />
                    <button 
                        type="submit"
                        disabled={loading}
                        className="absolute right-2 top-2 bg-purple-600 text-white px-4 py-1.5 rounded-md text-sm font-medium hover:bg-purple-700 transition disabled:opacity-50"
                    >
                        {loading ? 'Analyzing...' : 'Test Search'}
                    </button>
                </form>

                <div className="space-y-4">
                    {loading && (
                        <div className="text-center py-12 text-gray-400">
                            <div className="inline-block animate-spin rounded-full h-8 w-8 border-4 border-gray-200 border-t-purple-600 mb-2"></div>
                            <p>Analyzing semantic meaning...</p>
                        </div>
                    )}

                    {!loading && searched && results.length === 0 && (
                        <div className="text-center py-12 text-gray-400 bg-gray-50 rounded-lg border border-dashed border-gray-200">
                            <p>No products matched that query (Simulation Mode).</p>
                        </div>
                    )}

                    {!loading && results.map(product => (
                        <div key={product.id} className="flex items-center gap-4 p-4 border border-gray-100 rounded-lg hover:shadow-md transition bg-white animate-fade-in-up">
                            <div className="w-16 h-16 bg-gray-100 rounded-md flex-shrink-0 flex items-center justify-center text-gray-400">
                                Img
                            </div>
                            <div>
                                <h4 className="font-semibold text-gray-800">{product.name}</h4>
                                <p className="text-sm text-gray-500 line-clamp-1">{product.description}</p>
                                <div className="mt-1 font-medium text-purple-600">${product.price.toFixed(2)}</div>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </div>
      </div>
    </div>
  );
};

export default AiSearch;
