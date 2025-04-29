import { log, buildPriceLine } from './utils.js';

/**
 * Renders products using a default list layout (row-based)
 */
export function renderListLayout(container, products) {
    let html = '<div class="list-layout">';

    products.forEach(product => {
        const priceLine = buildPriceLine(product);
        html += `
            <div class="order-bump-product list-item"
                 data-bump-id="${container.dataset.bumpId}" data-product-id="${product.id}"
                 ${product.discount_type ? `data-discount-type="${product.discount_type}"` : ''}
                 ${product.discount ? `data-discount="${product.discount}"` : ''}
                 data-quantity="${product.quantity || 1}">
                 
                <div class="list-left">
                    <img src="${product.image}" alt="${product.name}" />
                </div>

                <div class="list-center">
                    <p class="product-name">${product.name}</p>
                    <div class="price-line">${priceLine}</div>
                </div>

                <div class="list-right">
                    <button type="button" class="add-to-cart" data-product-id="${product.id}">Add</button>
                </div>
            </div>
        `;
    });

    html += '</div>';
    return html;
}

/**
 * Renders products in a grid layout
 */
export function renderGridLayout(container, products) {
    let html = '<div class="grid-layout">';
    products.forEach(product => {
        const priceLine = buildPriceLine(product);
        html += `
            <div class="order-bump-product grid-item" data-bump-id="${container.dataset.bumpId}" data-product-id="${product.id}" 
                 ${product.discount_type ? `data-discount-type="${product.discount_type}"` : ''} 
                 ${product.discount ? `data-discount="${product.discount}"` : ''} 
                 data-quantity="${product.quantity || 1}">

                <img src="${product.image}" alt="${product.name}" />
                <h3>${product.name}</h3>
                <div class="price-line">${priceLine}</div>
                <button type="button" class="add-to-cart" data-product-id="${product.id}">Add this</button>
            </div>
        `;
    });
    html += '</div>';
    return html;
}



export function loadTemplateStyles(templateName) {
    const cssId = `css-${templateName}`;
    if (!document.getElementById(cssId)) {
        const style = document.createElement("style");
        style.id = cssId;
        style.innerHTML = orderBumpConfig.templates[templateName].css;
        document.head.appendChild(style);
    }
}

/**
 * Renders products using a named template (e.g., "template-1") from orderBumpConfig.templates
 */
export function renderNamedTemplate(container, products, templateName) {
    log(`renderNamedTemplate: Start for template "${templateName}"`);

    // Load CSS dynamically
    loadTemplateStyles(templateName);
    
    const templateSnippet = orderBumpConfig.templates[templateName]?.html || '';
    if (!templateSnippet) {
        log(`renderNamedTemplate: No template snippet found for "${templateName}". Falling back to list layout.`);
        return renderListLayout(products);
    }

    let html = `<div class="custom-layout ${templateName}">`;

    products.forEach(product => {
        let itemHtml = templateSnippet;

        // Log placeholders for debugging
        log("renderNamedTemplate: Before replacements =>", itemHtml);

        // Replace placeholders with product data
        itemHtml = itemHtml
            .replace(/{{PRODUCT_ID}}/g, product.id)
            .replace(/{{PRODUCT_NAME}}/g, product.name)
            .replace(/{{PRICE}}/g, product.price)
            .replace(/{{REGULAR_PRICE}}/g, product.regular_price || '')
            .replace(/{{QUANTITY}}/g, product.quantity || 1)
            .replace(/{{IMAGE}}/g, product.image || '');

        if (product.discount_type) {
            itemHtml = itemHtml.replace(/{{DISCOUNT_TYPE}}/g, product.discount_type);
        }
        if (product.discount) {
            itemHtml = itemHtml.replace(/{{DISCOUNT}}/g, product.discount);
        }

        // Convert HTML string to DOM element and append `data-bump-id`
        const tempDiv = document.createElement("div");
        tempDiv.innerHTML = itemHtml.trim();
        const productElement = tempDiv.firstChild;

        if (productElement.classList.contains("order-bump-product")) {
            productElement.setAttribute("data-bump-id", container.dataset.bumpId);
        }

        log("renderNamedTemplate: After adding bump ID =>", productElement.outerHTML);
        html += productElement.outerHTML;
    });


    html += '</div>';
    log(`renderNamedTemplate: End for template "${templateName}"`);
    return html;
}