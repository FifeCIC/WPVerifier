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
            this.initVendorFoldersManager();
            this.loadInitialConfig();
        },

        bindEvents() {
            $('#load-config').on('click', this.loadConfig.bind(this));
            $('#save-config').on('click', this.saveConfig.bind(this));
            $('#detect-vendors').on('click', this.detectVendors.bind(this));
            $('#reset-folders').on('click', this.resetFolders.bind(this));
        },

        initVendorFoldersManager() {
            // Make both zones sortable and droppable
            $('#included-folders, #excluded-folders').sortable({
                connectWith: '.folder-drop-zone',
                placeholder: 'folder-placeholder',
                tolerance: 'pointer',
                cursor: 'move',
                helper: 'clone',
                start: function(event, ui) {
                    ui.placeholder.html('<div style="height: 30px; background: #ddd; border-radius: 3px; margin: 2px 0;"></div>');
                },
                update: function(event, ui) {
                    ConfigManager.updateDropHints();
                }
            }).disableSelection();

            this.updateDropHints();
        },

        updateDropHints() {
            // Show/hide drop hints based on content
            $('#included-folders .drop-hint').toggle($('#included-folders .folder-item').length === 0);
            $('#excluded-folders .drop-hint').toggle($('#excluded-folders .folder-item').length === 0);
        },

        detectVendors() {
            const plugin = this.getCurrentPlugin();
            if (!plugin) {
                WPVerifierAjax.showMessage('config-content', 'No plugin selected', 'error');
                return;
            }

            // Use wpv_detect_vendors which includes JS libraries
            WPVerifierAjax.makeRequest('wpv_detect_vendors', { plugin }, {
                onSuccess: (result) => {
                    if (result.success && result.data.vendors) {
                        // Convert vendors object to array format if needed
                        const vendors = this.normalizeVendorsData(result.data.vendors);
                        this.populateVendorFolders(vendors, result.data.js_libraries);
                        WPVerifierAjax.showMessage('config-content', 'Vendor folders detected successfully', 'success');
                    } else {
                        WPVerifierAjax.showMessage('config-content', 'No vendor folders found', 'info');
                    }
                },
                onError: (error) => {
                    WPVerifierAjax.showMessage('config-content', 'Error detecting vendors: ' + error.message, 'error');
                }
            });
        },

        normalizeVendorsData(vendors) {
            // Handle both array format (from detect_vendors_simple) and object format (from detect_vendors)
            if (Array.isArray(vendors)) {
                return vendors;
            }
            
            // Convert object format to array format
            const vendorArray = [];
            for (const [path, subdirs] of Object.entries(vendors)) {
                if (Array.isArray(subdirs) && subdirs.length > 0) {
                    vendorArray.push({
                        path: path,
                        reason: 'vendor library detected',
                        subdirs: subdirs
                    });
                } else {
                    // Handle case where path itself is a vendor folder
                    vendorArray.push({
                        path: path,
                        reason: 'vendor library detected',
                        subdirs: []
                    });
                }
            }
            return vendorArray;
        },

        populateVendorFolders(vendors, jsLibraries) {
            // Clear existing folders
            $('#included-folders .folder-item, #excluded-folders .folder-item').remove();
            
            // Add detected vendor folders to included by default
            vendors.forEach(vendor => {
                if (vendor.subdirs && vendor.subdirs.length > 0) {
                    vendor.subdirs.forEach(subdir => {
                        const fullPath = vendor.path + '/' + subdir;
                        this.addFolderItem(fullPath, '#included-folders');
                    });
                } else if (vendor.path) {
                    this.addFolderItem(vendor.path, '#included-folders');
                }
            });
            
            // Add JS library directories to included by default
            if (jsLibraries) {
                const addedPaths = new Set();
                
                Object.values(jsLibraries).forEach(library => {
                    library.files.forEach(file => {
                        // Extract directory path from file path
                        const filePath = file.path.replace(/\\/g, '/');
                        const dirPath = filePath.substring(0, filePath.lastIndexOf('/'));
                        
                        // Only add unique directory paths
                        if (dirPath && !addedPaths.has(dirPath)) {
                            addedPaths.add(dirPath);
                            this.addFolderItem(dirPath, '#included-folders', library.name);
                        }
                    });
                });
            }
            
            this.updateDropHints();
        },

        addFolderItem(path, container, libraryName) {
            // Clean up path - remove absolute path prefix if present
            let displayPath = path;
            if (path.includes('wp-content/plugins/')) {
                displayPath = path.substring(path.indexOf('wp-content/plugins/') + 'wp-content/plugins/'.length);
            }
            
            // Extract just the plugin-relative path
            const pathParts = displayPath.split('/');
            if (pathParts.length > 1) {
                displayPath = pathParts.slice(1).join('/');
            }
            
            const label = libraryName ? `${displayPath} <span style="color: #856404; font-weight: normal;">(${libraryName})</span>` : displayPath;
            
            const folderHtml = `
                <div class="folder-item" data-path="${displayPath}" style="
                    background: #fff;
                    border: 1px solid #ddd;
                    border-radius: 3px;
                    padding: 8px 12px;
                    margin: 2px 0;
                    cursor: move;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                ">
                    <code style="font-size: 12px;">${label}</code>
                    <span class="dashicons dashicons-move" style="color: #666; font-size: 16px;"></span>
                </div>
            `;
            $(container).append(folderHtml);
        },

        resetFolders() {
            $('#included-folders .folder-item, #excluded-folders .folder-item').remove();
            this.updateDropHints();
            WPVerifierAjax.showMessage('config-content', 'Folder configuration reset', 'info');
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
                        this.loadIgnoredPaths(result.data.ignored_paths || []);
                    } else {
                        WPVerifierAjax.showMessage('config-content', result.data || 'Failed to load configuration', 'error');
                    }
                },
                onError: (error) => {
                    WPVerifierAjax.showMessage('config-content', 'Error loading configuration: ' + error.message, 'error');
                }
            });
        },

        loadIgnoredPaths(ignoredPaths) {
            // Clear existing folders
            $('#included-folders .folder-item, #excluded-folders .folder-item').remove();
            
            // First detect all available vendor folders and JS libraries
            const plugin = this.getCurrentPlugin();
            WPVerifierAjax.makeRequest('wpv_detect_vendors', { plugin }, {
                onSuccess: (result) => {
                    if (result.success && result.data.vendors) {
                        // Convert vendors object to array format if needed
                        const vendors = this.normalizeVendorsData(result.data.vendors);
                        const allFolders = [];
                        
                        // Collect all detected vendor folders
                        vendors.forEach(vendor => {
                            if (vendor.subdirs && vendor.subdirs.length > 0) {
                                vendor.subdirs.forEach(subdir => {
                                    allFolders.push(vendor.path + '/' + subdir);
                                });
                            } else if (vendor.path) {
                                allFolders.push(vendor.path);
                            }
                        });
                        
                        // Collect JS library directories
                        const jsLibraryFolders = [];
                        if (result.data.js_libraries) {
                            Object.values(result.data.js_libraries).forEach(library => {
                                library.files.forEach(file => {
                                    const filePath = file.path.replace(/\\/g, '/');
                                    const dirPath = filePath.substring(0, filePath.lastIndexOf('/'));
                                    if (dirPath && !jsLibraryFolders.includes(dirPath)) {
                                        jsLibraryFolders.push(dirPath);
                                        allFolders.push(dirPath);
                                    }
                                });
                            });
                        }
                        
                        // Get list of ignored paths
                        const ignoredPathsList = ignoredPaths.map(item => 
                            typeof item === 'string' ? item : item.path
                        );
                        
                        // Sort folders into included vs excluded
                        allFolders.forEach(folder => {
                            const isIgnored = ignoredPathsList.includes(folder);
                            const container = isIgnored ? '#excluded-folders' : '#included-folders';
                            this.addFolderItem(folder, container);
                        });
                        
                        this.updateDropHints();
                    }
                }
            });
        },

        renderSkippedRulesList(rules) {
            if (!rules || rules.length === 0) {
                return '<p><em>No rules are currently being skipped.</em></p>';
            }
            
            const listItems = rules.map(rule => `<li><code>${rule}</code></li>`).join('');
            return `<ul style="margin: 0; padding-left: 20px;">${listItems}</ul>`;
        },

        renderConfigForm(config) {
            // Store current config for later use
            this.currentConfig = config;
            
            const formHtml = `
                <div class="config-form">
                    <table class="form-table">
                        <tr>
                            <th><label for="wporg_preparation">WordPress.org Preparation</label></th>
                            <td>
                                <label>
                                    <input type="checkbox" id="wporg_preparation" ${config.configuration?.wporg_preparation ? 'checked' : ''} />
                                    Enable WordPress.org preparation mode
                                </label>
                                <p class="description">Applies stricter rules for WordPress.org submission</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Skipped Rules</label></th>
                            <td>
                                <div id="skipped_rules_display">
                                    ${this.renderSkippedRulesList(config.configuration?.skipped_rules || [])}
                                </div>
                                <p class="description">These rules will be skipped during verification.</p>
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

            // Get excluded folders (those in the excluded column)
            const excludedFolders = $('#excluded-folders .folder-item').map(function() {
                return $(this).data('path');
            }).get();

            // Preserve existing skipped rules since we're now display-only
            const existingSkippedRules = this.currentConfig?.configuration?.skipped_rules || [];

            const configData = {
                wporg_preparation: $('#wporg_preparation').is(':checked'),
                skipped_rules: existingSkippedRules,
                vendor_folders: excludedFolders // These will become ignored_paths
            };

            console.log('Saving config with excluded folders:', excludedFolders);

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