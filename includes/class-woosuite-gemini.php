<?php

class WooSuite_Gemini {

    private $api_key;
    private $api_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';

    public function __construct() {
        $this->api_key = get_option( 'woosuite_gemini_api_key', '' );
    }

    public function test_connection() {
        if ( empty( $this->api_key ) ) {
            return new WP_Error( 'missing_key', 'Gemini API Key is missing.' );
        }

        $body = array(
            'contents' => array(
                array(
                    'parts' => array(
                        array( 'text' => "Say 'Hello' if you can hear me." )
                    )
                )
            )
        );

        // Call the API but we want the raw response for debugging if possible
        $response = wp_remote_post( $this->api_url . '?key=' . $this->api_key, array(
            'headers' => array( 'Content-Type' => 'application/json' ),
            'body'    => json_encode( $body ),
            'timeout' => 30
        ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body_str = wp_remote_retrieve_body( $response );
        $data = json_decode( $body_str, true );

        if ( $code !== 200 ) {
            return new WP_Error( 'api_error', "Gemini API Error ($code): " . $body_str );
        }

        return array( 'status' => $code, 'raw_response' => $data );
    }

    public function generate_seo_meta( $item ) {
        if ( empty( $this->api_key ) ) {
            return new WP_Error( 'missing_key', 'Gemini API Key is missing.' );
        }

        $context = "Type: {$item['type']}\nName: {$item['name']}\nDescription: {$item['description']}";
        if ( isset( $item['price'] ) ) {
            $context .= "\nPrice: {$item['price']}";
        }

        $rewrite_prompt = "";
        $schema_props = array(
            'title' => array( 'type' => 'STRING' ),
            'description' => array( 'type' => 'STRING' ),
            'llmSummary' => array( 'type' => 'STRING' ),
        );
        $required_fields = array( 'title', 'description', 'llmSummary' );

        if ( ! empty( $item['rewrite_title'] ) ) {
            $rewrite_prompt = "4. SimplifiedTitle: A clean, concise product name (max 6 words). Remove keyword stuffing, specs, and clutter. E.g., 'Modern Velvet Office Chair'.";
            $schema_props['simplifiedTitle'] = array( 'type' => 'STRING' );
            $required_fields[] = 'simplifiedTitle';
        }

        $prompt = "
            Generate SEO and LLM-optimized metadata for this content.

            $context

            1. Title: Max 60 chars, keyword rich (Meta Title).
            2. Description: Max 160 chars, enticing click-through (Meta Description).
            3. LLM Summary: A concise, fact-dense summary (under 50 words) for AI Chatbots.
            $rewrite_prompt

            Return strictly JSON.
        ";

        $body = array(
            'contents' => array(
                array(
                    'parts' => array(
                        array( 'text' => $prompt )
                    )
                )
            ),
            'generationConfig' => array(
                'responseMimeType' => 'application/json',
                'responseSchema' => array(
                    'type' => 'OBJECT',
                    'properties' => $schema_props,
                    'required' => $required_fields
                )
            )
        );

        return $this->call_api( $body );
    }

    public function generate_image_seo( $url, $filename ) {
        if ( empty( $this->api_key ) ) {
            return new WP_Error( 'missing_key', 'Gemini API Key is missing.' );
        }

        // Fetch image (Server Side)
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

        // Sanitize filename for context: if it looks like a random hash, don't even show it to the AI
        $clean_filename = $filename;
        if ( preg_match( '/^[a-zA-Z0-9]{10,}\./', $filename ) || preg_match( '/\d{10,}/', $filename ) ) {
            $clean_filename = "Unknown (Ignore Filename)";
        }

        $prompt = "
            Analyze the visual content of this image to generate SEO metadata.

            Context (Filename): $clean_filename

            Instructions:
            1. Alt Text: Describe exactly what is visible in the image. Be specific and accessible. Max 125 chars.
            2. Title: Create a clean, descriptive title for the image based on its visual content.
               - STRICTLY IGNORE alphanumeric codes, random IDs, or camera file names (e.g., 'IMG_001', '07854fdsa').
               - If the filename is gibberish, look ONLY at the image.
               - Do not include file extensions like .jpg or .png.

            Return strictly JSON.
        ";

        $body = array(
            'contents' => array(
                array(
                    'parts' => array(
                        array( 'text' => $prompt ),
                        array(
                            'inlineData' => array(
                                'mimeType' => $mime_type,
                                'data' => $base64_data
                            )
                        )
                    )
                )
            ),
            'generationConfig' => array(
                'responseMimeType' => 'application/json',
                'responseSchema' => array(
                    'type' => 'OBJECT',
                    'properties' => array(
                        'altText' => array( 'type' => 'STRING' ),
                        'title' => array( 'type' => 'STRING' ),
                    ),
                    'required' => array( 'altText', 'title' )
                )
            )
        );

        return $this->call_api( $body );
    }

    private function call_api( $body ) {
        $response = wp_remote_post( $this->api_url . '?key=' . $this->api_key, array(
            'headers' => array( 'Content-Type' => 'application/json' ),
            'body'    => json_encode( $body ),
            'timeout' => 60
        ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );

        if ( $code === 429 ) {
            return new WP_Error( 'rate_limit', 'Gemini API Rate Limit Reached.' );
        }

        if ( $code !== 200 ) {
            $error_msg = wp_remote_retrieve_body( $response );
            // Log for debugging
            error_log('WooSuite Gemini Error: ' . $error_msg);
            return new WP_Error( 'api_error', 'Gemini API Error: ' . $code );
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $data['candidates'][0]['content']['parts'][0]['text'] ) ) {
            return new WP_Error( 'api_empty', 'No response from Gemini.' );
        }

        $text = $data['candidates'][0]['content']['parts'][0]['text'];
        $json = json_decode( $text, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
             return new WP_Error( 'json_error', 'Invalid JSON from Gemini: ' . $text );
        }

        return $json;
    }
}
