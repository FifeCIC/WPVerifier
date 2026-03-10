/**
 * Admin Page Saved Results JavaScript
 * Handles saved results display functionality
 */

// Global variable for saved results data
let wpvSavedResults = {};

// Initialize when localized data is available
if (typeof wpvSavedData !== 'undefined' && wpvSavedData.results) {
    wpvSavedResults = wpvSavedData.results;
}