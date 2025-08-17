/**
 * Points Exchange System for Checkout - Sederhana dan kompatibel dengan sistem existing
 * File: public/js/points-checkout.js
 */

class PointsCheckout {
    constructor() {
        this.userPointsBalance = 0;
        this.appliedPoints = 0;
        this.pointsDiscount = 0;
        this.isApplyingPoints = false;

        this.init();
    }

    init() {
        console.log("🪙 Points Checkout System initialized");

        // Get user points balance from meta tag or DOM
        this.loadUserPointsBalance();

        // Bind events
        this.bindEvents();

        // Load any existing applied points
        this.loadAppliedPoints();
    }

    loadUserPointsBalance() {
        // Get from meta tag
        const pointsMeta = document.querySelector(
            'meta[name="user-points-balance"]'
        );
        if (pointsMeta) {
            this.userPointsBalance = parseInt(pointsMeta.content) || 0;
        } else {
            // Try to get from checkbox data attribute
            const pointsToggle = document.getElementById("use-points-toggle");
            if (pointsToggle && pointsToggle.dataset.userPoints) {
                this.userPointsBalance =
                    parseInt(pointsToggle.dataset.userPoints) || 0;
            }
        }

        console.log("💰 User points balance:", this.userPointsBalance);
    }

    bindEvents() {
        // Points toggle checkbox
        const pointsToggle = document.getElementById("use-points-toggle");
        if (pointsToggle) {
            pointsToggle.addEventListener("change", (e) => {
                if (e.target.checked) {
                    this.enablePointsMode();
                } else {
                    this.disablePointsMode();
                }
            });
        }

        // Apply points button
        const applyPointsBtn = document.getElementById("apply-points-btn");
        if (applyPointsBtn) {
            applyPointsBtn.addEventListener("click", () =>
                this.applyCustomPoints()
            );
        }

        // Use all points button
        const useAllPointsBtn = document.getElementById("use-all-points");
        if (useAllPointsBtn) {
            useAllPointsBtn.addEventListener("click", () =>
                this.useAllPoints()
            );
        }

        // Remove points button
        const removePointsBtn = document.getElementById("remove-points-btn");
        if (removePointsBtn) {
            removePointsBtn.addEventListener("click", () =>
                this.removePoints()
            );
        }

        // Points amount input
        const pointsInput = document.getElementById("points-amount");
        if (pointsInput) {
            pointsInput.addEventListener("input", (e) => {
                this.validatePointsInput(e.target);
            });

            pointsInput.addEventListener("keypress", (e) => {
                if (e.key === "Enter") {
                    e.preventDefault();
                    this.applyCustomPoints();
                }
            });
        }

        // Listen for voucher changes to recalculate
        document.addEventListener("voucherApplied", () =>
            this.recalculateTotal()
        );
        document.addEventListener("voucherRemoved", () =>
            this.recalculateTotal()
        );
    }

    enablePointsMode() {
        // Show points details
        const pointsDetails = document.getElementById("points-details");
        if (pointsDetails) {
            pointsDetails.classList.remove("hidden");
        }

        // Auto-use all points (seperti referensi Shopee)
        this.useAllPoints();
    }

    disablePointsMode() {
        // Hide points details
        const pointsDetails = document.getElementById("points-details");
        if (pointsDetails) {
            pointsDetails.classList.add("hidden");
        }

        // Hide custom input
        const inputSection = document.getElementById("points-input-section");
        if (inputSection) {
            inputSection.classList.add("hidden");
        }

        // Remove applied points
        this.removePoints();
    }

    validatePointsInput(input) {
        const value = parseInt(input.value) || 0;
        const max = this.userPointsBalance;

        if (value > max) {
            input.value = max;
            this.showPointsMessage(
                `❌ Maksimal ${max.toLocaleString()} poin`,
                "error"
            );
        } else if (value < 0) {
            input.value = 0;
        }
    }

    async useAllPoints() {
        if (this.userPointsBalance <= 0) {
            this.showPointsMessage("❌ Tidak ada poin tersedia", "error");
            return;
        }

        await this.applyPoints(this.userPointsBalance);
    }

    async applyCustomPoints() {
        const pointsInput = document.getElementById("points-amount");
        if (!pointsInput) return;

        const amount = parseInt(pointsInput.value) || 0;

        if (amount <= 0) {
            this.showPointsMessage(
                "❌ Masukkan jumlah poin yang valid",
                "error"
            );
            return;
        }

        if (amount > this.userPointsBalance) {
            this.showPointsMessage(
                `❌ Poin tidak mencukupi. Maksimal: ${this.userPointsBalance.toLocaleString()}`,
                "error"
            );
            return;
        }

        await this.applyPoints(amount);
    }

    async applyPoints(amount) {
        if (this.isApplyingPoints) return;

        this.isApplyingPoints = true;

        try {
            // Validate amount
            if (amount > this.userPointsBalance) {
                throw new Error(
                    `Poin tidak mencukupi. Tersedia: ${this.userPointsBalance}`
                );
            }

            // Simple validation without API call - dapat diubah ke API call jika diperlukan
            const discount = amount; // 1 point = 1 rupiah

            // Update applied points
            this.appliedPoints = amount;
            this.pointsDiscount = discount;

            // Update UI
            this.showAppliedPoints(amount, discount);
            this.updateHiddenInputs(amount, discount);
            this.updateOrderSummary();

            // Show success message
            this.showPointsMessage(
                `✅ ${amount.toLocaleString()} poin berhasil diterapkan! Hemat Rp ${discount.toLocaleString()}`,
                "success"
            );

            // Hide input section
            const inputSection = document.getElementById(
                "points-input-section"
            );
            if (inputSection) {
                inputSection.classList.add("hidden");
            }

            console.log("✅ Points applied:", { amount, discount });

            // Dispatch event for other components
            document.dispatchEvent(
                new CustomEvent("pointsApplied", {
                    detail: { points: amount, discount },
                })
            );
        } catch (error) {
            console.error("❌ Error applying points:", error);
            this.showPointsMessage(`❌ ${error.message}`, "error");
        } finally {
            this.isApplyingPoints = false;
        }
    }

    async removePoints() {
        try {
            // Reset points
            this.appliedPoints = 0;
            this.pointsDiscount = 0;

            // Update UI
            this.hideAppliedPoints();
            this.updateHiddenInputs(0, 0);
            this.updateOrderSummary();

            // Uncheck toggle
            const pointsToggle = document.getElementById("use-points-toggle");
            if (pointsToggle) {
                pointsToggle.checked = false;
            }

            // Hide details
            const pointsDetails = document.getElementById("points-details");
            if (pointsDetails) {
                pointsDetails.classList.add("hidden");
            }

            this.showPointsMessage("✅ Penggunaan poin dibatalkan", "success");

            console.log("✅ Points removed");

            // Dispatch event
            document.dispatchEvent(new CustomEvent("pointsRemoved"));
        } catch (error) {
            console.error("❌ Error removing points:", error);
            this.showPointsMessage(
                "❌ Gagal membatalkan penggunaan poin",
                "error"
            );
        }
    }

    showAppliedPoints(amount, discount) {
        const container = document.getElementById("applied-points-container");
        if (!container) return;

        // Update content
        const usedAmountSpan = container.querySelector("#used-points-amount");
        const discountAmountSpan = container.querySelector(
            "#points-discount-amount"
        );

        if (usedAmountSpan) {
            usedAmountSpan.textContent = amount.toLocaleString();
        }

        if (discountAmountSpan) {
            discountAmountSpan.textContent = discount.toLocaleString();
        }

        // Show container
        container.classList.remove("hidden");
    }

    hideAppliedPoints() {
        const container = document.getElementById("applied-points-container");
        if (container) {
            container.classList.add("hidden");
        }
    }

    updateHiddenInputs(amount, discount) {
        // Update hidden form inputs
        const pointsUsedInput = document.getElementById("points_used");
        const pointsDiscountInput = document.getElementById("points_discount");

        if (pointsUsedInput) {
            pointsUsedInput.value = amount;
        }

        if (pointsDiscountInput) {
            pointsDiscountInput.value = discount;
        }
    }

    updateOrderSummary() {
        // Show/hide points discount row
        const pointsDiscountRow = document.querySelector(
            ".points-discount-row"
        );
        if (pointsDiscountRow) {
            if (this.pointsDiscount > 0) {
                pointsDiscountRow.classList.remove("hidden");

                const discountDisplay = pointsDiscountRow.querySelector(
                    "[data-points-discount-display]"
                );
                if (discountDisplay) {
                    discountDisplay.textContent = `-Rp ${this.pointsDiscount.toLocaleString()}`;
                }
            } else {
                pointsDiscountRow.classList.add("hidden");
            }
        }

        // Recalculate total
        this.recalculateTotal();
    }

    recalculateTotal() {
        try {
            // Get current values
            const subtotal = this.getSubtotal();
            const voucherDiscount = this.getVoucherDiscount();
            const shipping = this.getShippingCost();

            // Calculate new total
            const newTotal = Math.max(
                0,
                subtotal - voucherDiscount - this.pointsDiscount + shipping
            );

            // Update total display
            const totalDisplay = document.querySelector("[data-total-display]");
            if (totalDisplay) {
                totalDisplay.textContent = `Rp ${newTotal.toLocaleString()}`;
            }

            console.log("🧮 Order total recalculated:", {
                subtotal,
                voucherDiscount,
                pointsDiscount: this.pointsDiscount,
                shipping,
                total: newTotal,
            });
        } catch (error) {
            console.error("❌ Error recalculating total:", error);
        }
    }

    getSubtotal() {
        const meta = document.querySelector('meta[name="cart-subtotal"]');
        return meta ? parseInt(meta.content) || 0 : 0;
    }

    getVoucherDiscount() {
        const discountDisplay = document.querySelector(
            "[data-discount-display]"
        );
        if (discountDisplay && !discountDisplay.closest(".hidden")) {
            const text = discountDisplay.textContent.replace(/[^\d]/g, "");
            return parseInt(text) || 0;
        }
        return 0;
    }

    getShippingCost() {
        const shippingInput = document.getElementById("shipping_cost");
        return shippingInput ? parseInt(shippingInput.value) || 0 : 0;
    }

    showPointsMessage(message, type = "info") {
        const container = document.getElementById("points-message-container");
        if (!container) return;

        const bgColor =
            type === "success"
                ? "bg-green-50 border-green-200"
                : type === "error"
                ? "bg-red-50 border-red-200"
                : "bg-blue-50 border-blue-200";

        const textColor =
            type === "success"
                ? "text-green-800"
                : type === "error"
                ? "text-red-800"
                : "text-blue-800";

        container.innerHTML = `
            <div class="${bgColor} border rounded-lg p-3 mb-3">
                <p class="text-sm ${textColor}">${message}</p>
            </div>
        `;

        container.classList.remove("hidden");

        // Auto-hide after 5 seconds for success/error messages
        if (type === "success" || type === "error") {
            setTimeout(() => {
                container.classList.add("hidden");
            }, 5000);
        }
    }

    loadAppliedPoints() {
        // Check if there are already applied points from server-side
        const appliedPointsMeta = document.querySelector(
            'meta[name="applied-points-used"]'
        );
        const appliedDiscountMeta = document.querySelector(
            'meta[name="applied-points-discount"]'
        );

        if (appliedPointsMeta && appliedDiscountMeta) {
            const amount = parseInt(appliedPointsMeta.content) || 0;
            const discount = parseInt(appliedDiscountMeta.content) || 0;

            if (amount > 0) {
                this.appliedPoints = amount;
                this.pointsDiscount = discount;

                // Check the toggle
                const pointsToggle =
                    document.getElementById("use-points-toggle");
                if (pointsToggle) {
                    pointsToggle.checked = true;
                }

                // Show applied points
                this.showAppliedPoints(amount, discount);
                this.updateOrderSummary();

                console.log("📋 Existing points loaded from server:", {
                    amount,
                    discount,
                });
            }
        }
    }
}

// Initialize when DOM is ready
document.addEventListener("DOMContentLoaded", function () {
    // Only initialize if we're on checkout page and user has points
    const checkoutForm = document.getElementById("checkout-form");
    const pointsToggle = document.getElementById("use-points-toggle");

    if (checkoutForm && pointsToggle) {
        window.pointsCheckout = new PointsCheckout();
    }
});

// Enhanced checkout totals calculation with points support
function updateCheckoutTotals() {
    try {
        // Get all current values
        const subtotal =
            parseInt(
                document.querySelector('meta[name="cart-subtotal"]')?.content
            ) || 0;
        const shippingCost =
            parseInt(document.getElementById("shipping_cost")?.value) || 0;

        // Get voucher discount
        const voucherDiscountElement = document.querySelector(
            "[data-discount-display]"
        );
        const voucherDiscount =
            voucherDiscountElement && !voucherDiscountElement.closest(".hidden")
                ? parseInt(
                      voucherDiscountElement.textContent.replace(/[^\d]/g, "")
                  ) || 0
                : 0;

        // Get points discount
        const pointsDiscountElement = document.querySelector(
            "[data-points-discount-display]"
        );
        const pointsDiscount =
            pointsDiscountElement && !pointsDiscountElement.closest(".hidden")
                ? parseInt(
                      pointsDiscountElement.textContent.replace(/[^\d]/g, "")
                  ) || 0
                : 0;

        // Calculate final total
        const total = Math.max(
            0,
            subtotal + shippingCost - voucherDiscount - pointsDiscount
        );

        // Update displays
        const totalDisplay = document.querySelector("[data-total-display]");
        if (totalDisplay) {
            totalDisplay.textContent = `Rp ${total.toLocaleString()}`;
        }

        const shippingDisplay = document.querySelector(
            "[data-shipping-display]"
        );
        if (shippingDisplay && shippingCost > 0) {
            shippingDisplay.textContent = `Rp ${shippingCost.toLocaleString()}`;
        }

        console.log("💰 Checkout totals updated with points:", {
            subtotal,
            shippingCost,
            voucherDiscount,
            pointsDiscount,
            total,
        });
    } catch (error) {
        console.error("❌ Error updating checkout totals:", error);
    }
}

// Listen for shipping changes
document.addEventListener("shippingSelected", function (event) {
    updateCheckoutTotals();
    if (window.pointsCheckout) {
        window.pointsCheckout.recalculateTotal();
    }
});

// Listen for voucher changes
document.addEventListener("voucherApplied", function (event) {
    updateCheckoutTotals();
    if (window.pointsCheckout) {
        window.pointsCheckout.recalculateTotal();
    }
});

document.addEventListener("voucherRemoved", function (event) {
    updateCheckoutTotals();
    if (window.pointsCheckout) {
        window.pointsCheckout.recalculateTotal();
    }
});

// Listen for points changes
document.addEventListener("pointsApplied", function (event) {
    updateCheckoutTotals();
});

document.addEventListener("pointsRemoved", function (event) {
    updateCheckoutTotals();
});
