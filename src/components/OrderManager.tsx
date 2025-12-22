import React, { useState } from 'react';
import { Order } from '../types';
import { Mail, Send, X, MessageSquare } from 'lucide-react';

interface OrderManagerProps {
  orders: Order[];
}

const OrderManager: React.FC<OrderManagerProps> = ({ orders }) => {
  const [selectedOrder, setSelectedOrder] = useState<Order | null>(null);
  const [emailDraft, setEmailDraft] = useState('');
  const [generating, setGenerating] = useState(false);

  const handleOpenEmail = (order: Order) => {
    setSelectedOrder(order);
    setEmailDraft('');
  };

  const handleGenerateDraft = async () => {
    if (!selectedOrder) return;
    setGenerating(true);

    // Placeholder logic since Gemini Service is removed
    setTimeout(() => {
        const context = selectedOrder.customerNote 
            ? `Regarding your note: "${selectedOrder.customerNote}"`
            : `Order status is ${selectedOrder.status}`;

        const draft = `Dear ${selectedOrder.customer},\n\nThank you for your order #${selectedOrder.id}.\n${context}\n\nWe appreciate your business!\n\nBest regards,\nSupport Team`;
        
        setEmailDraft(draft);
        setGenerating(false);
    }, 800);
  };

  return (
    <div className="space-y-6">
       <div className="flex justify-between items-center">
        <div>
            <h2 className="text-2xl font-bold text-gray-800">Order Automation</h2>
            <p className="text-gray-500">Manage orders and automate customer communication.</p>
        </div>
      </div>

      <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table className="w-full text-left">
          <thead className="bg-gray-50 border-b border-gray-100">
            <tr>
              <th className="p-4 text-sm font-semibold text-gray-600">Order</th>
              <th className="p-4 text-sm font-semibold text-gray-600">Customer</th>
              <th className="p-4 text-sm font-semibold text-gray-600">Status</th>
              <th className="p-4 text-sm font-semibold text-gray-600">Total</th>
              <th className="p-4 text-sm font-semibold text-gray-600 text-right">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-100">
            {orders.map((order) => (
              <tr key={order.id} className="hover:bg-gray-50">
                <td className="p-4 font-mono text-gray-700">#{order.id}</td>
                <td className="p-4">
                    <div className="font-medium text-gray-800">{order.customer}</div>
                    {order.customerNote && (
                        <div className="text-xs text-amber-600 mt-1 flex items-center gap-1">
                            <MessageSquare size={10} /> Has Note
                        </div>
                    )}
                </td>
                <td className="p-4">
                  <span className={`px-2 py-1 rounded text-xs font-medium uppercase
                    ${order.status === 'completed' ? 'bg-green-100 text-green-700' : 
                      order.status === 'processing' ? 'bg-blue-100 text-blue-700' : 
                      order.status === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-700'}`}>
                    {order.status}
                  </span>
                </td>
                <td className="p-4 font-medium text-gray-700">${order.total.toFixed(2)}</td>
                <td className="p-4 text-right">
                  <button 
                    onClick={() => handleOpenEmail(order)}
                    className="text-purple-600 hover:text-purple-800 p-2 hover:bg-purple-50 rounded-lg transition"
                    title="Compose Email"
                  >
                    <Mail size={18} />
                  </button>
                </td>
              </tr>
            ))}
            {orders.length === 0 && (
                <tr><td colSpan={5} className="p-8 text-center text-gray-400">No orders found.</td></tr>
            )}
          </tbody>
        </table>
      </div>

      {/* Email Modal */}
      {selectedOrder && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-xl shadow-2xl max-w-lg w-full overflow-hidden animate-fade-in-up">
            <div className="bg-gray-50 px-6 py-4 border-b border-gray-100 flex justify-between items-center">
              <h3 className="font-semibold text-gray-800">Contact {selectedOrder.customer}</h3>
              <button onClick={() => setSelectedOrder(null)} className="text-gray-400 hover:text-gray-600">
                <X size={20} />
              </button>
            </div>
            <div className="p-6 space-y-4">
                <div className="bg-blue-50 p-3 rounded-lg text-sm text-blue-800 mb-4">
                    <strong>Context:</strong> {selectedOrder.customerNote || `Standard update for order #${selectedOrder.id}`}
                </div>
                
                <textarea 
                    className="w-full h-40 border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-purple-200 focus:border-purple-500 outline-none resize-none"
                    value={emailDraft}
                    onChange={(e) => setEmailDraft(e.target.value)}
                    placeholder="Type your message or generate one with AI..."
                />
                
                <div className="flex justify-between items-center">
                    <button 
                        onClick={handleGenerateDraft}
                        disabled={generating}
                        className="text-purple-600 text-sm font-medium hover:text-purple-800 flex items-center gap-1 disabled:opacity-50"
                    >
                        {generating ? (
                             <span className="inline-block animate-pulse">Generating...</span>
                        ) : (
                            <>
                                <div className="w-4 h-4 rounded-full bg-purple-100 flex items-center justify-center">✨</div>
                                Generate with AI
                            </>
                        )}
                    </button>
                    <button 
                        className="bg-purple-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-purple-700 transition flex items-center gap-2"
                        onClick={() => { alert('Email sent (mock)'); setSelectedOrder(null); }}
                    >
                        Send <Send size={14} />
                    </button>
                </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default OrderManager;
