import { GoogleGenAI, Type } from "@google/genai";
import { Product } from "../types";

const getAiClient = () => {
  // Check for user-provided key in localStorage first (simulating plugin settings)
  const userKey = localStorage.getItem('gemini_api_key');
  const apiKey = userKey || process.env.API_KEY;
  
  if (!apiKey) {
    throw new Error("API_KEY is not set. Please configure it in Settings.");
  }
  return new GoogleGenAI({ apiKey });
};

export const generateSeoMeta = async (product: Product): Promise<{ title: string; description: string; llmSummary: string }> => {
  const ai = getAiClient();
  
  // Prompt updated for "LLM Optimization" (GEO - Generative Engine Optimization)
  // Focusing on factual density and clear structure for AI parsers.
  const prompt = `
    Generate SEO and LLM-optimized metadata for this product.
    
    Product: ${product.name}
    Description: ${product.description}
    Price: ${product.price}
    
    1. Title: Max 60 chars, keyword rich.
    2. Description: Max 160 chars, enticing click-through.
    3. LLM Summary: A concise, fact-dense summary (under 50 words) designed for AI Chatbots (ChatGPT/Gemini) to easily extract key features and specs.
    
    Return strictly JSON.
  `;

  try {
    const response = await ai.models.generateContent({
      model: "gemini-2.5-flash",
      contents: prompt,
      config: {
        responseMimeType: "application/json",
        responseSchema: {
          type: Type.OBJECT,
          properties: {
            title: { type: Type.STRING },
            description: { type: Type.STRING },
            llmSummary: { type: Type.STRING }
          },
          required: ["title", "description", "llmSummary"]
        }
      }
    });
    
    const text = response.text;
    if (!text) throw new Error("No response from AI");
    
    return JSON.parse(text);
  } catch (error) {
    console.error("SEO Generation Error:", error);
    return {
      title: `${product.name} - Official Store`,
      description: `Buy ${product.name}. High quality.`,
      llmSummary: `${product.name} is available for $${product.price}. Key features: ${product.description}.`
    };
  }
};

export const generateEmailResponse = async (customerName: string, orderId: number, issue: string): Promise<string> => {
  const ai = getAiClient();

  const prompt = `
    Write a polite, professional customer service email response.
    Customer Name: ${customerName}
    Order ID: #${orderId}
    Context/Issue: ${issue}
    
    Keep it concise (under 100 words). Do not include subject line.
  `;

  try {
    const response = await ai.models.generateContent({
      model: "gemini-2.5-flash",
      contents: prompt,
    });
    return response.text || "Could not generate email.";
  } catch (error) {
    console.error("Email Generation Error:", error);
    return "Dear Customer, thank you for your message. We are looking into your order.";
  }
};

export const generateMarketingContent = async (topic: string, audience: string, type: 'email' | 'social'): Promise<string> => {
    const ai = getAiClient();
    const prompt = `
      Create a ${type} marketing campaign message.
      Topic/Event: ${topic}
      Target Audience: ${audience}
      
      Tone: Persuasive, Urgent, Exciting.
      Format: HTML Body (no html/body tags, just the content).
    `;

    try {
        const response = await ai.models.generateContent({
            model: "gemini-2.5-flash",
            contents: prompt,
        });
        return response.text || "";
    } catch (error) {
        return "Failed to generate marketing content.";
    }
}

export const performAiSearch = async (query: string, products: Product[]): Promise<number[]> => {
  const ai = getAiClient();
  
  const productsJson = JSON.stringify(products.map(p => ({ 
      id: p.id, 
      name: p.name, 
      desc: p.description,
      summary: p.llmSummary // Include the LLM summary for better matching
  })));
  
  const prompt = `
    You are an intelligent search engine for an online store.
    User Query: "${query}"
    
    Available Products:
    ${productsJson}
    
    Return a JSON object with a single key "matchedIds" which is an array of product IDs that are relevant to the query. 
    Rank them by relevance. If no matches, return empty array.
  `;

  try {
    const response = await ai.models.generateContent({
      model: "gemini-2.5-flash",
      contents: prompt,
      config: {
        responseMimeType: "application/json",
        responseSchema: {
            type: Type.OBJECT,
            properties: {
                matchedIds: {
                    type: Type.ARRAY,
                    items: { type: Type.NUMBER }
                }
            }
        }
      }
    });

    const text = response.text;
    if (!text) return [];
    
    const result = JSON.parse(text);
    return result.matchedIds || [];
  } catch (error) {
    console.error("AI Search Error:", error);
    return [];
  }
};
