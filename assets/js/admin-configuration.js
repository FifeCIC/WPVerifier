/**
 * Configuration Tab JavaScript
 * Consolidated from admin-page-preparation.js and admin-page-hash-generation.js
 *
 * @package wp-verifier
 */

(function($) {
    'use strict';

    // Configuration management
    const ConfigManager = {
        init() {
            this.bindEvents();
            this.loadInitialConfig();
        },

        bindEvents() {
            $('#load-config').on('click', this.loadConfig.bind(this));
            $('#save-config').on('click', this.saveConfig.bind(this));
        },

        loadInitialConfig() {
            const currentPlugin = this.getCurrentPlugin();
            if (currentPlugin) {
                this.loadConfig();
            }
        },

        getCurrentPlugin() {
            return wpv_ajax_object?.current_plugin || '';
        },

        loadConfig() {
            const plugin = this.getCurrentPlugin();
            if (!plugin) {
                WPVerifierAjax.showMessage('config-content', 'No plugin selected', 'error');
                return;
            }

            WPVerifierAjax.loadConfig(plugin, {
                onSuccess: (result) => {
                    if (result.success) {
                        this.renderConfigForm(result.data);
                    } else {
                        WPVerifierAjax.showMessage('config-content', result.data || 'Failed to load configuration', 'error');
                    }
                },
                onError: (error) => {
                    WPVerifierAjax.showMessage('config-content', 'Error loading configuration: ' + error.message, 'error');
                }
            });
        },

        renderConfigForm(config) {
            const formHtml = `
                <div class="config-form">
                    <table class="form-table">
                        <tr>
                            <th><label for="wporg_preparation">WordPress.org Preparation</label></th>
                            <td>
                                <label>
                                    <input type="checkbox" id="wporg_preparation" ${config.wporg_preparation ? 'checked' : ''} />
                                    Enable WordPress.org preparation mode
                                </label>
                                <p class="description">Applies stricter rules for WordPress.org submission</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="skipped_rules">Skipped Rules</label></th>
                            <td>
                                <textarea id="skipped_rules" rows="4" cols="50" placeholder="WordPress.Security.EscapeOutput&#10;WordPress.DB.DirectDatabaseQuery">${(config.skipped_rules || []).join('\\n')}</textarea>
                                <p class="description">One rule per line. These rules will be skipped during verification.</p>
                            </td>
                        </tr>
                    </table>
                    <p>
                        <button type="button" id="save-config" class="button button-primary">Save Configuration</button>
                        <span id="config-spinner" class="spinner" style="float: none;"></span>
                    </p>
                </div>
            `;

            $('#config-form').html(formHtml);
            this.bindConfigEvents();
        },

        bindConfigEvents() {
            $('#save-config').on('click', this.saveConfig.bind(this));
        },

        saveConfig() {
            const plugin = this.getCurrentPlugin();
            if (!plugin) return;

            const configData = {
                wporg_preparation: $('#wporg_preparation').is(':checked'),
                skipped_rules: $('#skipped_rules').val().split('\\n').filter(rule => rule.trim()),
                vendor_folders: $('input[name="vendor_folders[]"]:checked').map(function() {
                    return $(this).val();
                }).get()
            };

            WPVerifierAjax.saveConfig(plugin, configData, {
                onSuccess: (result) => {
                    if (result.success) {
                        WPVerifierAjax.showMessage('config-content', result.data.message || 'Configuration saved successfully', 'success');
                    } else {
                        WPVerifierAjax.showMessage('config-content', result.data || 'Failed to save configuration', 'error');
                    }
                },
                onError: (error) => {
                    WPVerifierAjax.showMessage('config-content', 'Error saving configuration: ' + error.message, 'error');
                }
            });
        }
    };

    // Hash generation management
    const HashManager = {
        init() {
            this.bindEvents();
            this.checkExistingHashes();
        },

        bindEvents() {
            $('#generate-hashes').on('click', this.generateHashes.bind(this));
            $('#validate-hashes').on('click', this.validateHashes.bind(this));
        },

        getCurrentPlugin() {
            return wpv_ajax_object?.current_plugin || '';
        },

        checkExistingHashes() {
            const plugin = this.getCurrentPlugin();
            if (!plugin) return;

            WPVerifierAjax.makeRequest('wpv_check_hashes', { plugin }, {
                onSuccess: (result) => {
                    if (result.success && result.data.has_hashes) {
                        $('#validate-hashes').show();
                        this.updateHashStatus('Existing hashes found', 'success');
                    } else {
                        this.updateHashStatus('No hashes found - generate new hashes', 'info');
                    }
                }
            });
        },

        generateHashes() {
            const plugin = this.getCurrentPlugin();
            if (!plugin) {
                this.updateHashStatus('No plugin selected', 'error');
                return;
            }

            this.updateHashStatus('Generating file hashes...', 'info');
            this.showProgress(0);

            // Start progress tracking
            const progressTracker = WPVerifierAjax.trackProgress('hash-progress', (progress) => {
                this.updateProgress(progress);
            });

            progressTracker.startTracking();

            WPVerifierAjax.generateHashes(plugin, {
                onSuccess: (result) => {
                    progressTracker.stopTracking();
                    
                    if (result.success) {
                        this.updateHashStatus('Hash generation completed successfully', 'success');
                        this.renderHashResults(result.data);
                        $('#validate-hashes').show();
                    } else {
                        this.updateHashStatus(result.data || 'Hash generation failed', 'error');
                    }
                    this.hideProgress();
                },
                onError: (error) => {
                    progressTracker.stopTracking();
                    this.updateHashStatus('Error generating hashes: ' + error.message, 'error');
                    this.hideProgress();
                }
            });
        },

        validateHashes() {
            const plugin = this.getCurrentPlugin();
            if (!plugin) return;

            this.updateHashStatus('Validating existing hashes...', 'info');

            WPVerifierAjax.validateHashes(plugin, {
                onSuccess: (result) => {
                    if (result.success) {
                        this.updateHashStatus('Hash validation completed', 'success');
                        this.renderValidationResults(result.data);
                    } else {
                        this.updateHashStatus(result.data || 'Hash validation failed', 'error');
                    }
                },
                onError: (error) => {
                    this.updateHashStatus('Error validating hashes: ' + error.message, 'error');
                }
            });
        },

        updateHashStatus(message, type) {
            const statusClass = type === 'error' ? 'error' : (type === 'success' ? 'success' : 'info');
            $('#hash-status').html(`<div class="notice notice-${statusClass}"><p>${message}</p></div>`);
        },

        showProgress(percent) {
            const progressHtml = `
                <div class="progress-container">
                    <div class="progress-bar" style="width: 100%; background: #f0f0f0; border-radius: 3px; overflow: hidden;">
                        <div class="progress-fill" style="width: ${percent}%; background: #0073aa; height: 20px; transition: width 0.3s;"></div>
                    </div>
                    <div class="progress-text" style="margin-top: 5px; font-size: 12px;">${percent}% complete</div>
                </div>
            `;
            $('#hash-progress').html(progressHtml).show();
        },

        updateProgress(progress) {
            if (progress.percent !== undefined) {
                $('.progress-fill').css('width', progress.percent + '%');
                $('.progress-text').text(progress.percent + '% complete');
            }
            
            if (progress.message) {
                $('.progress-text').text(progress.message);
            }
        },

        hideProgress() {
            $('#hash-progress').hide();
        },

        renderHashResults(data) {
            if (!data.stats) return;

            const resultsHtml = `
                <div class="hash-results">
                    <h4>Hash Generation Results</h4>
                    <table class="widefat">
                        <tr><td>Files Processed:</td><td>${data.stats.files_processed || 0}</td></tr>
                        <tr><td>Functions Found:</td><td>${data.stats.functions_found || 0}</td></tr>
                        <tr><td>File Hashes:</td><td>${data.stats.file_hashes || 0}</td></tr>
                        <tr><td>Function Hashes:</td><td>${data.stats.function_hashes || 0}</td></tr>
                        <tr><td>Processing Time:</td><td>${data.stats.processing_time || 'N/A'}</td></tr>
                    </table>
                </div>
            `;
            
            $('#hash-results').html(resultsHtml).show();
        },

        renderValidationResults(data) {
            if (!data.validation) return;

            let resultsHtml = '<div class="validation-results"><h4>Hash Validation Results</h4>';
            
            if (data.validation.valid_hashes > 0) {
                resultsHtml += `<p style="color: #46b450;">✓ ${data.validation.valid_hashes} hashes are valid</p>`;
            }
            
            if (data.validation.invalid_hashes > 0) {
                resultsHtml += `<p style="color: #dc3232;">✗ ${data.validation.invalid_hashes} hashes are invalid</p>`;
            }
            
            if (data.validation.missing_files && data.validation.missing_files.length > 0) {
                resultsHtml += '<p><strong>Missing Files:</strong></p><ul>';
                data.validation.missing_files.forEach(file => {
                    resultsHtml += `<li><code>${file}</code></li>`;
                });
                resultsHtml += '</ul>';
            }
            
            resultsHtml += '</div>';
            $('#hash-results').html(resultsHtml).show();
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        ConfigManager.init();
        HashManager.init();
    });

})(jQuery);