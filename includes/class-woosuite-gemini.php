<?php

class WooSuite_Gemini {

    private $api_key;
    private $api_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';

    public function __construct() {
        $this->api_key = get_option( 'woosuite_gemini_api_key', '' );
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
        $base64_data = base64_encode( $image_data );
        $mime_type = wp_remote_retrieve_header( $image_response, 'content-type' ) ?: 'image/jpeg';

        $prompt = "
            Analyze this image and generate SEO metadata.
            Filename context: $filename

            1. Alt Text: Descriptive, accessible, under 125 chars.
            2. Title: Short, catchy title for the image file.

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
        if ( $code !== 200 ) {
            return new WP_Error( 'api_error', 'Gemini API Error: ' . wp_remote_retrieve_body( $response ) );
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $data['candidates'][0]['content']['parts'][0]['text'] ) ) {
            return new WP_Error( 'api_empty', 'No response from Gemini.' );
        }

        $text = $data['candidates'][0]['content']['parts'][0]['text'];
        return json_decode( $text, true );
    }
}
