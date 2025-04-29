import { log, debounce, buildPriceLine, showLoadingSpinner, showError, displayCustomMessage } from './utils.js';
import { renderListLayout, renderGridLayout, loadTemplateStyles, renderNamedTemplate } from './templates.js';


document.addEventListener("DOMContentLoaded", () => {
    let excludedProducts = new Set(); // Track added products

    /**
     * Asynchronously fetches order bump products from the server
     */
    const fetchOrderBumpProducts = async (container) => {
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

    /**
     * Renders the order bump products in the chosen layout
     */
    const renderOrderBumpProducts = (container, products) => {
        log(`renderOrderBumpProducts: Rendering for bump ID ${container.dataset.bumpId}`, products);

        // If no products, display a message
        if (!products.length) {
            container.innerHTML = "<p>No products available for the order bump.</p>";
            return;
        }

        // Determine layout: list, grid, or a custom template like "template-1"
        const layout = container.dataset.layout || 'list';
        log(`layout: ${layout}`);

        let htmlOutput = '';
        switch (layout) {
            case 'grid':
                htmlOutput = renderGridLayout(container, products);
                break;
            case 'list':
                htmlOutput = renderListLayout(container, products);
                break;
            default:
                // If layout starts with "template-", handle it
                if (layout.startsWith('template-')) {
                    htmlOutput = renderNamedTemplate(container, products, layout);
                } else {
                    // fallback to list if unknown
                    htmlOutput = renderListLayout(container, products);
                }
                break;
        }

        container.innerHTML = htmlOutput;
        log(`renderOrderBumpProducts: Products rendered for bump ID ${container.dataset.bumpId}`);
    };

    /**
     * Event delegation for "Add to Cart" clicks
     */
    document.body.addEventListener("click", (event) => {
        if (event.target.classList.contains("add-to-cart")) {
            const productElement = event.target.closest('.order-bump-product');
            if (!productElement) {
                // Defensive check in case structure changes
                log("No .order-bump-product found for add-to-cart click");
                return;
            }
            const productId = productElement.dataset.productId;
            log(`addToCart: Button clicked for product ID ${productId}`);
            const bumpId = productElement.dataset.bumpId;
            addToCart(productId, bumpId, event.target, productElement);
        }
    });

    /**
     * Adds product to cart via AJAX
     */
    const addToCart = async (productId, bumpId, button, productElement) => {
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

    /**
     * Triggers WooCommerce cart update events
     */
    const triggerCartUpdate = () => {
        jQuery("body").trigger("updated_cart_totals");
        jQuery("body").trigger("update_checkout");
        log("triggerCartUpdate: Dispatched updated_cart_totals and update_checkout events.");
    };

    /**
     * Initial load: fetch products for each .order-bump-container
     */
    log("Fetching products on page load");
    document.querySelectorAll(".order-bump-container").forEach(container => {
        fetchOrderBumpProducts(container);
    });

    /**
     * Re-fetch products when the cart is updated, debounced
     */
    jQuery("body").on("updated_cart_totals update_checkout", debounce(() => {
        log("update_checkout: Re-fetching order bump products");
        document.querySelectorAll(".order-bump-container").forEach(container => {
            fetchOrderBumpProducts(container);
        });
    }, 300));
});