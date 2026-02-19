/**
 * Modal Replacement Helper
 * This script wraps the native alert/confirm/prompt to use custom modals
 * Add this after custom-modals.js to automatically replace all calls
 */

// Store original functions
window._originalAlert = window.alert;
window._originalConfirm = window.confirm;
window._originalPrompt = window.prompt;

// Replace alert with custom modal
window.alert = function(message) {
    // For synchronous contexts, we need to handle this carefully
    if (window.customAlert) {
        // Create a promise but don't wait for it in sync context
        customAlert(String(message), 'Notice').catch(err => {
            console.error('Custom alert error:', err);
            window._originalAlert(message);
        });
        // Return undefined like native alert
        return undefined;
    } else {
        return window._originalAlert(message);
    }
};

// Replace confirm with custom modal
window.confirm = function(message) {
    // Confirm is synchronous, so we can't use async here
    // Log a warning and use original
    console.warn('Synchronous confirm() called. Please convert to: const confirmed = await customConfirm(message);');
    return window._originalConfirm(message);
};

// Replace prompt with custom modal
window.prompt = function(message, defaultValue) {
    // Prompt is synchronous, so we can't use async here
    // Log a warning and use original
    console.warn('Synchronous prompt() called. Please convert to: const result = await customPrompt(message, defaultValue);');
    return window._originalPrompt(message, defaultValue);
};

// Helper function to convert alert calls in async context
window.asyncAlert = async function(message, title = 'Notice') {
    if (window.customAlert) {
        await customAlert(message, title);
    } else {
        window._originalAlert(message);
    }
};

// Helper function to convert confirm calls in async context
window.asyncConfirm = async function(message, title = 'Confirm') {
    if (window.customConfirm) {
        return await customConfirm(message, title);
    } else {
        return window._originalConfirm(message);
    }
};

console.log('Modal replacer loaded - alert() calls will use custom modals');
