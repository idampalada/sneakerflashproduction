// File: public/js/voucher-checkout.js - Frontend Voucher System Integration

class VoucherManager {
    constructor() {
        this.isApplying = false;
        this.appliedVoucher = null;
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

        // Load current applied voucher
        this.loadCurrentVoucher();

        // Load available vouchers
        this.loadAvailableVouchers();
    }

    setupEventListeners() {
        // Apply voucher button
        const applyBtn = document.getElementById("apply-voucher-btn");
        if (applyBtn) {
            applyBtn.addEventListener("click", () => this.applyVoucher());
        }

        // Remove voucher button
        const removeBtn = document.getElementById("remove-voucher-btn");
        if (removeBtn) {
            removeBtn.addEventListener("click", () => this.removeVoucher());
        }

        // Voucher code input - Enter key
        const voucherInput = document.getElementById("voucher-code");
        if (voucherInput) {
            voucherInput.addEventListener("keypress", (e) => {
                if (e.key === "Enter") {
                    e.preventDefault();
                    this.applyVoucher();
                }
            });

            // Real-time validation (debounced)
            let validationTimeout;
            voucherInput.addEventListener("input", (e) => {
                clearTimeout(validationTimeout);
                const code = e.target.value.trim();

                if (code.length >= 3) {
                    validationTimeout = setTimeout(() => {
                        this.validateVoucher(code);
                    }, 500);
                } else {
                    this.clearValidationMessage();
                }
            });
        }

        // Available voucher quick apply buttons
        document.addEventListener("click", (e) => {
            if (e.target.classList.contains("quick-apply-voucher")) {
                const code = e.target.dataset.code;
                if (code) {
                    this.quickApplyVoucher(code);
                }
            }
        });

        // Listen for cart/shipping changes
        document.addEventListener("cartUpdated", () => {
            this.revalidateAppliedVoucher();
        });

        document.addEventListener("shippingUpdated", (e) => {
            this.shippingCost = e.detail.cost || 0;
            this.revalidateAppliedVoucher();
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

    async applyVoucher() {
        if (this.isApplying) {
            return;
        }

        const voucherInput = document.getElementById("voucher-code");
        const applyBtn = document.getElementById("apply-voucher-btn");

        if (!voucherInput) {
            console.error("Voucher input not found");
            return;
        }

        const code = voucherInput.value.trim();

        if (!code) {
            this.showMessage("Please enter a voucher code", "error");
            return;
        }

        this.isApplying = true;
        this.updateApplyButton(true);

        try {
            const response = await fetch("/api/vouchers/apply", {
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
                this.appliedVoucher = result.voucher;

                // Update UI
                this.showAppliedVoucher(result.voucher);
                this.updateOrderSummary(result.totals);
                this.updateHiddenInputs(result.voucher);
                this.showMessage(`✅ ${result.message}`, "success");

                // Clear input
                voucherInput.value = "";

                console.log("✅ Voucher applied successfully:", result.voucher);

                // Dispatch event for other components
                document.dispatchEvent(
                    new CustomEvent("voucherApplied", {
                        detail: {
                            voucher: result.voucher,
                            totals: result.totals,
                        },
                    })
                );
            } else {
                this.showMessage(`❌ ${result.message}`, "error");
                console.warn("❌ Voucher application failed:", result.message);
            }
        } catch (error) {
            console.error("❌ Error applying voucher:", error);
            this.showMessage(
                "❌ Failed to apply voucher. Please try again.",
                "error"
            );
        } finally {
            this.isApplying = false;
            this.updateApplyButton(false);
        }
    }

    async removeVoucher() {
        if (!this.appliedVoucher) {
            return;
        }

        try {
            const response = await fetch("/api/vouchers/remove", {
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
                this.appliedVoucher = null;

                // Update UI
                this.hideAppliedVoucher();
                this.updateOrderSummary(result.totals);
                this.clearHiddenInputs();
                this.showMessage("✅ Voucher removed successfully", "success");

                console.log("✅ Voucher removed successfully");

                // Dispatch event
                document.dispatchEvent(
                    new CustomEvent("voucherRemoved", {
                        detail: { totals: result.totals },
                    })
                );
            } else {
                this.showMessage(`❌ ${result.message}`, "error");
            }
        } catch (error) {
            console.error("❌ Error removing voucher:", error);
            this.showMessage(
                "❌ Failed to remove voucher. Please try again.",
                "error"
            );
        }
    }

    async validateVoucher(code) {
        try {
            const response = await fetch("/api/vouchers/validate", {
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
                    `✅ Valid voucher! Save ${result.voucher.formatted_discount}`,
                    "success"
                );
            } else {
                this.showValidationMessage(`❌ ${result.message}`, "error");
            }
        } catch (error) {
            console.error("❌ Error validating voucher:", error);
            this.clearValidationMessage();
        }
    }

    async loadCurrentVoucher() {
        try {
            const response = await fetch("/api/vouchers/current", {
                headers: {
                    Accept: "application/json",
                },
            });

            const result = await response.json();

            if (result.success && result.voucher) {
                this.appliedVoucher = result.voucher;
                this.showAppliedVoucher(result.voucher);
                this.updateHiddenInputs(result.voucher);
                console.log("📋 Current voucher loaded:", result.voucher);
            }
        } catch (error) {
            console.error("❌ Error loading current voucher:", error);
        }
    }

    async loadAvailableVouchers() {
        try {
            const response = await fetch("/api/vouchers/available", {
                headers: {
                    Accept: "application/json",
                },
            });

            const result = await response.json();

            if (result.success && result.vouchers.length > 0) {
                this.showAvailableVouchers(result.vouchers);
                console.log(
                    "🎫 Available vouchers loaded:",
                    result.vouchers.length
                );
            }
        } catch (error) {
            console.error("❌ Error loading available vouchers:", error);
        }
    }

    async quickApplyVoucher(code) {
        const voucherInput = document.getElementById("voucher-code");
        if (voucherInput) {
            voucherInput.value = code;
        }
        await this.applyVoucher();
    }

    async revalidateAppliedVoucher() {
        if (!this.appliedVoucher) {
            return;
        }

        console.log(
            "🔄 Revalidating applied voucher due to cart/shipping change"
        );

        try {
            const response = await fetch("/api/vouchers/current", {
                headers: {
                    Accept: "application/json",
                },
            });

            const result = await response.json();

            if (!result.success || !result.voucher) {
                // Voucher is no longer valid
                this.appliedVoucher = null;
                this.hideAppliedVoucher();
                this.clearHiddenInputs();
                this.showMessage(
                    "⚠️ Your voucher was removed due to cart changes",
                    "warning"
                );

                console.log("⚠️ Applied voucher removed due to revalidation");
            } else {
                // Update voucher data if discount amount changed
                this.appliedVoucher = result.voucher;
                this.updateAppliedVoucherDisplay(result.voucher);
                this.updateHiddenInputs(result.voucher);

                console.log("✅ Applied voucher revalidated");
            }
        } catch (error) {
            console.error("❌ Error revalidating voucher:", error);
        }
    }

    showAppliedVoucher(voucher) {
        const container = document.getElementById("applied-voucher-container");
        if (!container) {
            console.warn("Applied voucher container not found");
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
                            <div class="font-medium text-green-800">${
                                voucher.voucher_code
                            }</div>
                            <div class="text-sm text-green-600">${
                                voucher.name || "Voucher Applied"
                            }</div>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <span class="text-green-800 font-semibold mr-3">-${
                            voucher.formatted_discount
                        }</span>
                        <button type="button" id="remove-voucher-btn" 
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
        const removeBtn = container.querySelector("#remove-voucher-btn");
        if (removeBtn) {
            removeBtn.addEventListener("click", () => this.removeVoucher());
        }

        // Hide voucher input section
        const inputSection = document.getElementById("voucher-input-section");
        if (inputSection) {
            inputSection.classList.add("hidden");
        }
    }

    updateAppliedVoucherDisplay(voucher) {
        const container = document.getElementById("applied-voucher-container");
        if (!container) return;

        const discountSpan = container.querySelector(".font-semibold");
        if (discountSpan) {
            discountSpan.textContent = `-${voucher.formatted_discount}`;
        }
    }

    hideAppliedVoucher() {
        const container = document.getElementById("applied-voucher-container");
        if (container) {
            container.classList.add("hidden");
            container.innerHTML = "";
        }

        // Show voucher input section
        const inputSection = document.getElementById("voucher-input-section");
        if (inputSection) {
            inputSection.classList.remove("hidden");
        }
    }

    showAvailableVouchers(vouchers) {
        const container = document.getElementById(
            "available-vouchers-container"
        );
        if (!container) {
            console.warn("Available vouchers container not found");
            return;
        }

        if (vouchers.length === 0) {
            container.classList.add("hidden");
            return;
        }

        let html = `
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h4 class="text-sm font-medium text-blue-800 mb-3">💡 Available Vouchers for Your Cart</h4>
                <div class="space-y-2">
        `;

        vouchers.forEach((voucher) => {
            const expiringWarning =
                voucher.remaining_quota <= 5
                    ? '<span class="text-xs text-orange-600">(Limited Stock!)</span>'
                    : "";

            html += `
                <div class="flex items-center justify-between bg-white p-3 rounded border border-blue-200">
                    <div class="flex-1">
                        <div class="font-medium text-gray-900">${
                            voucher.voucher_code
                        }</div>
                        <div class="text-sm text-gray-600">${
                            voucher.name || "Special Discount"
                        } ${expiringWarning}</div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-blue-600 font-semibold">${
                            voucher.formatted_discount
                        }</span>
                        <button type="button" 
                                class="quick-apply-voucher px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700 transition-colors"
                                data-code="${voucher.voucher_code}">
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

        console.log("📊 Order summary updated with voucher totals:", totals);
    }

    updateHiddenInputs(voucher) {
        // Update hidden form inputs for voucher data
        const voucherCodeInput = document.getElementById(
            "applied_voucher_code"
        );
        const voucherDiscountInput = document.getElementById(
            "applied_voucher_discount"
        );

        if (voucherCodeInput) {
            voucherCodeInput.value = voucher.voucher_code || "";
        }

        if (voucherDiscountInput) {
            voucherDiscountInput.value = voucher.discount_amount || 0;
        }

        console.log("🔧 Hidden inputs updated:", {
            code: voucher.voucher_code,
            discount: voucher.discount_amount,
        });
    }

    clearHiddenInputs() {
        // Clear hidden form inputs
        const voucherCodeInput = document.getElementById(
            "applied_voucher_code"
        );
        const voucherDiscountInput = document.getElementById(
            "applied_voucher_discount"
        );

        if (voucherCodeInput) {
            voucherCodeInput.value = "";
        }

        if (voucherDiscountInput) {
            voucherDiscountInput.value = 0;
        }

        console.log("🔧 Hidden inputs cleared");
    }

    updateApplyButton(isLoading) {
        const btn = document.getElementById("apply-voucher-btn");
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
        const container = document.getElementById("voucher-message-container");
        if (!container) {
            console.warn("Voucher message container not found");
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
        const container = document.getElementById("voucher-validation-message");
        if (!container) return;

        const textColor =
            type === "success" ? "text-green-600" : "text-red-600";

        container.innerHTML = `<div class="text-xs ${textColor} mt-1">${message}</div>`;
        container.classList.remove("hidden");
    }

    clearValidationMessage() {
        const container = document.getElementById("voucher-validation-message");
        if (container) {
            container.classList.add("hidden");
            container.innerHTML = "";
        }
    }
}

// Initialize when DOM is loaded
document.addEventListener("DOMContentLoaded", function () {
    // Only initialize if we're on checkout page
    if (document.getElementById("voucher-code")) {
        window.voucherManager = new VoucherManager();
        console.log("🎫 Voucher system initialized");
    }
});

// Export for global access
window.VoucherManager = VoucherManager;
