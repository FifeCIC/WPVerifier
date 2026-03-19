/**
 * WP Verifier AJAX Utilities
 * Shared AJAX functions to reduce code duplication
 *
 * @package wp-verifier
 */

window.WPVerifierAjax = (function() {
    'use strict';

    // Common AJAX configuration
    const config = {
        ajaxUrl: wpv_ajax_object?.ajax_url || ajaxurl,
        nonce: wpv_ajax_object?.nonce || '',
        timeout: 30000
    };

    /**
     * Show spinner
     */
    function showSpinner(spinnerId) {
        const spinner = document.getElementById(spinnerId);
        if (spinner) {
            spinner.classList.add('is-active');
        }
    }

    /**
     * Hide spinner
     */
    function hideSpinner(spinnerId) {
        const spinner = document.getElementById(spinnerId);
        if (spinner) {
            spinner.classList.remove('is-active');
        }
    }

    /**
     * Show message
     */
    function showMessage(containerId, message, type = 'info') {
        const container = document.getElementById(containerId);
        if (!container) return;

        const messageDiv = document.createElement('div');
        messageDiv.className = `notice notice-${type} is-dismissible`;
        messageDiv.innerHTML = `<p>${message}</p>`;
        
        container.innerHTML = '';
        container.appendChild(messageDiv);
    }

    /**
     * Generic AJAX request handler
     */
    function makeRequest(action, data = {}, options = {}) {
        const defaultOptions = {
            method: 'POST',
            timeout: config.timeout,
            showSpinner: null,
            onSuccess: null,
            onError: null,
            onComplete: null
        };

        const opts = { ...defaultOptions, ...options };

        if (opts.showSpinner) {
            showSpinner(opts.showSpinner);
        }

        const formData = new FormData();
        formData.append('action', action);
        formData.append('nonce', config.nonce);

        // Add data to form
        Object.keys(data).forEach(key => {
            if (data[key] !== null && data[key] !== undefined) {
                formData.append(key, data[key]);
            }
        });

        return fetch(config.ajaxUrl, {
            method: opts.method,
            body: formData,
            signal: AbortSignal.timeout(opts.timeout)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(result => {
            if (opts.onSuccess && typeof opts.onSuccess === 'function') {
                opts.onSuccess(result);
            }
            return result;
        })
        .catch(error => {
            console.error('AJAX Error:', error);
            if (opts.onError && typeof opts.onError === 'function') {
                opts.onError(error);
            }
            throw error;
        })
        .finally(() => {
            if (opts.showSpinner) {
                hideSpinner(opts.showSpinner);
            }
            if (opts.onComplete && typeof opts.onComplete === 'function') {
                opts.onComplete();
            }
        });
    }

    /**
     * Load plugin configuration
     */
    function loadConfig(plugin, options = {}) {
        return makeRequest('wpv_load_config', { plugin }, {
            showSpinner: 'config-spinner',
            ...options
        });
    }

    /**
     * Save plugin configuration
     */
    function saveConfig(plugin, configData, options = {}) {
        return makeRequest('wpv_save_config', { 
            plugin, 
            config_data: JSON.stringify(configData) 
        }, {
            showSpinner: 'config-spinner',
            ...options
        });
    }

    /**
     * Generate file hashes
     */
    function generateHashes(plugin, options = {}) {
        return makeRequest('wpv_generate_hashes', { plugin }, {
            showSpinner: 'hash-spinner',
            timeout: 60000, // Longer timeout for hash generation
            ...options
        });
    }

    /**
     * Validate existing hashes
     */
    function validateHashes(plugin, options = {}) {
        return makeRequest('wpv_validate_hashes', { plugin }, {
            showSpinner: 'hash-spinner',
            ...options
        });
    }

    /**
     * Load verification results
     */
    function loadResults(plugin, options = {}) {
        return makeRequest('wpv_load_results', { plugin }, {
            showSpinner: 'results-spinner',
            ...options
        });
    }

    /**
     * Run verification checks
     */
    function runChecks(plugin, checkOptions = {}, options = {}) {
        console.log('WPVerifierAjax.runChecks called with:');
        console.log('- plugin:', plugin);
        console.log('- checkOptions:', checkOptions);
        console.log('- options:', options);
        
        const checkOptionsJson = JSON.stringify(checkOptions);
        console.log('- checkOptions JSON:', checkOptionsJson);
        
        return makeRequest('wpv_run_checks', { 
            plugin,
            check_options: checkOptionsJson
        }, {
            showSpinner: 'verification-spinner',
            timeout: 120000, // Longer timeout for verification
            ...options
        });
    }

    /**
     * Progress tracking for long operations
     */
    function trackProgress(progressId, updateCallback) {
        let progressInterval;
        
        function startTracking() {
            progressInterval = setInterval(() => {
                makeRequest('wpv_get_progress', {}, {
                    onSuccess: (result) => {
                        if (updateCallback && typeof updateCallback === 'function') {
                            updateCallback(result);
                        }
                        
                        if (result.complete) {
                            stopTracking();
                        }
                    },
                    onError: () => {
                        stopTracking();
                    }
                });
            }, 1000);
        }
        
        function stopTracking() {
            if (progressInterval) {
                clearInterval(progressInterval);
                progressInterval = null;
            }
        }
        
        return { startTracking, stopTracking };
    }

    /**
     * Batch operations handler
     */
    function batchOperation(action, items, batchSize = 10, options = {}) {
        const batches = [];
        for (let i = 0; i < items.length; i += batchSize) {
            batches.push(items.slice(i, i + batchSize));
        }

        let completed = 0;
        const total = batches.length;

        return batches.reduce((promise, batch, index) => {
            return promise.then(() => {
                return makeRequest(action, { 
                    batch: JSON.stringify(batch),
                    batch_index: index,
                    total_batches: total
                }, options).then(result => {
                    completed++;
                    if (options.onProgress && typeof options.onProgress === 'function') {
                        options.onProgress(completed, total, result);
                    }
                    return result;
                });
            });
        }, Promise.resolve());
    }

    /**
     * File upload handler
     */
    function uploadFile(file, action, additionalData = {}, options = {}) {
        const formData = new FormData();
        formData.append('action', action);
        formData.append('nonce', config.nonce);
        formData.append('file', file);

        Object.keys(additionalData).forEach(key => {
            formData.append(key, additionalData[key]);
        });

        if (options.showSpinner) {
            showSpinner(options.showSpinner);
        }

        return fetch(config.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(result => {
            if (options.onSuccess && typeof options.onSuccess === 'function') {
                options.onSuccess(result);
            }
            return result;
        })
        .catch(error => {
            console.error('Upload Error:', error);
            if (options.onError && typeof options.onError === 'function') {
                options.onError(error);
            }
            throw error;
        })
        .finally(() => {
            if (options.showSpinner) {
                hideSpinner(options.showSpinner);
            }
        });
    }

    // Public API
    return {
        // Core functions
        makeRequest,
        showSpinner,
        hideSpinner,
        showMessage,
        
        // Specific operations
        loadConfig,
        saveConfig,
        generateHashes,
        validateHashes,
        loadResults,
        runChecks,
        
        // Utilities
        trackProgress,
        batchOperation,
        uploadFile,
        
        // Configuration
        config
    };
})();