export const isDev = true;

/**
 * Debug logger
 */
export const log = (message, ...args) => {
    if (isDev) {
        console.log(message, ...args);
    }
};

/**
 * Simple debounce utility
 */
export const debounce = (func, wait, immediate) => {
    let timeout;
    return function () {
        const context = this;
        const args = arguments;
        const later = function () {
            timeout = null;
            if (!immediate) func.apply(context, args);
        };
        const callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func.apply(context, args);
    };
};

/**
 * Builds the price/quantity markup for a product
 */
export function buildPriceLine(product) {
    let priceLine = '';
    
    // Show both regular and discounted price if they differ
    if (product.regular_price && product.regular_price !== product.price) {
        priceLine = `
            <p class="regular-price"><del>${product.regular_price}</del></p>
            <p class="discounted-price">${product.price}</p>
        `;
    } else {
        // Show single price
        priceLine = `<p>${product.price}</p>`;
    }
    
    // Add quantity if available
    if (product.quantity) {
        priceLine += `<p class="product-quantity">X ${product.quantity}</p>`;
    }
    
    return priceLine;
}

/**
 * Displays a loading spinner in the container
 */
export const showLoadingSpinner = (container) => {
    log(`showLoadingSpinner: Loading for bump ID ${container.dataset.bumpId}`);
    container.innerHTML = '<p>Loading...</p>';
};

/**
 * Displays an error message in the container
 */
export const showError = (container, message = "Failed to load products. Please try again.") => {
    log(`showError for bump ID ${container.dataset.bumpId}: ${message}`);
    container.innerHTML = `<p style="color: red;">${message}</p>`;
};


/**
 * Displays a custom success/error message above the checkout order review
 */
export const displayCustomMessage = (message) => {
    log("displayCustomMessage: Displaying custom message", message);
    const messageDiv = document.createElement("div");
    messageDiv.className = "custom-checkout-message";
    messageDiv.style.cssText = "padding: 10px; background: #e0ffe0; border: 1px solid #00a000; margin-top: 10px;";
    messageDiv.innerHTML = `<p>${message}</p>`;
    document.getElementById("order_review").prepend(messageDiv);
    setTimeout(() => messageDiv.remove(), 5000);
};