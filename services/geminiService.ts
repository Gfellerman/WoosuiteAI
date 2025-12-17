import { GoogleGenAI, Type } from "@google/genai";
import { ContentItem } from "../types";

const getAiClient = () => {
  // Prioritize the key from WordPress backend (woosuiteData)
  const wpKey = window.woosuiteData?.apiKey;
  // Fallback to localStorage or env for development
  const localKey = localStorage.getItem('gemini_api_key');
  const apiKey = wpKey || localKey || process.env.API_KEY;
  
  if (!apiKey) {
    throw new Error("API_KEY is not set. Please configure it in Settings.");
  }
  return new GoogleGenAI({ apiKey });
};

export const generateSeoMeta = async (item: ContentItem): Promise<{ title: string; description: string; llmSummary: string }> => {
  const ai = getAiClient();
  
  let context = `Type: ${item.type}\nName: ${item.name}\nDescription: ${item.description}`;
  if (item.price) context += `\nPrice: ${item.price}`;

  const prompt = `
    Generate SEO and LLM-optimized metadata for this content.
    
    ${context}
    
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
      title: `${item.name}`,
      description: `Learn more about ${item.name}.`,
      llmSummary: `${item.name}. ${item.description.substring(0, 100)}...`
    };
  }
};

export const generateImageSeo = async (imageUrl: string, fileName: string): Promise<{ altText: string; title: string }> => {
    const ai = getAiClient();

    try {
        // Fetch image and convert to Base64
        const imgRes = await fetch(imageUrl);
        const blob = await imgRes.blob();
        const base64Data = await new Promise<string>((resolve) => {
            const reader = new FileReader();
            reader.onloadend = () => resolve(reader.result as string);
            reader.readAsDataURL(blob);
        });
        const base64String = base64Data.split(',')[1];
        const mimeType = imgRes.headers.get('content-type') || 'image/jpeg';

        const prompt = `
            Analyze this image and generate SEO metadata.
            Filename context: ${fileName}

            1. Alt Text: Descriptive, accessible, under 125 chars.
            2. Title: Short, catchy title for the image file.

            Return JSON.
        `;

        const response = await ai.models.generateContent({
            model: "gemini-2.5-flash",
            contents: [
                { text: prompt },
                { inlineData: { mimeType: mimeType, data: base64String } }
            ],
            config: {
                responseMimeType: "application/json",
                responseSchema: {
                    type: Type.OBJECT,
                    properties: {
                        altText: { type: Type.STRING },
                        title: { type: Type.STRING }
                    },
                    required: ["altText", "title"]
                }
            }
        });

        const text = response.text;
        if (!text) throw new Error("No response from AI");
        return JSON.parse(text);

    } catch (error) {
        console.error("Image SEO Generation Error:", error);
        return {
            altText: fileName,
            title: fileName
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

export const performAiSearch = async (query: string, items: ContentItem[]): Promise<number[]> => {
  const ai = getAiClient();
  
  const itemsJson = JSON.stringify(items.map(p => ({
      id: p.id, 
      name: p.name, 
      desc: p.description,
      summary: p.llmSummary
  })));
  
  const prompt = `
    You are an intelligent search engine for an online store.
    User Query: "${query}"
    
    Available Content:
    ${itemsJson}
    
    Return a JSON object with a single key "matchedIds" which is an array of IDs that are relevant to the query.
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
