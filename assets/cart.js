import { log } from './utils.js';

export const triggerCartUpdate = () => {
    jQuery("body").trigger("updated_cart_totals");
    jQuery("body").trigger("update_checkout");
    log("Cart updated.");
};

export const displayCustomMessage = (message) => {
    log("Displaying message:", message);
    const messageDiv = document.createElement("div");
    messageDiv.className = "custom-checkout-message";
    messageDiv.innerHTML = `<p>${message}</p>`;
    document.getElementById("order_review").prepend(messageDiv);
    setTimeout(() => messageDiv.remove(), 5000);
};

/**
 * Adds product to cart via AJAX
 */
export const addToCart = async (productId, bumpId, button, productElement) => {
    log(`addToCart: Adding product ID ${productId} to cart`);
    const discountType = productElement.dataset.discountType || '';
    const discount = productElement.dataset.discount || '';
    const quantity = productElement.dataset.quantity || 1;

    button.disabled = true;
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
        const data = await response.json();
        log("addToCart: AJAX response received", data);

        if (data.success) {
            excludedProducts.add(`${productId}-${bumpId}`);
            productElement.remove();
            log(`addToCart: Product ID ${productId} removed from DOM`);
            displayCustomMessage("Product successfully added to the cart!");
            triggerCartUpdate();
        } else {
            log("addToCart: Error adding product to cart", data.message);
            alert(data.message);
        }
    } catch (error) {
        log("addToCart: AJAX error", error);
        alert("An error occurred while adding the product. Please try again.");
    } finally {
        button.disabled = false;
    }
};
