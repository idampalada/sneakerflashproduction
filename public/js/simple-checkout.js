// Simple Checkout JavaScript for Search-Based RajaOngkir V2 + Midtrans Integration
// UPDATED: Single Status System
// File: public/js/simple-checkout.js

// Global variables - will be initialized from HTML data attributes
let subtotal = 0;
let totalWeight = 1000;
let taxRate = 0.11; // 11% PPN
let currentStep = 1;
let availableShippingOptions = [];
let isCalculatingShipping = false;
let searchTimeout;
let selectedDestination = null;
let isSubmittingOrder = false; // Prevent double submission

// Initialize checkout when page loads
document.addEventListener("DOMContentLoaded", function () {
    console.log(
        "🚀 Checkout initialized (Single Status System + Search-Based RajaOngkir V2 + Midtrans)"
    );

    // Initialize variables from data attributes
    initializeVariables();

    console.log("📦 Total weight:", totalWeight, "grams");
    console.log("💰 Subtotal:", subtotal);

    // Setup event listeners
    setupEventListeners();

    // Test connection
    testRajaOngkirConnection();

    // Initialize password fields if create account was checked
    if (document.getElementById("create_account")?.checked) {
        togglePassword();
    }

    // Initialize payment method handlers
    initializePaymentMethods();

    // Load Midtrans Snap script
    loadMidtransScript();
});

function initializeVariables() {
    // Get values from hidden inputs or data attributes
    const subtotalEl = document.getElementById("subtotal-value");
    const weightEl = document.getElementById("total-weight");
    const taxRateEl = document.getElementById("tax-rate");

    if (subtotalEl) {
        subtotal = parseInt(subtotalEl.value) || 0;
    }

    if (weightEl) {
        totalWeight = parseInt(weightEl.value) || 1000;
    }

    if (taxRateEl) {
        taxRate = parseFloat(taxRateEl.value) || 0.11;
    }
}

function setupEventListeners() {
    // Destination search listener
    const destinationSearch = document.getElementById("destination_search");
    if (destinationSearch) {
        destinationSearch.addEventListener("input", function () {
            const query = this.value.trim();

            // Clear previous timeout
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }

            // Reset destination selection if user is typing new search
            if (selectedDestination && query.length >= 2) {
                // Don't reset immediately, let user refine search
            } else if (query.length < 2) {
                resetShippingOptions();
            }

            if (query.length >= 2) {
                // Debounce search
                searchTimeout = setTimeout(() => {
                    searchDestinations(query);
                }, 500);
            } else {
                hideSearchResults();
            }
        });

        // Hide results when clicking outside
        document.addEventListener("click", function (e) {
            const searchResults = document.getElementById("search-results");
            if (
                searchResults &&
                !destinationSearch.contains(e.target) &&
                !searchResults.contains(e.target)
            ) {
                hideSearchResults();
            }
        });
    }

    // Enhanced form submission handling with Midtrans integration
    const checkoutForm = document.getElementById("checkout-form");
    if (checkoutForm) {
        checkoutForm.addEventListener("submit", function (e) {
            e.preventDefault(); // Prevent default form submission

            if (isSubmittingOrder) {
                console.log("⏳ Order submission already in progress");
                return false;
            }

            // Validate final step
            if (!validateCurrentStep()) {
                return false;
            }

            // Check payment method
            const paymentMethod = document.querySelector(
                'input[name="payment_method"]:checked'
            )?.value;
            if (!paymentMethod) {
                alert("Please select a payment method.");
                return false;
            }

            // Handle different payment methods
            handleOrderSubmission(paymentMethod);
        });
    }
}

// Initialize payment method handlers
function initializePaymentMethods() {
    const paymentMethods = document.querySelectorAll(
        'input[name="payment_method"]'
    );
    paymentMethods.forEach((method) => {
        method.addEventListener("change", function () {
            const selectedMethod = this.value;
            console.log("💳 Payment method selected:", selectedMethod);

            // Update submit button text based on payment method
            updateSubmitButtonText(selectedMethod);
        });
    });
}

// Update submit button text based on payment method
function updateSubmitButtonText(paymentMethod) {
    const submitBtn = document.getElementById("place-order-btn");
    if (!submitBtn) return;

    switch (paymentMethod) {
        case "cod":
            submitBtn.textContent = "Place Order (COD)";
            submitBtn.className =
                "flex-1 bg-green-600 text-white py-3 rounded-lg hover:bg-green-700 transition-colors font-medium";
            break;
        case "bank_transfer":
        case "credit_card":
        case "ewallet":
            submitBtn.textContent = "Continue to Payment";
            submitBtn.className =
                "flex-1 bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium";
            break;
        default:
            submitBtn.textContent = "Place Order";
            submitBtn.className =
                "flex-1 bg-green-600 text-white py-3 rounded-lg hover:bg-green-700 transition-colors font-medium";
    }
}

// UPDATED: Handle order submission for single status system
function handleOrderSubmission(paymentMethod) {
    console.log("🛒 Processing order with payment method:", paymentMethod);

    if (isSubmittingOrder) {
        console.log("⏳ Order submission already in progress");
        return false;
    }

    isSubmittingOrder = true;
    const submitBtn = document.getElementById("place-order-btn");

    // Update button state
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = `
            <div class="flex items-center justify-center">
                <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-white mr-2"></div>
                Processing Order...
            </div>
        `;
    }

    showProcessingMessage(paymentMethod);

    // Get form data
    const form = document.getElementById("checkout-form");
    const formData = new FormData(form);

    // Debug form data
    console.log("📋 Form data being sent:");
    for (let [key, value] of formData.entries()) {
        console.log(`${key}: ${value}`);
    }

    // Ensure privacy_accepted is set correctly
    const privacyCheckbox = document.getElementById("privacy_accepted");
    if (privacyCheckbox && privacyCheckbox.checked) {
        formData.set("privacy_accepted", "1");
        console.log("✅ Privacy accepted: true");
    } else {
        console.log("❌ Privacy not accepted");
        resetSubmitButton();
        alert("Please accept the privacy policy to continue.");
        isSubmittingOrder = false;
        return;
    }

    // Get CSRF token
    const csrfToken =
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content") ||
        document.querySelector('input[name="_token"]')?.value;

    console.log("🔑 CSRF Token:", csrfToken ? "Found" : "Not found");

    // Submit with better response handling
    fetch("/checkout", {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": csrfToken,
            Accept: "application/json", // IMPORTANT: Request JSON response
            "X-Requested-With": "XMLHttpRequest", // Mark as AJAX
            "Content-Type": "application/x-www-form-urlencoded", // Form data
        },
        body: new URLSearchParams(formData), // Convert FormData to URLSearchParams for compatibility
    })
        .then(async (response) => {
            console.log("📤 Response status:", response.status);
            console.log(
                "📤 Response headers:",
                Object.fromEntries(response.headers.entries())
            );

            const contentType = response.headers.get("content-type");
            console.log("📄 Content type:", contentType);

            // Always parse as JSON since we request JSON
            if (contentType && contentType.includes("application/json")) {
                const data = await response.json();
                console.log("✅ JSON Response received:", data);
                return { success: true, data: data, status: response.status };
            } else {
                // If not JSON, might be error or redirect
                const text = await response.text();
                console.log(
                    "❌ Non-JSON response received:",
                    text.substring(0, 500)
                );

                // Try to parse as JSON if possible
                try {
                    const data = JSON.parse(text);
                    return {
                        success: true,
                        data: data,
                        status: response.status,
                    };
                } catch (e) {
                    return {
                        success: false,
                        error: "Server returned non-JSON response",
                        status: response.status,
                        text: text,
                    };
                }
            }
        })
        .then((result) => {
            if (result.success && result.data) {
                const data = result.data;

                // Handle response based on correct structure
                if (data.success) {
                    console.log("🎉 Order successful:", data);
                    handleSuccessfulOrder(data, paymentMethod);
                } else if (data.errors) {
                    console.log("❌ Validation errors:", data.errors);
                    handleOrderErrors(data.errors);
                } else if (data.error) {
                    console.log("❌ Order error:", data.error);
                    handleOrderError(data.error);
                } else {
                    console.log("❓ Unexpected response format:", data);
                    handleOrderError("Unexpected response format from server");
                }
            } else {
                console.log("❌ Request failed:", result);
                handleOrderError(result.error || "Server error occurred");
            }
        })
        .catch((error) => {
            console.error("❌ Network error:", error);
            handleOrderError(
                "Failed to connect to server. Please check your internet connection and try again."
            );
        })
        .finally(() => {
            console.log("🏁 Request completed");
            isSubmittingOrder = false;
            resetSubmitButton();
        });
}

// Handle successful order with Midtrans integration
function handleSuccessfulOrder(data, paymentMethod) {
    console.log("🎯 Handling successful order:", data);

    if (paymentMethod === "cod") {
        console.log("🚚 COD order, redirect to success");

        if (data.redirect_url) {
            window.location.href = data.redirect_url;
        } else if (data.order_number) {
            window.location.href = `/checkout/success/${data.order_number}`;
        } else {
            showSuccess("✅ COD order successfully created!");
            setTimeout(() => (window.location.href = "/"), 2000);
        }
    } else {
        console.log("💳 Online payment, handle Midtrans");

        // Handle Midtrans integration
        if (data.snap_token) {
            console.log("💳 Snap token received, open Midtrans popup");
            showSuccess("💳 Opening payment gateway...");

            // Wait a moment for user to read message
            setTimeout(() => {
                openMidtransPayment(data.snap_token, data.order_number);
            }, 1000);
        } else if (data.redirect_url) {
            console.log("🔄 Redirect to payment page:", data.redirect_url);
            window.location.href = data.redirect_url;
        } else if (data.order_number) {
            console.log("🔄 Redirect to payment page with order number");
            window.location.href = `/checkout/payment/${data.order_number}`;
        } else {
            console.error("❌ No snap token or redirect URL");
            handleOrderError(
                "Failed to create payment session. Please contact support."
            );
        }
    }
}

// Open Midtrans payment with better error handling
function openMidtransPayment(snapToken, orderNumber) {
    console.log("💳 Opening Midtrans payment with token:", snapToken);

    // Check if Snap is loaded
    if (typeof window.snap === "undefined") {
        console.error("❌ Midtrans Snap not loaded");

        // Try to load Midtrans script first
        loadMidtransScript()
            .then(() => {
                console.log("✅ Midtrans script loaded, retry payment");
                setTimeout(
                    () => openMidtransPayment(snapToken, orderNumber),
                    1000
                );
            })
            .catch(() => {
                handleOrderError(
                    "Payment system not available. Please refresh the page."
                );
            });
        return;
    }

    showSuccess("💳 Opening payment gateway...");

    // Open Midtrans Snap with proper error handling
    try {
        window.snap.pay(snapToken, {
            onSuccess: function (result) {
                console.log("✅ Payment successful:", result);
                showSuccess("✅ Payment successful! Redirecting...");

                setTimeout(() => {
                    if (result.order_id) {
                        window.location.href = `/checkout/success/${result.order_id}?payment=success`;
                    } else {
                        window.location.href = `/checkout/success/${orderNumber}?payment=success`;
                    }
                }, 1500);
            },

            onPending: function (result) {
                console.log("⏳ Payment pending:", result);
                showWarning(
                    "⏳ Payment is being processed. You will receive confirmation shortly."
                );

                setTimeout(() => {
                    if (result.order_id) {
                        window.location.href = `/checkout/success/${result.order_id}?payment=pending`;
                    } else {
                        window.location.href = `/checkout/success/${orderNumber}?payment=pending`;
                    }
                }, 2000);
            },

            onError: function (result) {
                console.error("❌ Payment error:", result);
                handleOrderError(
                    "Payment failed. Please try again or use a different payment method."
                );
            },

            onClose: function () {
                console.log("🔒 Payment popup closed by user");
                showWarning(
                    "Payment was cancelled. You can continue payment later from your order page."
                );

                setTimeout(() => {
                    if (
                        confirm(
                            "Would you like to view your order and try payment again?"
                        )
                    ) {
                        window.location.href = `/checkout/success/${orderNumber}`;
                    } else {
                        // Reset to original state
                        resetSubmitButton();
                    }
                }, 1000);
            },
        });
    } catch (error) {
        console.error("❌ Error opening Midtrans:", error);
        handleOrderError("Failed to open payment gateway. Please try again.");
    }
}

// Load Midtrans script with promise
function loadMidtransScript() {
    return new Promise((resolve, reject) => {
        // Check if already loaded
        if (window.snap) {
            resolve();
            return;
        }

        const clientKey = document
            .querySelector('meta[name="midtrans-client-key"]')
            ?.getAttribute("content");
        const isProduction =
            document
                .querySelector('meta[name="midtrans-production"]')
                ?.getAttribute("content") === "true";

        if (!clientKey) {
            console.error("⚠️ Midtrans client key not found");
            reject(new Error("Midtrans client key not found"));
            return;
        }

        // Check if script already exists
        if (document.querySelector("script[data-client-key]")) {
            // Script already exists, wait a moment and check again
            setTimeout(() => {
                if (window.snap) {
                    resolve();
                } else {
                    reject(
                        new Error(
                            "Midtrans script loaded but snap not available"
                        )
                    );
                }
            }, 1000);
            return;
        }

        const script = document.createElement("script");
        script.src = isProduction
            ? "https://app.midtrans.com/snap/snap.js"
            : "https://app.sandbox.midtrans.com/snap/snap.js";
        script.setAttribute("data-client-key", clientKey);

        script.onload = function () {
            console.log("✅ Midtrans Snap script successfully loaded");
            // Wait a moment to ensure snap is available
            setTimeout(() => {
                if (window.snap) {
                    resolve();
                } else {
                    reject(
                        new Error("Snap object not available after script load")
                    );
                }
            }, 500);
        };

        script.onerror = function () {
            console.error("❌ Failed to load Midtrans Snap script");
            reject(new Error("Failed to load Midtrans script"));
        };

        document.head.appendChild(script);
    });
}

// Helper function to show processing message
function showProcessingMessage(paymentMethod) {
    const statusEl = document.getElementById("connection-status");
    const statusText = document.getElementById("status-text");

    if (!statusEl || !statusText) return;

    let message = "";
    switch (paymentMethod) {
        case "cod":
            message = "🚚 Processing COD order...";
            break;
        case "bank_transfer":
        case "credit_card":
        case "ewallet":
            message = "💳 Creating payment session...";
            break;
        default:
            message = "🛒 Processing your order...";
    }

    statusEl.className =
        "mb-4 p-3 rounded-lg border bg-blue-50 border-blue-200";
    statusText.textContent = message;
    statusEl.classList.remove("hidden");
}

// Improved error handling functions
function handleOrderErrors(errors) {
    let errorMessage = "Please fix the following errors:\n";

    if (typeof errors === "object") {
        Object.keys(errors).forEach((field) => {
            if (Array.isArray(errors[field])) {
                errorMessage += `\n• ${errors[field].join(", ")}`;
            } else {
                errorMessage += `\n• ${errors[field]}`;
            }
        });
    } else {
        errorMessage = errors;
    }

    alert(errorMessage);
    showError("❌ Please fix the errors and try again.");
}

function handleOrderError(message) {
    console.error("❌ Order error:", message);
    alert(message);
    showError("❌ " + message);
}

// Reset submit button
function resetSubmitButton() {
    const submitBtn = document.getElementById("place-order-btn");
    if (!submitBtn) return;

    const paymentMethod = document.querySelector(
        'input[name="payment_method"]:checked'
    )?.value;

    submitBtn.disabled = false;
    updateSubmitButtonText(paymentMethod || "default");
}

// Test RajaOngkir connection
function testRajaOngkirConnection() {
    console.log("🔍 Testing RajaOngkir V2 connection...");

    const statusEl = document.getElementById("connection-status");
    const statusText = document.getElementById("status-text");

    // Test with a simple search
    fetch("/checkout/search-destinations?search=jakarta&limit=1", {
        method: "GET",
        headers: {
            Accept: "application/json",
            "X-Requested-With": "XMLHttpRequest",
        },
    })
        .then((response) => {
            console.log("📡 RajaOngkir test response status:", response.status);
            return response.json();
        })
        .then((data) => {
            if (data.success && data.data && data.data.length > 0) {
                console.log("✅ RajaOngkir V2 connection successful");
                showSuccess("✅ Shipping service connected successfully");
            } else {
                console.log("⚠️ RajaOngkir returned empty data");
                showWarning(
                    "⚠️ Shipping service connected but limited data available"
                );
            }
        })
        .catch((error) => {
            console.error("❌ RajaOngkir connection failed:", error);
            showError(
                "❌ Failed to connect to shipping service. Using fallback options."
            );
        });
}

// Status message functions
function showSuccess(message) {
    const statusEl = document.getElementById("connection-status");
    const statusText = document.getElementById("status-text");
    if (statusEl && statusText) {
        statusEl.className =
            "mb-4 p-3 rounded-lg border bg-green-50 border-green-200";
        statusText.textContent = message;
        statusEl.classList.remove("hidden");
        setTimeout(() => statusEl.classList.add("hidden"), 5000);
    }
}

function showWarning(message) {
    const statusEl = document.getElementById("connection-status");
    const statusText = document.getElementById("status-text");
    if (statusEl && statusText) {
        statusEl.className =
            "mb-4 p-3 rounded-lg border bg-yellow-50 border-yellow-200";
        statusText.textContent = message;
        statusEl.classList.remove("hidden");
        setTimeout(() => statusEl.classList.add("hidden"), 5000);
    }
}

function showError(message) {
    const statusEl = document.getElementById("connection-status");
    const statusText = document.getElementById("status-text");
    if (statusEl && statusText) {
        statusEl.className =
            "mb-4 p-3 rounded-lg border bg-red-50 border-red-200";
        statusText.textContent = message;
        statusEl.classList.remove("hidden");
    }
}

// Destination search functions
function searchDestinations(query) {
    console.log("🔍 Searching destinations for:", query);

    const searchResults = document.getElementById("search-results");
    if (!searchResults) return;

    // Show loading
    searchResults.innerHTML = `
        <div class="p-3 text-center">
            <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-blue-600 mx-auto"></div>
            <span class="text-sm text-gray-600 ml-2">Searching...</span>
        </div>
    `;
    searchResults.classList.remove("hidden");

    fetch(
        `/checkout/search-destinations?search=${encodeURIComponent(
            query
        )}&limit=10`,
        {
            method: "GET",
            headers: {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },
        }
    )
        .then((response) => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return response.json();
        })
        .then((data) => {
            if (data.success && data.data && data.data.length > 0) {
                displaySearchResults(data.data);
            } else {
                searchResults.innerHTML = `
                <div class="p-3 text-center text-gray-500">
                    <p>No locations found for "${query}"</p>
                    <p class="text-xs mt-1">Try searching with different keywords</p>
                </div>
            `;
            }
        })
        .catch((error) => {
            console.error("Search error:", error);
            searchResults.innerHTML = `
            <div class="p-3 text-center text-red-500">
                <p>Search failed. Please try again.</p>
            </div>
        `;
        });
}

function displaySearchResults(results) {
    const searchResults = document.getElementById("search-results");

    let html = "";
    results.forEach((result, index) => {
        const resultJson = JSON.stringify(result).replace(/"/g, "&quot;");
        html += `
            <div class="search-result-item" onclick="selectDestination(${resultJson})">
                <div class="font-medium text-sm">${result.subdistrict_name}</div>
                <div class="text-xs text-gray-600">${result.district_name}, ${result.city_name}</div>
                <div class="text-xs text-gray-500">${result.province_name} ${result.zip_code}</div>
            </div>
        `;
    });

    searchResults.innerHTML = html;
    searchResults.classList.remove("hidden");
}

function selectDestination(destination) {
    console.log("📍 Selected destination:", destination);

    selectedDestination = destination;

    // Update form fields
    const destinationIdEl = document.getElementById("destination_id");
    const destinationLabelEl = document.getElementById("destination_label");

    if (destinationIdEl) {
        destinationIdEl.value = destination.location_id;
    }

    if (destinationLabelEl) {
        destinationLabelEl.value =
            destination.label ||
            destination.full_address ||
            `${destination.subdistrict_name}, ${destination.district_name}, ${destination.city_name}, ${destination.province_name}`;
    }

    // Update display
    const selectedDiv = document.getElementById("selected-destination");
    const selectedText = document.getElementById("selected-destination-text");

    if (selectedDiv && selectedText) {
        selectedText.textContent =
            destination.label ||
            `${destination.subdistrict_name}, ${destination.district_name}, ${destination.city_name}, ${destination.province_name}`;

        selectedDiv.classList.remove("hidden");
    }

    // Auto-fill postal code if available
    if (destination.zip_code) {
        const postalCodeEl = document.getElementById("postal_code");
        if (postalCodeEl) {
            postalCodeEl.value = destination.zip_code;
        }
    }

    // Hide search results
    hideSearchResults();

    // Clear search input
    const searchInput = document.getElementById("destination_search");
    if (searchInput) {
        searchInput.value = "";
    }

    // Auto-calculate shipping if on step 3
    if (currentStep >= 3) {
        calculateShipping();
    }
}

function clearDestination() {
    selectedDestination = null;

    // Clear form fields
    const destinationIdEl = document.getElementById("destination_id");
    const destinationLabelEl = document.getElementById("destination_label");

    if (destinationIdEl) destinationIdEl.value = "";
    if (destinationLabelEl) destinationLabelEl.value = "";

    // Hide selected destination
    const selectedDiv = document.getElementById("selected-destination");
    if (selectedDiv) {
        selectedDiv.classList.add("hidden");
    }

    // Clear search
    const searchInput = document.getElementById("destination_search");
    if (searchInput) {
        searchInput.value = "";
    }

    // Reset shipping options
    resetShippingOptions();
}

function hideSearchResults() {
    const searchResults = document.getElementById("search-results");
    if (searchResults) {
        searchResults.classList.add("hidden");
    }
}

// Shipping calculation functions
function calculateShipping() {
    if (!selectedDestination) {
        console.log("❌ No destination selected for shipping calculation");
        return;
    }

    if (isCalculatingShipping) {
        console.log("⏳ Shipping calculation already in progress");
        return;
    }

    console.log("🚚 Calculating shipping to:", selectedDestination.location_id);

    isCalculatingShipping = true;

    const shippingOptions = document.getElementById("shipping-options");
    const loadingDiv = document.getElementById("shipping-loading");

    // Show loading
    if (shippingOptions) shippingOptions.classList.add("hidden");
    if (loadingDiv) loadingDiv.classList.remove("hidden");

    const requestData = {
        destination_id: selectedDestination.location_id,
        destination_label:
            selectedDestination.label ||
            selectedDestination.full_address ||
            `${selectedDestination.subdistrict_name}, ${selectedDestination.city_name}`,
        weight: totalWeight,
    };

    // Get CSRF token
    const csrfToken =
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content") ||
        document.querySelector('input[name="_token"]')?.value;

    fetch("/checkout/shipping", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
            "X-CSRF-TOKEN": csrfToken,
            "X-Requested-With": "XMLHttpRequest",
        },
        body: JSON.stringify(requestData),
    })
        .then((response) => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return response.json();
        })
        .then((data) => {
            console.log("✅ Shipping calculation response:", data);

            if (data.success && data.options && data.options.length > 0) {
                displayShippingOptions(data.options);
                availableShippingOptions = data.options;
            } else {
                throw new Error("No shipping options available");
            }
        })
        .catch((error) => {
            console.error("❌ Shipping calculation error:", error);
            displayShippingError();
        })
        .finally(() => {
            isCalculatingShipping = false;
            if (loadingDiv) loadingDiv.classList.add("hidden");
            if (shippingOptions) shippingOptions.classList.remove("hidden");
        });
}

function displayShippingOptions(options) {
    const shippingOptions = document.getElementById("shipping-options");
    if (!shippingOptions) return;

    let html = "";
    options.forEach((option, index) => {
        const isChecked = index === 0 ? "checked" : "";
        const mockBadge = option.is_mock
            ? '<span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded ml-2">Mock Data</span>'
            : "";

        html += `
            <label class="shipping-option flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                <input type="radio" name="shipping_option" value="${
                    option.courier
                }_${option.service}" 
                       data-cost="${option.cost}" 
                       data-description="${option.courier} ${
            option.service
        } - ${option.description}"
                       onchange="selectShipping(this)" ${isChecked}
                       class="mr-4">
                <div class="shipping-content flex-1">
                    <div class="font-medium flex items-center">
                        ${option.courier_name || option.courier} - ${
            option.service
        }
                        ${mockBadge}
                    </div>
                    <div class="text-sm text-gray-600">${
                        option.description
                    }</div>
                    <div class="text-sm text-gray-600">Estimated delivery: ${
                        option.formatted_etd || option.etd + " days"
                    }</div>
                </div>
                <div class="font-semibold text-blue-600">${
                    option.formatted_cost
                }</div>
            </label>
        `;
    });

    shippingOptions.innerHTML = html;

    // Auto-select first option
    if (options.length > 0) {
        const firstOption = options[0];
        const shippingMethodEl = document.getElementById("shipping_method");
        const shippingCostEl = document.getElementById("shipping_cost");

        if (shippingMethodEl) {
            shippingMethodEl.value = `${firstOption.courier} ${firstOption.service} - ${firstOption.description}`;
        }
        if (shippingCostEl) {
            shippingCostEl.value = firstOption.cost;
        }
        updateTotals(firstOption.cost);
    }
}

function displayShippingError() {
    const shippingOptions = document.getElementById("shipping-options");
    if (!shippingOptions) return;

    shippingOptions.innerHTML = `
        <div class="p-4 text-center border-2 border-dashed border-red-200 rounded-lg">
            <p class="text-red-600 mb-2">❌ Unable to calculate shipping</p>
            <p class="text-sm text-gray-600">Please try selecting a different location or contact support.</p>
            <button type="button" onclick="calculateShipping()" 
                    class="mt-2 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                Try Again
            </button>
        </div>
    `;

    resetShippingOptions();
}

function resetShippingOptions() {
    const shippingOptions = document.getElementById("shipping-options");
    if (!shippingOptions) return;

    shippingOptions.innerHTML = `
        <div class="p-4 text-center text-gray-500 border-2 border-dashed border-gray-200 rounded-lg">
            <p>📍 Please select your delivery location first</p>
        </div>
    `;

    // Reset form values
    const shippingMethodEl = document.getElementById("shipping_method");
    const shippingCostEl = document.getElementById("shipping_cost");

    if (shippingMethodEl) shippingMethodEl.value = "";
    if (shippingCostEl) shippingCostEl.value = "0";

    // Reset totals
    updateTotals(0);

    availableShippingOptions = [];
}

function selectShipping(radio) {
    console.log("🚚 Selected shipping:", radio.dataset.description);

    const shippingMethodEl = document.getElementById("shipping_method");
    const shippingCostEl = document.getElementById("shipping_cost");

    if (shippingMethodEl) shippingMethodEl.value = radio.dataset.description;
    if (shippingCostEl) shippingCostEl.value = radio.dataset.cost;

    updateTotals(parseInt(radio.dataset.cost));
}

function updateTotals(shippingCost) {
    const tax = subtotal * taxRate;
    const total = subtotal + shippingCost + tax;

    const shippingDisplay = document.getElementById("shipping-display");
    const taxDisplay = document.getElementById("tax-display");
    const totalDisplay = document.getElementById("total-display");

    if (shippingDisplay) {
        shippingDisplay.textContent =
            "Rp " + shippingCost.toLocaleString("id-ID");
    }
    if (taxDisplay) {
        taxDisplay.textContent =
            "Rp " + Math.round(tax).toLocaleString("id-ID");
    }
    if (totalDisplay) {
        totalDisplay.textContent =
            "Rp " + Math.round(total).toLocaleString("id-ID");
    }
}

// Step navigation functions
function nextStep(step) {
    if (validateCurrentStep()) {
        showStep(step);

        // Auto-calculate shipping when reaching step 3
        if (step === 3 && selectedDestination) {
            setTimeout(() => {
                calculateShipping();
            }, 500);
        }
    }
}

function prevStep(step) {
    showStep(step);
}

function showStep(step) {
    // Hide all sections
    document.querySelectorAll(".checkout-section").forEach((section) => {
        section.classList.remove("active");
        section.classList.add("hidden");
    });

    // Reset all step indicators
    document.querySelectorAll(".step").forEach((stepEl) => {
        stepEl.classList.remove("active", "completed");
    });

    // Mark completed steps
    for (let i = 1; i < step; i++) {
        const stepEl = document.getElementById(`step-${i}`);
        if (stepEl) stepEl.classList.add("completed");
    }

    // Show current step
    const currentSection = document.getElementById(`section-${step}`);
    const currentStepEl = document.getElementById(`step-${step}`);

    if (currentSection) {
        currentSection.classList.remove("hidden");
        currentSection.classList.add("active");
    }
    if (currentStepEl) {
        currentStepEl.classList.add("active");
    }

    currentStep = step;

    // Scroll to top of form
    const container = document.querySelector(".container");
    if (container) {
        container.scrollIntoView({ behavior: "smooth" });
    }
}

function validateCurrentStep() {
    switch (currentStep) {
        case 1:
            const firstName = document
                .getElementById("first_name")
                ?.value.trim();
            const lastName = document.getElementById("last_name")?.value.trim();
            const email = document.getElementById("email")?.value.trim();
            const phone = document.getElementById("phone")?.value.trim();
            const privacyAccepted =
                document.getElementById("privacy_accepted")?.checked;

            if (!firstName || !lastName || !email || !phone) {
                alert(
                    "Please fill in all required fields: First name, Last name, Email, and Phone."
                );
                return false;
            }

            if (!privacyAccepted) {
                alert("Please accept the privacy policy to continue.");
                return false;
            }

            // Validate email format
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                alert("Please enter a valid email address.");
                return false;
            }

            // Check password if creating account
            const createAccount =
                document.getElementById("create_account")?.checked;
            if (createAccount) {
                const password = document.getElementById("password")?.value;
                const passwordConfirmation = document.getElementById(
                    "password_confirmation"
                )?.value;

                if (!password || password.length < 8) {
                    alert("Password must be at least 8 characters long.");
                    return false;
                }

                if (password !== passwordConfirmation) {
                    alert("Password confirmation does not match.");
                    return false;
                }
            }
            break;

        case 2:
            const address = document.getElementById("address")?.value.trim();
            const destinationId =
                document.getElementById("destination_id")?.value;
            const postalCode = document
                .getElementById("postal_code")
                ?.value.trim();

            if (!address) {
                alert("Please enter your street address.");
                return false;
            }

            if (!destinationId || !selectedDestination) {
                alert("Please search and select your delivery location.");
                return false;
            }

            if (!postalCode) {
                alert("Please enter your postal code.");
                return false;
            }
            break;

        case 3:
            const shippingMethod =
                document.getElementById("shipping_method")?.value;
            const shippingCost =
                document.getElementById("shipping_cost")?.value;

            if (!shippingMethod || !shippingCost || shippingCost === "0") {
                alert("Please select a shipping method.");
                return false;
            }
            break;
    }
    return true;
}

function togglePassword() {
    const checkbox = document.getElementById("create_account");
    const passwordFields = document.getElementById("password-fields");

    if (checkbox && passwordFields) {
        if (checkbox.checked) {
            passwordFields.classList.remove("hidden");
            const passwordEl = document.getElementById("password");
            const passwordConfirmationEl = document.getElementById(
                "password_confirmation"
            );

            if (passwordEl) passwordEl.setAttribute("required", "required");
            if (passwordConfirmationEl)
                passwordConfirmationEl.setAttribute("required", "required");
        } else {
            passwordFields.classList.add("hidden");
            const passwordEl = document.getElementById("password");
            const passwordConfirmationEl = document.getElementById(
                "password_confirmation"
            );

            if (passwordEl) passwordEl.removeAttribute("required");
            if (passwordConfirmationEl)
                passwordConfirmationEl.removeAttribute("required");
        }
    }
}

// Make functions available globally for onclick handlers
window.nextStep = nextStep;
window.prevStep = prevStep;
window.selectDestination = selectDestination;
window.clearDestination = clearDestination;
window.calculateShipping = calculateShipping;
window.selectShipping = selectShipping;
window.togglePassword = togglePassword;
window.openMidtransPayment = openMidtransPayment;
window.loadMidtransScript = loadMidtransScript;

// Export functions for debugging
window.testMidtransIntegration = function () {
    console.log("🧪 Testing Midtrans integration...");

    if (typeof window.snap === "undefined") {
        console.error("❌ Midtrans Snap not available");
        return false;
    }

    console.log("✅ Midtrans Snap available");
    return true;
};

console.log(
    "🎯 Enhanced checkout with Single Status System + Midtrans integration loaded successfully!"
);

// UPDATED: Check order status for single status system
function checkOrderStatus(orderNumber) {
    fetch(`/api/payment/status/${orderNumber}`, {
        method: "GET",
        headers: {
            Accept: "application/json",
            "X-Requested-With": "XMLHttpRequest",
        },
    })
        .then((response) => response.json())
        .then((data) => {
            console.log("💳 Order status:", data);

            if (data.status === "paid") {
                showSuccess("✅ Payment confirmed!");
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else if (data.status === "cancelled") {
                showError("❌ Order cancelled");
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                showWarning(
                    `⏳ Order status: ${data.status_info || data.status}`
                );
            }
        })
        .catch((error) => {
            console.error("❌ Failed to check order status:", error);
            showError("Failed to check order status");
        });
}

// UPDATED: Retry payment function for single status system
function retryPayment(orderNumber, snapToken) {
    console.log("🔄 Retrying payment for order:", orderNumber);

    const loadingOverlay = document.getElementById("payment-loading");
    if (loadingOverlay) {
        loadingOverlay.classList.remove("hidden");
    }

    // Check if we have a snap token
    if (snapToken && snapToken !== "null" && snapToken !== "") {
        console.log("💳 Using existing snap token");

        // Load Midtrans script if not loaded
        if (typeof window.snap === "undefined") {
            loadMidtransScript()
                .then(() => {
                    openMidtransPayment(snapToken, orderNumber);
                })
                .catch(() => {
                    if (loadingOverlay) loadingOverlay.classList.add("hidden");
                    handleOrderError(
                        "Payment system not available. Please refresh the page."
                    );
                });
        } else {
            openMidtransPayment(snapToken, orderNumber);
        }
    } else {
        console.log("🔄 Generating new snap token");

        // Get new snap token
        fetch(`/api/payment/retry/${orderNumber}`, {
            method: "POST",
            headers: {
                Accept: "application/json",
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content"),
            },
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success && data.snap_token) {
                    console.log("✅ New snap token received");

                    // Load Midtrans script if not loaded
                    if (typeof window.snap === "undefined") {
                        loadMidtransScript()
                            .then(() => {
                                openMidtransPayment(
                                    data.snap_token,
                                    orderNumber
                                );
                            })
                            .catch(() => {
                                if (loadingOverlay)
                                    loadingOverlay.classList.add("hidden");
                                handleOrderError(
                                    "Payment system not available. Please refresh the page."
                                );
                            });
                    } else {
                        openMidtransPayment(data.snap_token, orderNumber);
                    }
                } else {
                    if (loadingOverlay) loadingOverlay.classList.add("hidden");
                    handleOrderError(
                        data.error || "Failed to create payment session"
                    );
                }
            })
            .catch((error) => {
                console.error("❌ Error retrying payment:", error);
                if (loadingOverlay) loadingOverlay.classList.add("hidden");
                handleOrderError("Failed to retry payment. Please try again.");
            });
    }
}

// Auto-initialize on specific pages
if (window.location.pathname.includes("/checkout/payment/")) {
    // If on payment page, ensure Midtrans is loaded
    loadMidtransScript().catch(console.error);
}

// Make retry payment available globally
window.retryPayment = retryPayment;
window.checkOrderStatus = checkOrderStatus;
