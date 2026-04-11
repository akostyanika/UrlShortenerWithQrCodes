/**
 * URL Shortener Frontend Application
 *
 * This JavaScript file handles the SPA behavior for the URL Shortener.
 * It performs all create operations via AJAX and displays results dynamically.
 */

(function() {
    'use strict';

    // ============================================================
    // Configuration
    // ============================================================
    const CONFIG = {
        apiEndpoint: '/api/shorten',
        requestTimeout: 30000, // 30 seconds
        animationDuration: 500,
    };

    // ============================================================
    // DOM Elements
    // ============================================================
    const elements = {
        form: document.getElementById('shortenForm'),
        urlInput: document.getElementById('urlInput'),
        urlError: document.getElementById('urlError'),
        shortenBtn: document.getElementById('shortenBtn'),
        btnSpinner: document.getElementById('btnSpinner'),
        btnText: document.getElementById('btnText'),
        resultSection: document.getElementById('resultSection'),
        shortUrlText: document.getElementById('shortUrlText'),
        copyBtn: document.getElementById('copyBtn'),
        qrCodeImg: document.getElementById('qrCodeImg'),
        visitLink: document.getElementById('visitLink'),
        newLinkBtn: document.getElementById('newLinkBtn'),
        copyToast: document.getElementById('copyToast'),
    };

    // ============================================================
    // State
    // ============================================================
    let state = {
        isLoading: false,
        currentShortUrl: '',
    };

    // ============================================================
    // Utility Functions
    // ============================================================

    /**
     * Show error message
     * @param {string} message - Error message to display
     */
    function showError(message) {
        elements.urlInput.classList.add('is-invalid');
        elements.urlError.textContent = message;
        elements.urlError.classList.add('show');
    }

    /**
     * Clear error message
     */
    function clearError() {
        elements.urlInput.classList.remove('is-invalid');
        elements.urlError.textContent = '';
        elements.urlError.classList.remove('show');
    }

    /**
     * Set loading state
     * @param {boolean} loading - Whether to show loading state
     */
    function setLoading(loading) {
        state.isLoading = loading;
        elements.shortenBtn.disabled = loading;
        elements.urlInput.disabled = loading;

        if (loading) {
            elements.btnSpinner.style.display = 'inline-block';
            elements.btnText.textContent = 'Shortening...';
        } else {
            elements.btnSpinner.style.display = 'none';
            elements.btnText.textContent = 'Shorten URL';
        }
    }

    /**
     * Show result section with animation
     */
    function showResults() {
        elements.resultSection.classList.add('show');
        // Scroll to results
        elements.resultSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    /**
     * Hide result section
     */
    function hideResults() {
        elements.resultSection.classList.remove('show');
    }

    /**
     * Reset form to initial state
     */
    function resetForm() {
        elements.form.reset();
        clearError();
        hideResults();
        elements.urlInput.focus();
    }

    /**
     * Show toast notification
     * @param {HTMLElement} toast - Toast element to show
     */
    function showToast(toast) {
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
    }

    /**
     * Copy text to clipboard
     * @param {string} text - Text to copy
     * @returns {Promise<boolean>} Success status
     */
    async function copyToClipboard(text) {
        try {
            // Try modern clipboard API first
            if (navigator.clipboard && navigator.clipboard.writeText) {
                await navigator.clipboard.writeText(text);
                return true;
            }

            // Fallback to older method
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();

            const result = document.execCommand('copy');
            document.body.removeChild(textArea);
            return result;
        } catch (err) {
            console.error('Failed to copy:', err);
            return false;
        }
    }

    /**
     * Update copy button state
     * @param {boolean} copied - Whether text was copied
     */
    function updateCopyButton(copied) {
        if (copied) {
            elements.copyBtn.classList.add('copied');
            elements.copyBtn.innerHTML = '<i class="bi bi-check-lg"></i>';
            showToast(elements.copyToast);

            // Reset after 2 seconds
            setTimeout(() => {
                elements.copyBtn.classList.remove('copied');
                elements.copyBtn.innerHTML = '<i class="bi bi-clipboard"></i>';
            }, 2000);
        }
    }

    // ============================================================
    // API Functions
    // ============================================================

    /**
     * Send shorten URL request to API
     * @param {string} url - URL to shorten
     * @returns {Promise<object>} API response
     */
    async function shortenUrl(url) {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), CONFIG.requestTimeout);

        try {
            const response = await fetch(CONFIG.apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ url: url }),
                signal: controller.signal,
            });

            clearTimeout(timeoutId);

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Failed to shorten URL');
            }

            return data;
        } catch (error) {
            clearTimeout(timeoutId);

            if (error.name === 'AbortError') {
                throw new Error('Request timed out. Please try again.');
            }

            throw error;
        }
    }

    // ============================================================
    // Event Handlers
    // ============================================================

    /**
     * Handle form submission
     * @param {Event} event - Form submit event
     */
    async function handleFormSubmit(event) {
        event.preventDefault();
        clearError();

        const url = elements.urlInput.value.trim();

        // Client-side validation
        if (!url) {
            showError('Please enter a URL');
            elements.urlInput.focus();
            return;
        }

        // Basic URL format validation
        try {
            new URL(url);
        } catch (e) {
            showError('Please enter a valid URL (e.g., https://example.com)');
            elements.urlInput.focus();
            return;
        }

        setLoading(true);

        try {
            const response = await shortenUrl(url);

            if (response.status === 'success') {
                const result = response.data;

                // Update UI with results
                state.currentShortUrl = result.short_url;
                elements.shortUrlText.textContent = result.short_url;
                elements.qrCodeImg.src = result.qr_code;
                elements.qrCodeImg.alt = 'QR Code for ' + result.short_url;
                elements.visitLink.href = result.short_url;

                showResults();
            } else {
                showError(response.message || 'Failed to shorten URL');
            }
        } catch (error) {
            console.error('Shorten error:', error);
            showError(error.message || 'An error occurred. Please try again.');
        } finally {
            setLoading(false);
        }
    }

    /**
     * Handle copy button click
     */
    async function handleCopyClick() {
        if (state.currentShortUrl) {
            const success = await copyToClipboard(state.currentShortUrl);
            updateCopyButton(success);
        }
    }

    /**
     * Handle new link button click
     */
    function handleNewLinkClick() {
        resetForm();
    }

    /**
     * Handle input change (clear error on typing)
     */
    function handleInputChange() {
        if (elements.urlInput.classList.contains('is-invalid')) {
            clearError();
        }
    }

    // ============================================================
    // Initialize Application
    // ============================================================

    /**
     * Initialize event listeners
     */
    function initEventListeners() {
        // Form submission
        elements.form.addEventListener('submit', handleFormSubmit);

        // Copy button
        elements.copyBtn.addEventListener('click', handleCopyClick);

        // New link button
        elements.newLinkBtn.addEventListener('click', handleNewLinkClick);

        // Input change (clear error)
        elements.urlInput.addEventListener('input', handleInputChange);

        // Enter key in input field
        elements.urlInput.addEventListener('keypress', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                elements.form.dispatchEvent(new Event('submit'));
            }
        });
    }

    /**
     * Initialize the application
     */
    function init() {
        initEventListeners();
        elements.urlInput.focus();
        console.log('URL Shortener initialized');
    }

    // Start the application when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
