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

        $prompt = "
            Generate SEO and LLM-optimized metadata for this content.

            $context

            1. Title: Max 60 chars, keyword rich.
            2. Description: Max 160 chars, enticing click-through.
            3. LLM Summary: A concise, fact-dense summary (under 50 words) designed for AI Chatbots (ChatGPT/Gemini) to easily extract key features and specs.

            Return strictly JSON with keys: title, description, llmSummary.
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
                    'properties' => array(
                        'title' => array( 'type' => 'STRING' ),
                        'description' => array( 'type' => 'STRING' ),
                        'llmSummary' => array( 'type' => 'STRING' ),
                    ),
                    'required' => array( 'title', 'description', 'llmSummary' )
                )
            )
        );

        $response = wp_remote_post( $this->api_url . '?key=' . $this->api_key, array(
            'headers' => array( 'Content-Type' => 'application/json' ),
            'body'    => json_encode( $body ),
            'timeout' => 30
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
