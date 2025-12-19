<?php

class WooSuite_Admin {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	public function add_plugin_admin_menu() {
		add_menu_page(
			'WooSuite AI',
			'WooSuite AI',
			'manage_options',
			'woosuite-ai',
			array( $this, 'display_plugin_admin_page' ),
			'dashicons-superhero',
			2
		);

        // Add a hidden/diagnostic page for testing connection
        add_submenu_page(
            'woosuite-ai',
            'Connection Test',
            'Connection Test',
            'manage_options',
            'woosuite-connection-test',
            array( $this, 'display_test_page' )
        );
	}

	public function display_plugin_admin_page() {
		// This div is where the React App will mount
		echo '<div id="woosuite-app-root"></div>';
	}

    public function display_test_page() {
        ?>
        <div class="wrap">
            <h1>WooSuite AI - Connection Test</h1>
            <p>Use this tool to verify if your server can connect to Google Gemini API.</p>

            <div style="background: #fff; padding: 20px; border: 1px solid #ccc; max-width: 600px;">
                <p><strong>API Key Status:</strong> <?php echo get_option('woosuite_gemini_api_key') ? 'Present (Hidden)' : 'Missing'; ?></p>
                <button id="run-test-btn" class="button button-primary button-large">Test Connection Now</button>
                <div id="test-result" style="margin-top: 20px; background: #f0f0f1; padding: 10px; white-space: pre-wrap; display:none;">Waiting...</div>
            </div>

            <script>
            // Ensure woosuiteData is available for this page
            var woosuiteData = {
                root: '<?php echo esc_url_raw( rest_url() ); ?>',
                nonce: '<?php echo wp_create_nonce( 'wp_rest' ); ?>',
                apiUrl: '<?php echo rest_url( 'woosuite/v1' ); ?>'
            };

            jQuery(document).ready(function($) {
                $('#run-test-btn').on('click', function() {
                    var btn = $(this);
                    var output = $('#test-result');

                    btn.prop('disabled', true).text('Testing...');
                    output.show().text('Sending request...');

                    $.ajax({
                        url: woosuiteData.apiUrl + '/settings/test-connection',
                        method: 'POST',
                        beforeSend: function(xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', woosuiteData.nonce);
                        },
                        success: function(res) {
                            output.css('border-left', '5px solid green')
                                  .text("SUCCESS:\n" + JSON.stringify(res, null, 2));
                            btn.prop('disabled', false).text('Test Again');
                        },
                        error: function(xhr) {
                             var msg = "ERROR " + xhr.status + ": " + xhr.statusText;
                             if (xhr.responseJSON) {
                                 msg += "\nDetails: " + JSON.stringify(xhr.responseJSON, null, 2);
                             } else {
                                 msg += "\nRaw: " + xhr.responseText;
                             }
                             output.css('border-left', '5px solid red').text(msg);
                             btn.prop('disabled', false).text('Test Again');
                        }
                    });
                });
            });
            </script>
        </div>
        <?php
    }

	public function enqueue_styles( $hook ) {
        if ( 'toplevel_page_woosuite-ai' !== $hook ) {
            return;
        }

        $css_file = WOOSUITE_AI_PATH . 'assets/woosuite-app.css';
        $version = file_exists( $css_file ) ? filemtime( $css_file ) : $this->version;

		wp_enqueue_style( $this->plugin_name, WOOSUITE_AI_URL . 'assets/woosuite-app.css', array(), $version, 'all' );
	}

	public function enqueue_scripts( $hook ) {
        if ( 'toplevel_page_woosuite-ai' !== $hook ) {
            return;
        }

        $js_file = WOOSUITE_AI_PATH . 'assets/woosuite-app.js';
        $version = file_exists( $js_file ) ? filemtime( $js_file ) : $this->version;

		wp_enqueue_script( $this->plugin_name, WOOSUITE_AI_URL . 'assets/woosuite-app.js', array( 'jquery' ), $version, true );

        // Pass nonce and API url to React
        wp_localize_script( $this->plugin_name, 'woosuiteData', array(
            'root' => esc_url_raw( rest_url() ),
            'homeUrl' => home_url(),
            'nonce' => wp_create_nonce( 'wp_rest' ),
            'apiUrl' => rest_url( 'woosuite/v1' ),
            'apiKey' => get_option( 'woosuite_gemini_api_key', '' )
        ));

        // Inject Test Button if we are on the React App page
        // This is a fallback in case the Submenu is missed or React build is old
        wp_add_inline_script( $this->plugin_name, "
            jQuery(document).ready(function($) {
                // Wait for React to render Settings tab
                // We use a simple interval to check for the Settings header
                var checkSettings = setInterval(function() {
                     var settingsHeader = $('h3:contains(\"Gemini API Configuration\")');
                     if (settingsHeader.length > 0 && $('#injected-test-btn').length === 0) {
                          var container = settingsHeader.closest('div').parent();
                          var btn = $('<button type=\"button\" id=\"injected-test-btn\" class=\"button button-secondary\" style=\"margin-left:10px;\">Test API Connection</button>');
                          settingsHeader.append(btn);

                          btn.on('click', function() {
                              $(this).text('Testing...').prop('disabled', true);
                              $.ajax({
                                    url: woosuiteData.apiUrl + '/settings/test-connection',
                                    method: 'POST',
                                    beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', woosuiteData.nonce); },
                                    success: function(res) {
                                        alert('SUCCESS: ' + res.message);
                                        $('#injected-test-btn').text('Test Connection').prop('disabled', false);
                                    },
                                    error: function(xhr) {
                                        var msg = xhr.responseJSON ? xhr.responseJSON.message : xhr.statusText;
                                        alert('ERROR: ' + msg);
                                        $('#injected-test-btn').text('Test Connection').prop('disabled', false);
                                    }
                              });
                          });
                     }
                }, 2000);
            });
        " );
	}

    /**
     * Add type="module" to the script tag for Vite support.
     */
    public function add_type_attribute( $tag, $handle, $src ) {
        if ( $this->plugin_name !== $handle ) {
            return $tag;
        }
        return '<script type="module" src="' . esc_url( $src ) . '"></script>';
    }
}
