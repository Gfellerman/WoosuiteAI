<?php

class WooSuite_Groq {

    private $api_key;
    private $api_url = 'https://api.groq.com/openai/v1/chat/completions';
    private $model_id;

    // Model Constants
    const MODEL_MAIN = 'llama-3.1-8b-instant'; // Fast, high limits (Production)
    const MODEL_HIGH_QUALITY = 'llama-3.3-70b-versatile'; // Fallback / Premium
    const MODEL_TEST = 'llama-3.1-8b-instant'; // Fast, for connection testing
    const MODEL_VISION = 'llama-3.2-11b-vision-preview'; // Vision
    const MODEL_GUARD = 'meta-llama/llama-guard-4-12b'; // Safety

    public function __construct( $api_key = null ) {
        // Allow passing key explicitly for testing connection before saving
        if ( ! empty( $api_key ) ) {
            $this->api_key = $api_key;
        } else {
            // We reuse the existing option key to preserve user input
            $this->api_key = get_option( 'woosuite_gemini_api_key', '' );
        }

        // Handle Custom API / BYO-LLM
        $use_custom = get_option( 'woosuite_use_custom_api', 'no' ) === 'yes';
        if ( $use_custom ) {
            $custom_url = get_option( 'woosuite_api_url_custom', '' );
            if ( ! empty( $custom_url ) ) {
                $this->api_url = rtrim( $custom_url, '/' );
                // If user didn't append /chat/completions, we might need to?
                // Standard OpenAI compatible endpoints usually end in v1/chat/completions
                // But user might paste the full URL. Let's assume full URL for flexibility,
                // or appending if it looks like a base URL.
                // For now, trust the user pasted the full endpoint or we stick to standard if it's just a base.
            }

            $custom_model = get_option( 'woosuite_api_model_custom', '' );
            if ( ! empty( $custom_model ) ) {
                $this->model_id = $custom_model;
            }
        }
    }

    private function get_model( $default_model ) {
        return ! empty( $this->model_id ) ? $this->model_id : $default_model;
    }

    public function test_connection() {
        if ( empty( $this->api_key ) ) {
            return new WP_Error( 'missing_key', 'Groq API Key is missing or empty.' );
        }

        // Use the most basic/stable model for connection testing
        // This avoids issues where a key might be valid but not have access to preview models
        $body = array(
            'model' => $this->get_model( self::MODEL_TEST ),
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => "Ping"
                )
            ),
            'max_tokens' => 5
        );

        return $this->call_api( $body, false );
    }

    public function generate_seo_meta( $item ) {
        if ( empty( $this->api_key ) ) {
            return new WP_Error( 'missing_key', 'Groq API Key is missing.' );
        }

        $context = "Type: {$item['type']}\nName: {$item['name']}\nDescription: {$item['description']}";
        if ( isset( $item['price'] ) ) {
            $context .= "\nPrice: {$item['price']}";
        }

        $rewrite_instruction = "";
        $json_structure = "
        {
            \"title\": \"Max 60 chars meta title\",
            \"description\": \"Max 160 chars meta description\",
            \"llmSummary\": \"Concise summary for AI (max 50 words)\",
            \"tags\": \"comma, separated, tags, max 5\"
        }";

        if ( ! empty( $item['rewrite_title'] ) ) {
            $rewrite_instruction = "5. simplifiedTitle: A clean, concise product name (max 6 words). Remove keyword stuffing. E.g., 'Modern Velvet Office Chair'.";
            $json_structure = "
            {
                \"title\": \"Max 60 chars meta title\",
                \"description\": \"Max 160 chars meta description\",
                \"llmSummary\": \"Concise summary for AI (max 50 words)\",
                \"tags\": \"comma, separated, tags, max 5\",
                \"simplifiedTitle\": \"Clean product name\"
            }";
        }

        $prompt = "
            You are an SEO expert. Generate metadata for this content.

            $context

            Instructions:
            1. Title: Keyword rich, max 60 chars. Summarize the product name.
            2. Description: Enticing, max 160 chars.
            3. LLM Summary: Fact-dense, under 50 words.
            4. Tags: Relevant keywords, max 5, comma separated.
            $rewrite_instruction

            NEGATIVE CONSTRAINTS (CRITICAL):
            - Do NOT include shipping, warranty, or logistics info (e.g. 'DHL', 'Free Shipping').
            - Do NOT include competitor names (e.g. 'Amazon', 'eBay', 'Walmart').
            - Do NOT include random numbers or SKU codes unless part of the model name.

            Return strictly JSON matching this structure:
            $json_structure
        ";

        $body = array(
            'model' => $this->get_model( self::MODEL_MAIN ),
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => 'You are a helpful SEO assistant that outputs strictly JSON.'
                ),
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'response_format' => array( 'type' => 'json_object' )
        );

        return $this->call_api( $body, true );
    }

    public function analyze_firewall_logs( $logs_summary ) {
        if ( empty( $this->api_key ) && strpos( $this->api_url, 'groq.com' ) !== false ) {
            return new WP_Error( 'missing_key', 'Groq API Key is missing.' );
        }

        $prompt = "
            You are a firewall expert. Analyze these blocked requests and suggest IP bans or rule changes.

            Blocked Requests Summary:
            \"$logs_summary\"

            Task:
            1. Identify persistent attackers (IPs with multiple malicious attempts).
            2. Distinguish between random bots and targeted attacks.
            3. Recommend IPs to PERMANENTLY BAN.

            Output strictly JSON:
            {
                \"analysis\": \"Concise analysis of the attack patterns.\",
                \"suggestedBans\": [
                    { \"ip\": \"1.2.3.4\", \"reason\": \"SQL Injection Attempt\", \"confidence\": \"High\" }
                ]
            }
        ";

        $body = array(
            'model' => $this->get_model( self::MODEL_MAIN ),
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => 'You are a cybersecurity expert. Output strictly JSON.'
                ),
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'response_format' => array( 'type' => 'json_object' )
        );

        return $this->call_api( $body, true );
    }

    public function analyze_security_logs( $logs_summary ) {
        // If Custom API is used, we might not need an API key (e.g. Ollama local)
        // But for Groq it is required.
        // We relax the check if custom URL is set.
        if ( empty( $this->api_key ) && strpos( $this->api_url, 'groq.com' ) !== false ) {
            return new WP_Error( 'missing_key', 'Groq API Key is missing.' );
        }

        $prompt = "
            You are a WordPress Security Analyst. Analyze these recent security events and provide actionable insights.

            Security Events Summary:
            \"$logs_summary\"

            Task:
            1. Identify if there is an active attack (e.g., Brute Force, SQLi campaign).
            2. Assess the overall threat level (Low, Medium, Critical).
            3. Recommend 2-3 specific actions the user should take.

            Output strictly JSON:
            {
                \"verdict\": \"Safe\" | \"Under Attack\" | \"Suspicious Activity\",
                \"threatLevel\": \"Low\" | \"Medium\" | \"Critical\",
                \"summary\": \"Concise summary of what is happening (max 50 words).\",
                \"actions\": [\"Action 1\", \"Action 2\"]
            }
        ";

        $body = array(
            'model' => $this->get_model( self::MODEL_MAIN ),
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => 'You are a cybersecurity expert. Output strictly JSON.'
                ),
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'response_format' => array( 'type' => 'json_object' )
        );

        return $this->call_api( $body, true );
    }

    public function rewrite_content( $text, $type, $tone, $instructions = '', $context = '' ) {
        if ( empty( $this->api_key ) ) {
            return new WP_Error( 'missing_key', 'Groq API Key is missing.' );
        }

        $tone_instruction = "Tone: $tone.";
        if ( stripos( $tone, 'Technical' ) !== false ) {
            $tone_instruction .= " Use technical terminology, bullet points for specifications (if applicable), and concise, objective language. Focus on features and specs.";
        }

        $prompt = "
            Task: Rewrite the following $type.
            $tone_instruction

            CRITICAL USER DEMAND: $instructions

            NEGATIVE CONSTRAINTS (Strictly Enforce):
            - Do NOT include any shipping details (e.g. 'DHL', 'Fast Delivery', 'Free Shipping').
            - Do NOT include warranty or return policy info.
            - Do NOT mention competitors (Amazon, eBay, AliExpress, Walmart).
            - Do NOT mention prices or promotions.

            Context (Product Name/Title): \"$context\"

            Original Text:
            \"$text\"

            CRITICAL VALIDATION:
            The 'Original Text' might be incorrect or placeholder data (e.g. describing 'Fashion' for a 'USB Drive').
            Always prioritize the 'Context' (Name) as the source of truth.
            If the Original Text conflicts with the Context, IGNORE the Original Text and generate new content based on the Context.

            Return strictly JSON: { \"rewritten\": \"...\" }
        ";

        $body = array(
            'model' => self::MODEL_MAIN,
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => 'You are an expert technical copywriter. Output strictly JSON.'
                ),
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'response_format' => array( 'type' => 'json_object' )
        );

        return $this->call_api( $body, true );
    }

    public function analyze_security_threat( $code_snippet, $filename ) {
        if ( empty( $this->api_key ) && strpos( $this->api_url, 'groq.com' ) !== false ) {
            return new WP_Error( 'missing_key', 'Groq API Key is missing.' );
        }

        // Truncate snippet if too long
        $snippet = substr( $code_snippet, 0, 4000 );

        $prompt = "
            You are a WordPress Security Analyst. Analyze this code snippet found in a file named '$filename'.

            Code Snippet:
            \"$snippet\"

            Task: Determine if this code is Malicious, Suspicious, or Safe.
            Context: It was flagged by a regex scanner (e.g. for 'eval' or 'base64_decode').

            Output strictly JSON:
            {
                \"verdict\": \"Safe\" | \"Suspicious\" | \"Malicious\",
                \"confidence\": \"High\" | \"Medium\" | \"Low\",
                \"explanation\": \"Concise explanation (max 50 words) suitable for a non-technical user. Explain WHAT the code does and WHY it is flagged.\"
            }
        ";

        $body = array(
            'model' => $this->get_model( self::MODEL_MAIN ),
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => 'You are a cybersecurity expert. Output strictly JSON.'
                ),
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'response_format' => array( 'type' => 'json_object' )
        );

        return $this->call_api( $body, true );
    }

    public function generate_image_seo( $url, $filename, $product_context = null ) {
        if ( empty( $this->api_key ) && strpos( $this->api_url, 'groq.com' ) !== false ) {
            return new WP_Error( 'missing_key', 'Groq API Key is missing.' );
        }

        // 1. Check Image Size
        $head = wp_remote_head( $url, array( 'timeout' => 5, 'sslverify' => false ) );
        if ( ! is_wp_error( $head ) ) {
             $size = wp_remote_retrieve_header( $head, 'content-length' );
             if ( $size && $size > 4194304 ) { // 4MB
                 return new WP_Error( 'image_too_large', 'Image is too large for AI analysis (>4MB). Skipping.' );
             }
        }

        // 2. Fetch image
        $image_response = wp_remote_get( $url, array( 'timeout' => 20, 'sslverify' => false ) );
        if ( is_wp_error( $image_response ) || wp_remote_retrieve_response_code( $image_response ) !== 200 ) {
            return new WP_Error( 'image_fetch_error', 'Could not fetch image from server: ' . $url );
        }

        $image_data = wp_remote_retrieve_body( $image_response );
        if ( empty( $image_data ) ) {
            return new WP_Error( 'image_empty', 'Image data is empty.' );
        }

        $base64_data = base64_encode( $image_data );
        $mime_type = wp_remote_retrieve_header( $image_response, 'content-type' ) ?: 'image/jpeg';
        $data_url = "data:$mime_type;base64,$base64_data";

        // Sanitize filename
        $clean_filename = $filename;
        if ( preg_match( '/^[a-zA-Z0-9]{10,}\./', $filename ) || preg_match( '/\d{10,}/', $filename ) ) {
            $clean_filename = "Unknown (Ignore Filename)";
        }

        $prompt = "
            Analyze this image. Context Filename: $clean_filename.

            1. Alt Text: Specific, accessible description. Max 125 chars.
            2. Title: Clean, descriptive title. IGNORE filename if random/gibberish.

            Return strictly JSON: { \"altText\": \"...\", \"title\": \"...\" }
        ";

        $body = array(
            'model' => $this->get_model( self::MODEL_VISION ),
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => array(
                        array(
                            'type' => 'text',
                            'text' => $prompt
                        ),
                        array(
                            'type' => 'image_url',
                            'image_url' => array(
                                'url' => $data_url
                            )
                        )
                    )
                )
            ),
            'response_format' => array( 'type' => 'json_object' )
        );

        return $this->call_api( $body, true );
    }

    private function log_error( $message ) {
        $logs = get_option( 'woosuite_debug_log', array() );
        $timestamp = date( 'Y-m-d H:i:s' );
        array_unshift( $logs, "[$timestamp] [ERROR] $message" );
        if ( count( $logs ) > 50 ) {
            $logs = array_slice( $logs, 0, 50 );
        }
        update_option( 'woosuite_debug_log', $logs );
    }

    private function call_api( $body, $json_mode = true ) {
        $response = wp_remote_post( $this->api_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key
            ),
            'body'    => json_encode( $body ),
            'timeout' => 60
        ) );

        if ( is_wp_error( $response ) ) {
            $this->log_error( 'Connection Error: ' . $response->get_error_message() );
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $raw_body = wp_remote_retrieve_body( $response );

        if ( $code === 429 ) {
            $this->log_error( 'Groq Rate Limit Reached (429).' );
            return new WP_Error( 'rate_limit', 'Groq API Rate Limit Reached. Please wait a moment.' );
        }

        if ( $code !== 200 ) {
            $this->log_error( 'API Error (' . $code . '): ' . substr( $raw_body, 0, 200 ) );
            return new WP_Error( 'api_error', 'Groq API Error: ' . $code . ' - ' . $raw_body );
        }

        $data = json_decode( $raw_body, true );

        if ( empty( $data['choices'][0]['message']['content'] ) ) {
            $this->log_error( 'Empty response content from API.' );
            return new WP_Error( 'api_empty', 'No response content from Groq.' );
        }

        $content = $data['choices'][0]['message']['content'];

        if ( $json_mode ) {
            $extracted_json = $this->extract_json_from_text( $content );
            $json = json_decode( $extracted_json, true );

            if ( json_last_error() !== JSON_ERROR_NONE ) {
                $cleaned_json = $this->cleanup_json_syntax( $extracted_json );
                $json = json_decode( $cleaned_json, true );
            }

            if ( json_last_error() !== JSON_ERROR_NONE ) {
                 $this->log_error( 'JSON Parse Fail: ' . json_last_error_msg() );
                 error_log( 'WooSuite JSON Fail. Original: ' . $content );
                 return new WP_Error( 'json_error', 'Invalid JSON from Groq.' );
            }
            return $json;
        }

        return array( 'status' => $code, 'raw_response' => $data, 'content' => $content );
    }

    private function extract_json_from_text( $text ) {
        if ( preg_match( '/```(?:json)?\s*(\{.*?\})\s*```/s', $text, $matches ) ) {
            return $matches[1];
        }

        $offset = 0;
        $max_attempts = 5;
        $attempt = 0;

        while ( ($start = strpos( $text, '{', $offset )) !== false && $attempt < $max_attempts ) {
            $end = strrpos( $text, '}' );
            if ( $end !== false && $end > $start ) {
                $candidate = substr( $text, $start, $end - $start + 1 );
                json_decode( $candidate );
                if ( json_last_error() === JSON_ERROR_NONE ) {
                    return $candidate;
                }
            }
            $offset = $start + 1;
            $attempt++;
        }

        $start = strpos( $text, '{' );
        $end = strrpos( $text, '}' );
        if ( $start !== false && $end !== false && $end > $start ) {
             return substr( $text, $start, $end - $start + 1 );
        }

        return $text;
    }

    private function cleanup_json_syntax( $json ) {
        $json = preg_replace( '/,\s*([\}\]])/', '$1', $json );
        return $json;
    }
}
