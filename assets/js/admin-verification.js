/**
 * Verification Tab JavaScript
 * Consolidated from basic-verification.js and plugin-check-admin.js
 *
 * @package wp-verifier
 */

(function($) {
    'use strict';

    const VerificationManager = {
        
        init() {
            console.log('VerificationManager initialized');
            console.log('wpv_ajax_object:', typeof wpv_ajax_object !== 'undefined' ? wpv_ajax_object : 'undefined');
            this.bindEvents();
        },

        bindEvents() {
            console.log('Binding verification events');
            // Form submission
            $('#plugin-check__submit').on('click', this.runVerification.bind(this));
            console.log('Submit button found:', $('#plugin-check__submit').length);
            
            // Plugin selection (legacy - may not exist)
            $('#plugin-check__plugins-dropdown').on('change', this.handlePluginChange.bind(this));
        },

        handlePluginChange() {
            // Update any plugin-specific UI elements
            const selectedPlugin = $('#plugin-check__plugins-dropdown').val();
            if (selectedPlugin) {
                this.loadPluginInfo(selectedPlugin);
            }
        },

        loadPluginInfo(plugin) {
            // Load plugin-specific information if needed
            WPVerifierAjax.makeRequest('wpv_get_plugin_info', { plugin }, {
                onSuccess: (result) => {
                    if (result.success) {
                        this.updatePluginInfo(result.data);
                    }
                }
            });
        },

        updatePluginInfo(info) {
            // Update UI with plugin information
            if (info.has_config) {
                // Show configuration status
            }
            if (info.has_hashes) {
                // Show hash status
            }
        },

        runVerification(e) {
            e.preventDefault();
            this.runAdvancedVerification();
        },

        runAdvancedVerification() {
            console.log('runAdvancedVerification called');
            const plugin = this.getSelectedPlugin();
            console.log('Selected plugin:', plugin);
            
            if (!plugin) {
                alert('Please select a plugin first.');
                return;
            }

            // Hide previous results and show progress
            $('#plugin-check__results').empty();
            $('#wpv-ignored-files-panel').hide();
            $('#wpv-ast-container').hide();
            $('#verification-progress').show();
            $('#verification-progress-container').show();

            const options = {
                plugin: plugin,
                categories: this.getSelectedCategories(),
                types: this.getSelectedTypes(),
                include_experimental: $('#plugin-check__include-experimental').is(':checked'),
                limit_results: $('#plugin-check__limit-results').is(':checked')
            };

            console.log('Verification options:', options);
            console.log('Limit results checkbox checked:', $('#plugin-check__limit-results').is(':checked'));
            console.log('Limit results checkbox exists:', $('#plugin-check__limit-results').length);
            console.log('Limit results checkbox value:', $('#plugin-check__limit-results').val());
            console.log('Limit results checkbox element:', $('#plugin-check__limit-results')[0]);

            // Start progress simulation
            this.startProgressSimulation();

            WPVerifierAjax.runChecks(plugin, options, {
                showSpinner: 'plugin-check__spinner',
                timeout: 120000, // 2 minutes for advanced verification
                onSuccess: (result) => {
                    console.log('AJAX Success - Full result:', result);
                    this.stopProgressSimulation();
                    this.hideProgress();
                    
                    if (result.success) {
                        console.log('Result data:', result.data);
                        this.displayAdvancedResults(result.data);
                    } else {
                        console.log('Result failed:', result.data);
                        this.displayAdvancedError(result.data || 'Advanced verification failed');
                    }
                },
                onError: (error) => {
                    console.log('AJAX Error:', error);
                    this.stopProgressSimulation();
                    this.hideProgress();
                    this.displayAdvancedError('Error: ' + error.message);
                }
            });
        },

        getSelectedPlugin() {
            return wpv_ajax_object?.current_plugin || '';
        },

        getSelectedCategories() {
            const categories = [];
            $('input[name="categories"]:checked').each(function() {
                categories.push($(this).val());
            });
            return categories;
        },

        getSelectedTypes() {
            const types = [];
            $('input[name="types"]:checked').each(function() {
                types.push($(this).val());
            });
            
            // Default to both error and warning if none selected
            if (types.length === 0) {
                types.push('error', 'warning');
            }
            
            console.log('Selected types:', types);
            return types;
        },

        displayAdvancedResults(data) {
            this.hideProgress();
            
            console.log('displayAdvancedResults called with data:', data);
            
            // Show the readiness score display after verification
            $('#readiness-score-display').show();
            
            if (data.html_output) {
                console.log('Displaying HTML output, length:', data.html_output.length);
                $('#plugin-check__results').html(data.html_output);
                
                // Check if readiness score is in the JSON file and render it
                this.loadAndDisplayReadinessScore();
            } else {
                console.log('No html_output in response data');
            }

            if (data.ignored_files && data.ignored_files.length > 0) {
                this.displayIgnoredFiles(data.ignored_files);
            }

            if (data.ast_results) {
                this.displayASTResults(data.ast_results);
            }

            if (data.export_controls) {
                $('#plugin-check__export-controls').html(data.export_controls);
            }

            // Initialize any interactive elements
            this.initializeAdvancedFeatures();
            
            // Scroll to results
            $('html, body').animate({
                scrollTop: $('#plugin-check__results').offset().top
            }, 500);
        },

        loadAndDisplayReadinessScore() {
            const plugin = this.getSelectedPlugin();
            if (!plugin) return;
            
            const pluginFolder = plugin.indexOf('/') !== -1 ? plugin.split('/')[0] : plugin;
            const currentUrl = window.location.href;
            const wpContentBase = currentUrl.substring(0, currentUrl.indexOf('/wp-admin/')) + '/wp-content/';
            const jsonUrl = wpContentBase + 'plugins/' + pluginFolder + '/.wpv-results.json';
            
            fetch(jsonUrl)
                .then(response => response.json())
                .then(data => {
                    if (data.readiness) {
                        this.renderReadinessScore(data.readiness, plugin);
                    }
                })
                .catch(error => {
                    console.log('Could not load readiness score:', error);
                });
        },

        renderReadinessScore(readiness, plugin) {
            if (!readiness || readiness.overall === undefined) {
                return;
            }
            
            const statusColors = {
                excellent: '#00a32a',
                good: '#72aee6', 
                fair: '#dba617',
                'needs-work': '#d63638'
            };
            const statusLabels = {
                excellent: 'Excellent - Ready for Submission',
                good: 'Good - Minor Issues',
                fair: 'Fair - Needs Improvement', 
                'needs-work': 'Needs Work - Major Issues'
            };
            
            // Determine status based on score
            let status = 'needs-work';
            if (readiness.overall >= 90) status = 'excellent';
            else if (readiness.overall >= 75) status = 'good';
            else if (readiness.overall >= 60) status = 'fair';
            
            const color = statusColors[status] || '#646970';
            const label = statusLabels[status] || status;
            
            const pluginName = this.getSelectedPluginName();
            
            const readinessHtml = `
                <div id="readiness-score-container" style="margin: 20px 0; padding: 25px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; box-shadow: 0 1px 1px rgba(0,0,0,0.04);">
                    <div style="display: flex; align-items: center; gap: 30px;">
                        <div style="text-align: center; min-width: 120px;">
                            <div style="font-size: 64px; font-weight: 700; color: ${color}; line-height: 1;">${readiness.overall}</div>
                            <div style="font-size: 12px; color: #646970; margin-top: 5px;">out of 100</div>
                        </div>
                        <div style="flex: 1;">
                            <h3 style="margin: 0 0 8px 0; font-size: 20px;">Readiness Score: ${pluginName}</h3>
                            <div style="font-size: 16px; color: ${color}; font-weight: 600; margin-bottom: 10px;">${label}</div>
                            <div style="font-size: 14px; color: #646970;">
                                <strong>${readiness.errors}</strong> error${readiness.errors !== 1 ? 's' : ''} • 
                                <strong>${readiness.warnings}</strong> warning${readiness.warnings !== 1 ? 's' : ''}
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Insert readiness score before the results summary
            const resultsContainer = $('#plugin-check__results');
            if (resultsContainer.length) {
                resultsContainer.prepend(readinessHtml);
            }
        },

        getSelectedPluginName() {
            // Try to get plugin name from available data
            if (typeof wpv_ajax_object !== 'undefined' && wpv_ajax_object.plugin_name) {
                return wpv_ajax_object.plugin_name;
            }
            return this.getSelectedPlugin() || 'Unknown Plugin';
        },

        displayAdvancedError(error) {
            this.hideProgress();
            $('#plugin-check__results').html(`
                <div class="notice notice-error">
                    <p><strong>Verification Error:</strong> ${error}</p>
                </div>
            `);
        },

        displayIgnoredFiles(ignoredFiles) {
            let html = '<ul>';
            ignoredFiles.forEach(file => {
                html += `<li><code>${file.path}</code> - ${file.reason}</li>`;
            });
            html += '</ul>';
            
            $('#wpv-ignored-files-content').html(html);
            $('#wpv-ignored-files-panel').show();
        },

        displayASTResults(astResults) {
            if (!astResults.issues || !astResults.issues.length) {
                return;
            }

            let html = '<table class="wp-list-table widefat fixed striped">';
            html += '<thead><tr>';
            html += '<th>File</th><th>Line</th><th>Type</th><th>Message</th><th>Actions</th>';
            html += '</tr></thead><tbody>';

            astResults.issues.forEach((issue, index) => {
                html += `<tr data-issue-id="${index}">`;
                html += `<td><code>${issue.file}</code></td>`;
                html += `<td>${issue.line}</td>`;
                html += `<td><span class="issue-type issue-type-${issue.type.toLowerCase()}">${issue.type}</span></td>`;
                html += `<td>${issue.message}</td>`;
                html += `<td><button class="button button-small view-details" data-issue-id="${index}">Details</button></td>`;
                html += '</tr>';
            });

            html += '</tbody></table>';
            
            $('#wpv-ast-results').html(html);
            $('#wpv-ast-container').show();
            
            // Bind detail view events
            $('.view-details').on('click', this.showIssueDetails.bind(this));
        },

        showIssueDetails(e) {
            const issueId = $(e.target).data('issue-id');
            // Load and display issue details
            this.loadIssueDetails(issueId);
        },

        loadIssueDetails(issueId) {
            WPVerifierAjax.makeRequest('wpv_get_issue_details', { issue_id: issueId }, {
                onSuccess: (result) => {
                    if (result.success) {
                        this.displayIssueDetails(result.data);
                    }
                }
            });
        },

        displayIssueDetails(details) {
            let html = `
                <h4>Issue Details</h4>
                <table class="form-table">
                    <tr><th>File:</th><td><code>${details.file}</code></td></tr>
                    <tr><th>Line:</th><td>${details.line}</td></tr>
                    <tr><th>Type:</th><td>${details.type}</td></tr>
                    <tr><th>Code:</th><td><code>${details.code}</code></td></tr>
                    <tr><th>Message:</th><td>${details.message}</td></tr>
                </table>
            `;

            if (details.code_context) {
                html += `
                    <h5>Code Context</h5>
                    <pre style="background: #f5f5f5; padding: 10px; border-radius: 3px; overflow-x: auto;">${details.code_context}</pre>
                `;
            }

            if (details.ai_guidance) {
                html += `
                    <div id="wpv-ai-guidance">
                        <h5>AI Guidance</h5>
                        <div>${details.ai_guidance}</div>
                    </div>
                `;
            }

            $('#wpv-ast-details').html(html).show();
        },

        startProgressSimulation() {
            this.progressInterval = 0;
            this.progressSteps = [
                { percent: 10, message: 'Initializing verification environment...' },
                { percent: 15, message: 'Loading plugin files...' },
                { percent: 37, message: 'Running security checks...' },
                { percent: 54, message: 'Analyzing code quality...' },
                { percent: 60, message: 'Checking performance issues...' },
                { percent: 63, message: 'Validating accessibility...' },
                { percent: 70, message: 'Generating report...' }
            ];
            this.currentStepIndex = 0;
            
            this.updateProgress(0, 'Starting verification...');
            
            this.progressTimer = setInterval(() => {
                if (this.currentStepIndex < this.progressSteps.length) {
                    const step = this.progressSteps[this.currentStepIndex];
                    this.updateProgress(step.percent, step.message);
                    this.currentStepIndex++;
                } else {
                    // Keep at 70% until completion
                    this.updateProgress(70, 'Finalizing verification...');
                }
            }, 2000); // Update every 2 seconds
        },

        stopProgressSimulation() {
            if (this.progressTimer) {
                clearInterval(this.progressTimer);
                this.progressTimer = null;
            }
            this.updateProgress(100, 'Verification completed!');
        },

        updateProgress(percent, message) {
            $('#progress-bar').css('width', percent + '%');
            $('#progress-percentage').text(percent + '%');
            $('#progress-message').text(message);
            
            $('#overall-progress-bar').css('width', percent + '%');
            $('#overall-progress-percentage').text(percent + '%');
            $('#current-step').text(message);
            
            if (percent >= 100) {
                setTimeout(() => {
                    this.hideProgress();
                }, 1000);
            }
        },

        hideProgress() {
            $('#verification-progress').hide();
            $('#verification-progress-container').hide();
        },

        initializeAdvancedFeatures() {
            // Initialize any advanced features like sorting, filtering, etc.
            this.initializeTableSorting();
            this.initializeFiltering();
            this.initializeExportControls();
        },

        initializeTableSorting() {
            // Add table sorting functionality
            $('.wp-list-table th').on('click', function() {
                // Implement sorting logic
            });
        },

        initializeFiltering() {
            // Add filtering functionality
            if ($('#issue-filter').length) {
                $('#issue-filter').on('input', this.filterIssues.bind(this));
            }
        },

        filterIssues() {
            const filterText = $('#issue-filter').val().toLowerCase();
            $('.wp-list-table tbody tr').each(function() {
                const rowText = $(this).text().toLowerCase();
                $(this).toggle(rowText.includes(filterText));
            });
        },

        initializeExportControls() {
            // Initialize export functionality
            $('.export-button').on('click', this.handleExport.bind(this));
        },

        handleExport(e) {
            const exportType = $(e.target).data('export-type');
            const plugin = this.getSelectedPlugin();
            
            WPVerifierAjax.makeRequest('wpv_export_results', { 
                plugin: plugin, 
                export_type: exportType 
            }, {
                onSuccess: (result) => {
                    if (result.success && result.data.download_url) {
                        window.location.href = result.data.download_url;
                    }
                }
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        VerificationManager.init();
    });

})(jQuery);