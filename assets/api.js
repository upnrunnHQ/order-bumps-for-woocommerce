import { log } from './utils.js';

/**
 * Asynchronously fetches order bump products from the server
 */
export const fetchOrderBumpProducts = async (container) => {
    const bumpId = container.dataset.bumpId;
    log(`fetchOrderBumpProducts: Fetching products for bump ID ${bumpId}`);
    showLoadingSpinner(container);

    try {
        const response = await fetch(
            `${orderBumpConfig.ajaxUrl}?action=get_order_bump_products&bump_id=${bumpId}&nonce=${orderBumpConfig.nonce}`
        );
        const data = await response.json();
        log(`fetchOrderBumpProducts: Received response for bump ID ${bumpId}`, data);

        if (data.success) {
            // Filter out excluded products
            const filteredProducts = data.data.filter(product => !excludedProducts.has(`${product.id}-${bumpId}`));
            log(`fetchOrderBumpProducts: Filtered products for bump ID ${bumpId}`, filteredProducts);
            renderOrderBumpProducts(container, filteredProducts);
        } else {
            showError(container, "No products available for the order bump.");
        }
    } catch (error) {
        console.error(`fetchOrderBumpProducts: Error fetching products for bump ID ${bumpId}`, error);
        showError(container);
    }
};


export const addToCart = async (productId, bumpId, productElement) => {
    log(`Adding product ID ${productId} to cart`);

    const discountType = productElement.dataset.discountType || '';
    const discount = productElement.dataset.discount || '';
    const quantity = productElement.dataset.quantity || 1;

    try {
        const response = await fetch(orderBumpConfig.ajaxUrl, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({
                action: "add_product_to_cart",
                product_id: productId,
                quantity: quantity,
                discount_type: discountType,
                discount: discount
            }),
        });
        return await response.json();
    } catch (error) {
        log("Error adding product to cart", error);
        return { success: false };
    }
};
