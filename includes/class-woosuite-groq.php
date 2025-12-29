<?php

class WooSuite_Groq {

    private $api_key;
    private $api_url = 'https://api.groq.com/openai/v1/chat/completions';

    // Model Constants
    const MODEL_MAIN = 'meta-llama/llama-4-scout-17b-16e-instruct'; // Preferred
    const MODEL_FALLBACK = 'llama-3.1-8b-instant'; // Safe fallback for connection testing
    const MODEL_GUARD = 'meta-llama/llama-guard-4-12b';
    const MODEL_VISION = 'llama-3.2-11b-vision-preview'; // Only vision model available currently

    public function __construct( $api_key = null ) {
        // Allow passing key explicitly for testing connection before saving
        if ( ! empty( $api_key ) ) {
            $this->api_key = $api_key;
        } else {
            // We reuse the existing option key to preserve user input
            // if they already pasted the Groq key into the 'Gemini' field.
            $this->api_key = get_option( 'woosuite_gemini_api_key', '' );
        }
    }

    public function test_connection() {
        if ( empty( $this->api_key ) ) {
            return new WP_Error( 'missing_key', 'Groq API Key is missing.' );
        }

        // 1. Try Main Model
        $body = array(
            'model' => self::MODEL_MAIN,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => "Say 'Hello' if you can hear me."
                )
            ),
            'max_tokens' => 10
        );

        $result = $this->call_api( $body, false );

        if ( ! is_wp_error( $result ) ) {
            return $result; // Success with main model
        }

        // 2. If Main Model fails (e.g. 404 Model Not Found), try Fallback Model
        // This ensures we can tell the user "Key is Valid" even if the specific model ID is wrong/restricted.
        $error_code = $result->get_error_code();
        $error_msg = $result->get_error_message();

        // Groq returns 404 for invalid model, 401 for invalid key.
        if ( strpos( $error_msg, '404' ) !== false || strpos( $error_msg, 'model_not_found' ) !== false ) {
             $body['model'] = self::MODEL_FALLBACK;
             $fallback_result = $this->call_api( $body, false );

             if ( ! is_wp_error( $fallback_result ) ) {
                 // Return success but with a warning data
                 return array(
                     'status' => 200,
                     'content' => 'Connection Successful (Fallback Model Used)',
                     'warning' => "The primary model (" . self::MODEL_MAIN . ") was not found or is restricted. Using " . self::MODEL_FALLBACK . " instead."
                 );
             }
        }

        return $result;
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
            1. Title: Keyword rich, max 60 chars.
            2. Description: Enticing, max 160 chars.
            3. LLM Summary: Fact-dense, under 50 words.
            4. Tags: Relevant keywords, max 5, comma separated.
            $rewrite_instruction

            Return strictly JSON matching this structure:
            $json_structure
        ";

        $body = array(
            'model' => self::MODEL_MAIN,
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

    public function analyze_security_logs( $logs_summary ) {
        if ( empty( $this->api_key ) ) {
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
            'model' => self::MODEL_MAIN,
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
        if ( empty( $this->api_key ) ) {
            return new WP_Error( 'missing_key', 'Groq API Key is missing.' );
        }

        // Truncate snippet if too long (just to be safe, though API limits it too)
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
                \"explanation\": \"Concise explanation (max 50 words) suitable for a non-technical user.\"
            }
        ";

        // Use MAIN model (Scout 17b) as it is better at reasoning than Guard for explanation.
        // Guard is for safety classification (Input/Output), not malware analysis per se.
        $body = array(
            'model' => self::MODEL_MAIN,
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
        if ( empty( $this->api_key ) ) {
            return new WP_Error( 'missing_key', 'Groq API Key is missing.' );
        }

        // 1. Check Image Size (Prevent Memory Exhaustion)
        // Limit to 4MB (Groq Vision Limit & PHP Memory Safety)
        $head = wp_remote_head( $url, array( 'timeout' => 5, 'sslverify' => false ) );
        if ( ! is_wp_error( $head ) ) {
             $size = wp_remote_retrieve_header( $head, 'content-length' );
             if ( $size && $size > 4194304 ) { // 4MB
                 return new WP_Error( 'image_too_large', 'Image is too large for AI analysis (>4MB). Skipping.' );
             }
        }

        // 2. Fetch image (Server Side) to convert to base64
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

        // Use Llama 3.2 Vision (11b) for image analysis (only one supporting images currently)
        $body = array(
            'model' => self::MODEL_VISION,
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
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $raw_body = wp_remote_retrieve_body( $response );

        if ( $code === 429 ) {
            return new WP_Error( 'rate_limit', 'Groq API Rate Limit Reached.' );
        }

        if ( $code !== 200 ) {
            error_log('WooSuite Groq Error: ' . $raw_body);
            return new WP_Error( 'api_error', 'Groq API Error: ' . $code . ' - ' . $raw_body );
        }

        $data = json_decode( $raw_body, true );

        if ( empty( $data['choices'][0]['message']['content'] ) ) {
            return new WP_Error( 'api_empty', 'No response content from Groq.' );
        }

        $content = $data['choices'][0]['message']['content'];

        if ( $json_mode ) {
            // Updated Robust JSON Extraction for Llama 4
            $extracted_json = $this->extract_json_from_text( $content );

            // Attempt basic decode
            $json = json_decode( $extracted_json, true );

            // If basic decode fails, try to clean up common LLM JSON errors (like trailing commas)
            if ( json_last_error() !== JSON_ERROR_NONE ) {
                $cleaned_json = $this->cleanup_json_syntax( $extracted_json );
                $json = json_decode( $cleaned_json, true );
            }

            if ( json_last_error() !== JSON_ERROR_NONE ) {
                 // Debug log for failed extractions
                 error_log( 'WooSuite JSON Fail. Error: ' . json_last_error_msg() );
                 error_log( 'Original: ' . $content );
                 error_log( 'Extracted: ' . $extracted_json );
                 return new WP_Error( 'json_error', 'Invalid JSON from Groq. See System Logs for details.' );
            }
            return $json;
        }

        return array( 'status' => $code, 'raw_response' => $data, 'content' => $content );
    }

    /**
     * Extracts strictly the JSON part from a text response.
     * Handles Markdown code blocks and conversational text.
     */
    private function extract_json_from_text( $text ) {
        // 1. Try to find JSON block inside Markdown code block (```json ... ``` or ``` ... ```)
        // using 's' modifier for multiline support (DOTALL)
        if ( preg_match( '/```(?:json)?\s*(\{.*?\})\s*```/s', $text, $matches ) ) {
            return $matches[1];
        }

        // 2. Brute-force JSON finder (Recursive-ish loop)
        // This handles cases where there is a preamble with braces before the actual JSON object.
        // e.g., "I used the context {foo} to generate: { "title": ... }"

        $offset = 0;
        $max_attempts = 5; // Prevent infinite loops on massive text
        $attempt = 0;

        while ( ($start = strpos( $text, '{', $offset )) !== false && $attempt < $max_attempts ) {
            $end = strrpos( $text, '}' ); // Look for the last brace in the WHOLE string

            if ( $end !== false && $end > $start ) {
                $candidate = substr( $text, $start, $end - $start + 1 );

                // Fast Validation Check before full decode (Optimization)
                // We expect specific keys based on the task, but let's just trust json_decode for now.
                // Or better, check if the candidate *looks* like our expected schema?
                // No, generic JSON validation is safer.

                // Attempt decode
                json_decode( $candidate );
                if ( json_last_error() === JSON_ERROR_NONE ) {
                    return $candidate;
                }
            }

            // If failed, try finding the next opening brace
            $offset = $start + 1;
            $attempt++;
        }

        // 3. Fallback: Return original if nothing found (or if simple case)
        $start = strpos( $text, '{' );
        $end = strrpos( $text, '}' );
        if ( $start !== false && $end !== false && $end > $start ) {
             return substr( $text, $start, $end - $start + 1 );
        }

        return $text;
    }

    /**
     * Cleans common JSON syntax errors from LLM output
     */
    private function cleanup_json_syntax( $json ) {
        // Remove trailing commas before closing braces/brackets
        // Regex: , (whitespace) }  ->  }
        $json = preg_replace( '/,\s*([\}\]])/', '$1', $json );

        // Ensure keys are quoted (if missing) - Simple case
        // $json = preg_replace( '/([{,]\s*)([a-zA-Z0-9_]+)(\s*:)/', '$1"$2"$3', $json );
        // (Commented out as it's risky with content containing colons, but trailing commas are the main culprit)

        return $json;
    }
}
