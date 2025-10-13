// File: public/js/voucher.js - Frontend Voucher System Integration

class VoucherManager {
    constructor() {
        this.isApplying = false;
        this.appliedCoupon = null;
        this.subtotal = 0;
        this.shippingCost = 0;

        this.init();
    }

    init() {
        console.log("🎫 VoucherManager initialized");

        // Get initial values
        this.updateSubtotalFromMeta();

        // Setup event listeners
        this.setupEventListeners();

        // Load current applied coupon
        this.loadCurrentCoupon();

        // Load available coupons
        this.loadAvailableCoupons();
    }

    setupEventListeners() {
        // Apply coupon button
        const applyBtn = document.getElementById("apply-coupon-btn");
        if (applyBtn) {
            applyBtn.addEventListener("click", () => this.applyCoupon());
        }

        // Remove coupon button
        const removeBtn = document.getElementById("remove-coupon-btn");
        if (removeBtn) {
            removeBtn.addEventListener("click", () => this.removeCoupon());
        }

        // Coupon code input - Enter key
        const couponInput = document.getElementById("coupon-code");
        if (couponInput) {
            couponInput.addEventListener("keypress", (e) => {
                if (e.key === "Enter") {
                    e.preventDefault();
                    this.applyCoupon();
                }
            });

            // Real-time validation (debounced)
            let validationTimeout;
            couponInput.addEventListener("input", (e) => {
                clearTimeout(validationTimeout);
                const code = e.target.value.trim();

                if (code.length >= 3) {
                    validationTimeout = setTimeout(() => {
                        this.validateCoupon(code);
                    }, 500);
                } else {
                    this.clearValidationMessage();
                }
            });
        }

        // Available coupon quick apply buttons
        document.addEventListener("click", (e) => {
            if (e.target.classList.contains("quick-apply-coupon")) {
                const code = e.target.dataset.code;
                if (code) {
                    this.quickApplyCoupon(code);
                }
            }
        });

        // Listen for cart/shipping changes
        document.addEventListener("cartUpdated", () => {
            this.revalidateAppliedCoupon();
        });

        document.addEventListener("shippingUpdated", (e) => {
            this.shippingCost = e.detail.cost || 0;
            this.revalidateAppliedCoupon();
        });
    }

    updateSubtotalFromMeta() {
        const subtotalMeta = document.querySelector(
            'meta[name="cart-subtotal"]'
        );
        if (subtotalMeta) {
            this.subtotal = parseInt(subtotalMeta.content) || 0;
        }
    }

    async applyCoupon() {
        if (this.isApplying) {
            return;
        }

        const couponInput = document.getElementById("coupon-code");
        const applyBtn = document.getElementById("apply-coupon-btn");

        if (!couponInput) {
            console.error("Coupon input not found");
            return;
        }

        const code = couponInput.value.trim();

        if (!code) {
            this.showMessage("Please enter a coupon code", "error");
            return;
        }

        this.isApplying = true;
        this.updateApplyButton(true);

        try {
            const response = await fetch("/api/coupons/apply", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector(
                        'meta[name="csrf-token"]'
                    ).content,
                    Accept: "application/json",
                },
                body: JSON.stringify({ code: code }),
            });

            const result = await response.json();

            if (result.success) {
                this.appliedCoupon = result.coupon;

                // Update UI
                this.showAppliedCoupon(result.coupon);
                this.updateOrderSummary(result.totals);
                this.showMessage(`✅ ${result.message}`, "success");

                // Clear input
                couponInput.value = "";

                console.log("✅ Coupon applied successfully:", result.coupon);

                // Dispatch event for other components
                document.dispatchEvent(
                    new CustomEvent("couponApplied", {
                        detail: {
                            coupon: result.coupon,
                            totals: result.totals,
                        },
                    })
                );
            } else {
                this.showMessage(`❌ ${result.message}`, "error");
                console.warn("❌ Coupon application failed:", result.message);
            }
        } catch (error) {
            console.error("❌ Error applying coupon:", error);
            this.showMessage(
                "❌ Failed to apply coupon. Please try again.",
                "error"
            );
        } finally {
            this.isApplying = false;
            this.updateApplyButton(false);
        }
    }

    async removeCoupon() {
        if (!this.appliedCoupon) {
            return;
        }

        try {
            const response = await fetch("/api/coupons/remove", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector(
                        'meta[name="csrf-token"]'
                    ).content,
                    Accept: "application/json",
                },
            });

            const result = await response.json();

            if (result.success) {
                this.appliedCoupon = null;

                // Update UI
                this.hideAppliedCoupon();
                this.updateOrderSummary(result.totals);
                this.showMessage("✅ Coupon removed successfully", "success");

                console.log("✅ Coupon removed successfully");

                // Dispatch event
                document.dispatchEvent(
                    new CustomEvent("couponRemoved", {
                        detail: { totals: result.totals },
                    })
                );
            } else {
                this.showMessage(`❌ ${result.message}`, "error");
            }
        } catch (error) {
            console.error("❌ Error removing coupon:", error);
            this.showMessage(
                "❌ Failed to remove coupon. Please try again.",
                "error"
            );
        }
    }

    async validateCoupon(code) {
        try {
            const response = await fetch("/api/coupons/validate", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector(
                        'meta[name="csrf-token"]'
                    ).content,
                    Accept: "application/json",
                },
                body: JSON.stringify({ code: code }),
            });

            const result = await response.json();

            if (result.valid) {
                this.showValidationMessage(
                    `✅ Valid coupon! Save ${result.coupon.formatted_discount}`,
                    "success"
                );
            } else {
                this.showValidationMessage(`❌ ${result.message}`, "error");
            }
        } catch (error) {
            console.error("❌ Error validating coupon:", error);
            this.clearValidationMessage();
        }
    }

    async loadCurrentCoupon() {
        try {
            const response = await fetch("/api/coupons/current", {
                headers: {
                    Accept: "application/json",
                },
            });

            const result = await response.json();

            if (result.success && result.coupon) {
                this.appliedCoupon = result.coupon;
                this.showAppliedCoupon(result.coupon);
                console.log("📋 Current coupon loaded:", result.coupon);
            }
        } catch (error) {
            console.error("❌ Error loading current coupon:", error);
        }
    }

    async loadAvailableCoupons() {
        try {
            const response = await fetch("/api/coupons/available", {
                headers: {
                    Accept: "application/json",
                },
            });

            const result = await response.json();

            if (result.success && result.coupons.length > 0) {
                this.showAvailableCoupons(result.coupons);
                console.log(
                    "🎫 Available coupons loaded:",
                    result.coupons.length
                );
            }
        } catch (error) {
            console.error("❌ Error loading available coupons:", error);
        }
    }

    async quickApplyCoupon(code) {
        const couponInput = document.getElementById("coupon-code");
        if (couponInput) {
            couponInput.value = code;
        }
        await this.applyCoupon();
    }

    async revalidateAppliedCoupon() {
        if (!this.appliedCoupon) {
            return;
        }

        console.log(
            "🔄 Revalidating applied coupon due to cart/shipping change"
        );

        try {
            const response = await fetch("/api/coupons/current", {
                headers: {
                    Accept: "application/json",
                },
            });

            const result = await response.json();

            if (!result.success || !result.coupon) {
                // Coupon is no longer valid
                this.appliedCoupon = null;
                this.hideAppliedCoupon();
                this.showMessage(
                    "⚠️ Your coupon was removed due to cart changes",
                    "warning"
                );

                console.log("⚠️ Applied coupon removed due to revalidation");
            } else {
                // Update coupon data if discount amount changed
                this.appliedCoupon = result.coupon;
                this.updateAppliedCouponDisplay(result.coupon);

                console.log("✅ Applied coupon revalidated");
            }
        } catch (error) {
            console.error("❌ Error revalidating coupon:", error);
        }
    }

    showAppliedCoupon(coupon) {
        const container = document.getElementById("applied-coupon-container");
        if (!container) {
            console.warn("Applied coupon container not found");
            return;
        }

        container.innerHTML = `
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <div class="font-medium text-green-800">${coupon.code}</div>
                            <div class="text-sm text-green-600">${coupon.summary}</div>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <span class="text-green-800 font-semibold mr-3">-${coupon.formatted_discount}</span>
                        <button type="button" id="remove-coupon-btn" 
                                class="text-red-600 hover:text-red-800 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        `;

        container.classList.remove("hidden");

        // Reattach remove button event listener
        const removeBtn = container.querySelector("#remove-coupon-btn");
        if (removeBtn) {
            removeBtn.addEventListener("click", () => this.removeCoupon());
        }

        // Hide coupon input section
        const inputSection = document.getElementById("coupon-input-section");
        if (inputSection) {
            inputSection.classList.add("hidden");
        }
    }

    updateAppliedCouponDisplay(coupon) {
        const container = document.getElementById("applied-coupon-container");
        if (!container) return;

        const discountSpan = container.querySelector(".font-semibold");
        if (discountSpan) {
            discountSpan.textContent = `-${coupon.formatted_discount}`;
        }
    }

    hideAppliedCoupon() {
        const container = document.getElementById("applied-coupon-container");
        if (container) {
            container.classList.add("hidden");
            container.innerHTML = "";
        }

        // Show coupon input section
        const inputSection = document.getElementById("coupon-input-section");
        if (inputSection) {
            inputSection.classList.remove("hidden");
        }
    }

    showAvailableCoupons(coupons) {
        const container = document.getElementById(
            "available-coupons-container"
        );
        if (!container) {
            console.warn("Available coupons container not found");
            return;
        }

        if (coupons.length === 0) {
            container.classList.add("hidden");
            return;
        }

        let html = `
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h4 class="text-sm font-medium text-blue-800 mb-3">💡 Available Coupons for Your Cart</h4>
                <div class="space-y-2">
        `;

        coupons.forEach((coupon) => {
            const expiringWarning = coupon.is_expiring_soon
                ? '<span class="text-xs text-orange-600">(Expires Soon!)</span>'
                : "";

            html += `
                <div class="flex items-center justify-between bg-white p-3 rounded border border-blue-200">
                    <div class="flex-1">
                        <div class="font-medium text-gray-900">${coupon.code}</div>
                        <div class="text-sm text-gray-600">${coupon.summary} ${expiringWarning}</div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-blue-600 font-semibold">${coupon.formatted_discount}</span>
                        <button type="button" 
                                class="quick-apply-coupon px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700 transition-colors"
                                data-code="${coupon.code}">
                            Apply
                        </button>
                    </div>
                </div>
            `;
        });

        html += `
                </div>
            </div>
        `;

        container.innerHTML = html;
        container.classList.remove("hidden");
    }

    updateOrderSummary(totals) {
        // Update subtotal
        const subtotalElements = document.querySelectorAll(
            "[data-subtotal-display]"
        );
        subtotalElements.forEach((el) => {
            el.textContent = totals.formatted.subtotal;
        });

        // Update shipping cost
        const shippingElements = document.querySelectorAll(
            "[data-shipping-display]"
        );
        shippingElements.forEach((el) => {
            el.textContent = totals.formatted.shipping_cost;
        });

        // Update discount amount
        const discountElements = document.querySelectorAll(
            "[data-discount-display]"
        );
        discountElements.forEach((el) => {
            if (totals.discount_amount > 0) {
                el.textContent = `-${totals.formatted.discount_amount}`;
                el.closest(".discount-row")?.classList.remove("hidden");
            } else {
                el.closest(".discount-row")?.classList.add("hidden");
            }
        });

        // Update total
        const totalElements = document.querySelectorAll("[data-total-display]");
        totalElements.forEach((el) => {
            el.textContent = totals.formatted.total;
        });

        console.log("📊 Order summary updated with coupon totals:", totals);
    }

    updateApplyButton(isLoading) {
        const btn = document.getElementById("apply-coupon-btn");
        if (!btn) return;

        if (isLoading) {
            btn.disabled = true;
            btn.innerHTML = `
                <div class="flex items-center justify-center">
                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                    Applying...
                </div>
            `;
        } else {
            btn.disabled = false;
            btn.textContent = "Apply";
        }
    }

    showMessage(message, type = "info") {
        const container = document.getElementById("coupon-message-container");
        if (!container) {
            console.warn("Coupon message container not found");
            return;
        }

        const bgColor =
            {
                success: "bg-green-100 border-green-400 text-green-700",
                error: "bg-red-100 border-red-400 text-red-700",
                warning: "bg-yellow-100 border-yellow-400 text-yellow-700",
                info: "bg-blue-100 border-blue-400 text-blue-700",
            }[type] || "bg-gray-100 border-gray-400 text-gray-700";

        container.innerHTML = `
            <div class="${bgColor} px-4 py-3 rounded border mb-4">
                ${message}
            </div>
        `;

        container.classList.remove("hidden");

        // Auto-hide after 5 seconds
        setTimeout(() => {
            container.classList.add("hidden");
        }, 5000);
    }

    showValidationMessage(message, type) {
        const container = document.getElementById("coupon-validation-message");
        if (!container) return;

        const textColor =
            type === "success" ? "text-green-600" : "text-red-600";

        container.innerHTML = `<div class="text-xs ${textColor} mt-1">${message}</div>`;
        container.classList.remove("hidden");
    }

    clearValidationMessage() {
        const container = document.getElementById("coupon-validation-message");
        if (container) {
            container.classList.add("hidden");
            container.innerHTML = "";
        }
    }
}

// Initialize when DOM is loaded
document.addEventListener("DOMContentLoaded", function () {
    // Only initialize if we're on checkout page
    if (document.getElementById("coupon-code")) {
        window.voucherManager = new VoucherManager();
        console.log("🎫 Voucher system initialized");
    }
});

// Export for global access
window.VoucherManager = VoucherManager;
