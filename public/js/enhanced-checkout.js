// Enhanced Checkout JS - Complete System
// Part 1: Core Setup, Global Variables, and Initialization
// Hierarchical Location System + Step Navigation + Server Validation Fix

// Debug system
const DEBUG_PAY = true;
function dlog(...args) {
    if (DEBUG_PAY) console.log(...args);
}
function derr(...args) {
    if (DEBUG_PAY) console.error(...args);
}
function dgroup(name) {
    if (DEBUG_PAY) console.group(name);
}
function dgroupEnd() {
    if (DEBUG_PAY) console.groupEnd();
}

// Global error handlers
window.onerror = function (msg, src, line, col, err) {
    derr("window.onerror", { msg, src, line, col, err });
};
window.addEventListener("unhandledrejection", (e) => {
    derr("unhandledrejection", e.reason);
});

// Global variables
let subtotal = 0;
let totalWeight = 1000;
let taxRate = 0; // NO TAX
let currentStep = 1;
let selectedDestination = null;
let isSubmittingOrder = false;
let userHasPrimaryAddress = false;
let primaryAddressId = null;
let isCalculatingShipping = false;
let searchTimeout;
let cartItems = [];
let appliedVoucher = null;
let originalSubtotal = 0;
let discountAmount = 0;
let appliedPoints = null;
let pointsDiscount = 0;

// Point data getter
function getCurrentPointsData() {
    if (window.pointsCheckout && window.pointsCheckout.appliedPoints > 0) {
        return {
            points_used: window.pointsCheckout.appliedPoints,
            discount: window.pointsCheckout.pointsDiscount,
        };
    }
    return null;
}

// Main initialization
document.addEventListener("DOMContentLoaded", function () {
    dgroup("=== DEBUG Midtrans Meta ===");
    const ck = document.querySelector('meta[name="midtrans-client-key"]')?.content;
    const prod = document.querySelector('meta[name="midtrans-production"]')?.content;
    dlog("client_key length:", ck ? ck.length : 0);
    dlog("production meta:", prod);
    dgroupEnd();

    if (!window.snap) {
        dlog("snap not present on load → will be loaded when needed.");
    } else {
        dlog("snap already present on load.");
    }

    console.log("Enhanced Checkout Fix initialized - HIERARCHICAL SYSTEM");

    // Initialize all systems in order
    initializeVariables();
    initializeOrderSummary();
    initializeStepNavigation();
    setupHierarchicalLocationHandlers();
    setupEventListeners();
    initializeAddressIntegration();
    autoFillPersonalInformation();
    testRajaOngkirConnection();
    loadMidtransScript();
    initializeVoucherSystem();
    setupVoucherEventListeners();
});

// Initialize variables from meta tags
function initializeVariables() {
    const subtotalMeta = document.querySelector('meta[name="cart-subtotal"]');
    const weightMeta = document.querySelector('meta[name="total-weight"]');
    const hasPrimaryMeta = document.querySelector('meta[name="user-has-primary-address"]');
    const primaryIdMeta = document.querySelector('meta[name="primary-address-id"]');

    // Parse meta values with proper fallbacks
    if (subtotalMeta) subtotal = parseInt(subtotalMeta.content) || 0;
    if (weightMeta) totalWeight = parseInt(weightMeta.content) || 1000;

    // Set originalSubtotal properly BEFORE using it
    originalSubtotal = subtotal;

    // Parse address-related meta values
    userHasPrimaryAddress = hasPrimaryMeta && hasPrimaryMeta.content === "true";
    primaryAddressId = primaryIdMeta && primaryIdMeta.content !== "null" ? primaryIdMeta.content : null;

    console.log("Variables initialized (HIERARCHICAL + NO TAX + VOUCHER + POINTS):", {
        subtotal,
        originalSubtotal,
        totalWeight,
        userHasPrimaryAddress,
        primaryAddressId,
        taxRate: 0,
    });

    // Update initial totals with correct values
    updateOrderSummaryTotals(originalSubtotal, 0, 0, 0);

    // Check for applied voucher and points from session
    checkAppliedVoucher();
    checkAppliedPoints();
}

// Initialize Order Summary with proper data
function initializeOrderSummary() {
    console.log("Initializing Order Summary - NO TAX + VOUCHER + POINTS");

    // Get cart items from the page (they should be rendered by the server)
    const cartItemElements = document.querySelectorAll(".order-summary-item");
    cartItems = [];

    cartItemElements.forEach((element) => {
        const item = {
            name: element.dataset.name || "Unknown Product",
            quantity: parseInt(element.dataset.quantity) || 1,
            price: parseInt(element.dataset.price) || 0,
            subtotal: parseInt(element.dataset.subtotal) || 0,
            size: element.dataset.size || null,
            image: element.dataset.image || "/images/placeholder.jpg",
        };
        cartItems.push(item);
    });

    // If no cart items found from elements, try to fetch
    if (cartItems.length === 0) {
        fetchCartItems();
    }

    // Update totals WITHOUT TAX
    updateOrderSummaryTotals(originalSubtotal, 0, discountAmount, pointsDiscount);

    console.log("Order Summary initialized with", cartItems.length, "items - NO TAX + VOUCHER + POINTS");
}

// Fetch cart items if not available in DOM
async function fetchCartItems() {
    try {
        const response = await fetch("/cart/data", {
            headers: {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.getAttribute("content"),
            },
        });

        if (response.ok) {
            const data = await response.json();
            if (data.items) {
                cartItems = data.items;
                subtotal = data.total || 0;
                updateOrderSummaryTotals(originalSubtotal, 0, discountAmount, pointsDiscount);
                console.log("Cart items fetched successfully:", cartItems.length, "items");
            }
        }
    } catch (error) {
        console.error("Failed to fetch cart items:", error);
    }
}

// Main order summary totals update function
function updateOrderSummaryTotals(cartSubtotal, shippingCost = 0, voucherDiscount = 0, pointsDiscountAmount = 0) {
    // Ensure all values are numbers
    cartSubtotal = parseFloat(cartSubtotal) || 0;
    shippingCost = parseFloat(shippingCost) || 0;
    voucherDiscount = parseFloat(voucherDiscount) || 0;
    pointsDiscountAmount = parseFloat(pointsDiscountAmount) || 0;

    const taxAmount = 0; // NO TAX
    const totalAmount = Math.max(0, cartSubtotal + shippingCost - voucherDiscount - pointsDiscountAmount);

    console.log("Updating Order Summary (NO TAX + VOUCHER + POINTS):", {
        subtotal: cartSubtotal,
        shipping: shippingCost,
        voucherDiscount: voucherDiscount,
        pointsDiscount: pointsDiscountAmount,
        tax: 0,
        total: totalAmount,
    });

    // Update subtotal display
    const subtotalElements = document.querySelectorAll("[data-subtotal-display]");
    subtotalElements.forEach((el) => {
        el.textContent = `Rp ${new Intl.NumberFormat("id-ID").format(cartSubtotal)}`;
    });

    // Update shipping cost display
    const shippingElements = document.querySelectorAll("#shipping-cost-display, [data-shipping-display]");
    shippingElements.forEach((el) => {
        if (shippingCost > 0) {
            el.textContent = `Rp ${new Intl.NumberFormat("id-ID").format(shippingCost)}`;
        } else {
            el.textContent = "To be calculated";
        }
    });

    // Update voucher discount display
    const voucherDiscountElements = document.querySelectorAll("[data-discount-display]");
    const voucherDiscountRows = document.querySelectorAll(".discount-row");

    if (voucherDiscount > 0) {
        voucherDiscountElements.forEach((el) => {
            el.textContent = `-Rp ${new Intl.NumberFormat("id-ID").format(voucherDiscount)}`;
        });
        voucherDiscountRows.forEach((row) => {
            row.classList.remove("hidden");
        });
    } else {
        voucherDiscountRows.forEach((row) => {
            row.classList.add("hidden");
        });
    }

    // Update points discount display
    const pointsDiscountElements = document.querySelectorAll("[data-points-discount-display]");
    const pointsDiscountRows = document.querySelectorAll(".points-discount-row");

    if (pointsDiscountAmount > 0) {
        pointsDiscountElements.forEach((el) => {
            el.textContent = `-Rp ${new Intl.NumberFormat("id-ID").format(pointsDiscountAmount)}`;
        });
        pointsDiscountRows.forEach((row) => {
            row.classList.remove("hidden");
        });
    } else {
        pointsDiscountRows.forEach((row) => {
            row.classList.add("hidden");
        });
    }

    // Update total display
    const totalElements = document.querySelectorAll("#total-display, [data-total-display]");
    totalElements.forEach((el) => {
        el.textContent = `Rp ${new Intl.NumberFormat("id-ID").format(totalAmount)}`;
    });

    // Store current values for form submission
    const shippingCostInput = document.getElementById("shipping_cost");
    if (shippingCostInput) {
        shippingCostInput.value = shippingCost;
    }

    // Store discount amounts separately
    discountAmount = voucherDiscount;
    pointsDiscount = pointsDiscountAmount;
    subtotal = cartSubtotal;
}

// Auto-fill personal information from authenticated user
function autoFillPersonalInformation() {
    console.log("Auto-filling personal information");

    const isAuthenticated = document.querySelector('meta[name="user-authenticated"]')?.content === "true";

    if (isAuthenticated) {
        const authenticatedUserName = document.querySelector('meta[name="authenticated-user-name"]')?.content || "";
        const authenticatedUserPhone = document.querySelector('meta[name="authenticated-user-phone"]')?.content || "";

        // Split name into first and last name
        const nameParts = authenticatedUserName.trim().split(" ");
        const firstName = nameParts[0] || "";
        const lastName = nameParts.slice(1).join(" ") || "";

        // Fill personal information fields if they exist and are empty
        fillFieldIfEmpty("first_name", firstName);
        fillFieldIfEmpty("last_name", lastName);
        fillFieldIfEmpty("phone", authenticatedUserPhone);

        // Auto-fill email from meta tag
        const userEmailMeta = document.querySelector('meta[name="user-email"]');
        if (userEmailMeta && userEmailMeta.content) {
            fillFieldIfEmpty("email", userEmailMeta.content);
        }

        // Auto-fill recipient fields in address section
        fillFieldIfEmpty("recipient_name", authenticatedUserName);
        fillFieldIfEmpty("phone_recipient", authenticatedUserPhone);

        console.log("Personal information auto-filled:", {
            firstName,
            lastName,
            phone: authenticatedUserPhone,
            email: userEmailMeta?.content,
        });
    }
}

// Helper function to fill field if empty
function fillFieldIfEmpty(fieldId, value) {
    const field = document.getElementById(fieldId);
    if (field && !field.value.trim() && value) {
        field.value = value;
        console.log(`Filled ${fieldId}:`, value);
    }
}
// Enhanced Checkout JS - Part 2
// Step Navigation System & Hierarchical Validation

// STEP NAVIGATION SYSTEM
function initializeStepNavigation() {
    console.log("Initializing step navigation...");
    
    // Start with step 1 only
    showStep(1);
    
    // Setup continue button handlers
    setupContinueButtons();
    
    console.log("Step navigation initialized");
}

function showStep(step) {
    console.log(`Showing step ${step}`);
    
    // Hide all sections first
    const allSections = document.querySelectorAll(".checkout-section");
    allSections.forEach((section) => {
        section.style.display = "none";
        section.classList.remove("active");
        section.classList.add("hidden");
    });

    // Reset all step indicators
    const allSteps = document.querySelectorAll(".step");
    allSteps.forEach((stepEl) => {
        stepEl.classList.remove("active", "completed");
    });

    // Mark completed steps
    for (let i = 1; i < step; i++) {
        const stepEl = document.getElementById(`step-${i}`);
        if (stepEl) {
            stepEl.classList.add("completed");
        }
    }

    // Show current step
    const currentSection = document.getElementById(`section-${step}`);
    const currentStepEl = document.getElementById(`step-${step}`);

    if (currentSection) {
        currentSection.style.display = "block";
        currentSection.classList.remove("hidden");
        currentSection.classList.add("active");
    }
    
    if (currentStepEl) {
        currentStepEl.classList.add("active");
    }

    // Update global current step
    currentStep = step;

    // Scroll to top of current section
    if (currentSection) {
        currentSection.scrollIntoView({ 
            behavior: "smooth", 
            block: "start" 
        });
    }

    console.log(`Step ${step} displayed`);
}

function nextStep(step) {
    console.log(`Moving to step ${step}`);
    
    if (validateCurrentStep()) {
        showStep(step);
        
        // Auto-calculate shipping when reaching step 3
        if (step === 3 && selectedDestination) {
            setTimeout(() => {
                calculateShipping();
            }, 500);
        }
    } else {
        console.log(`Validation failed for current step`);
    }
}

function prevStep(step) {
    console.log(`Moving back to step ${step}`);
    showStep(step);
}

function setupContinueButtons() {
    console.log("Setting up continue buttons...");
    
    // Step 1 to Step 2
    const continueStep1 = document.getElementById("continue-step-1");
    if (continueStep1) {
        continueStep1.onclick = function (e) {
            e.preventDefault();
            if (validateStep1()) {
                nextStep(2);
            }
        };
    }

    // Step 2 to Step 3
    const continueStep2 = document.getElementById("continue-step-2");
    if (continueStep2) {
        continueStep2.onclick = function (e) {
            e.preventDefault();
            if (validateStep2Enhanced()) {
                nextStep(3);
                setTimeout(() => {
                    if (selectedDestination) {
                        calculateShipping();
                    }
                }, 500);
            }
        };
    }

    // Step 3 to Step 4
    const continueStep3 = document.getElementById("continue-step-3");
    if (continueStep3) {
        continueStep3.onclick = function (e) {
            e.preventDefault();
            if (validateStep3()) {
                nextStep(4);
            }
        };
    }

    // Previous buttons
    const prevStep2 = document.getElementById("prev-step-2");
    if (prevStep2) {
        prevStep2.onclick = function (e) {
            e.preventDefault();
            prevStep(1);
        };
    }

    const prevStep3 = document.getElementById("prev-step-3");
    if (prevStep3) {
        prevStep3.onclick = function (e) {
            e.preventDefault();
            prevStep(2);
        };
    }

    console.log("Continue buttons setup complete");
}

// VALIDATION SYSTEM
function validateCurrentStep() {
    switch (currentStep) {
        case 1:
            return validateStep1();
        case 2:
            return validateStep2Enhanced();
        case 3:
            return validateStep3();
        default:
            return true;
    }
}

function validateStep1() {
    const firstName = document.getElementById("first_name")?.value.trim();
    const lastName = document.getElementById("last_name")?.value.trim();
    const email = document.getElementById("email")?.value.trim();
    const phone = document.getElementById("phone")?.value.trim();
    const privacyAccepted = document.getElementById("privacy_accepted")?.checked;

    if (!firstName || !lastName || !email || !phone) {
        alert("Please fill in all required fields: First name, Last name, Email, and Phone.");
        return false;
    }

    if (!privacyAccepted) {
        alert("Please accept the privacy policy to continue.");
        return false;
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        alert("Please enter a valid email address.");
        return false;
    }

    const createAccount = document.getElementById("create_account")?.checked;
    if (createAccount) {
        const password = document.getElementById("password")?.value;
        const passwordConfirmation = document.getElementById("password_confirmation")?.value;

        if (!password || password.length < 8) {
            alert("Password must be at least 8 characters long.");
            return false;
        }

        if (password !== passwordConfirmation) {
            alert("Password confirmation does not match.");
            return false;
        }
    }

    return true;
}

// HIERARCHICAL VALIDATION SYSTEM
function validateStep2Enhanced() {
    console.log("Enhanced Step 2 validation starting (HIERARCHICAL)...");

    let isValid = true;
    const errors = [];

    // Remove existing error messages first
    const existingError = document.getElementById("step2-errors");
    if (existingError) {
        existingError.remove();
    }

    // Clear previous error styling
    document.querySelectorAll(".border-red-500").forEach((el) => {
        el.classList.remove("border-red-500");
    });

    // Check if using saved address or new address
    const savedAddressInput = document.querySelector('input[name="saved_address_id"]:checked');

    if (!savedAddressInput) {
        console.log("No saved address selected - validating new address");
        isValid = validateNewAddressFormHierarchical(errors);
    } else if (savedAddressInput.value === "new") {
        console.log("New address explicitly selected");
        isValid = validateNewAddressFormHierarchical(errors);
    } else {
        console.log("Saved address selected:", savedAddressInput.value);
        isValid = validateSavedAddressSelection(errors);
    }

    if (!isValid) {
        showValidationErrors(errors);
        return false;
    }

    console.log("Step 2 validation passed");
    return true;
}

function validateNewAddressFormHierarchical(errors) {
    let isValid = true;

    console.log("Validating hierarchical address form...");

    // Required fields validation - UPDATED untuk hierarchical system
    const requiredFields = [
        { id: "recipient_name", name: "Recipient Name" },
        { id: "phone_recipient", name: "Recipient Phone" },
        { id: "street_address", name: "Street Address" },
        { id: "checkout_province_id", name: "Province" },
        { id: "checkout_city_id", name: "City" },
        { id: "checkout_district_id", name: "District" },
        { id: "checkout_sub_district_id", name: "Sub District" },
    ];

    requiredFields.forEach(field => {
        const element = document.getElementById(field.id);
        if (!element || !element.value.trim()) {
            errors.push(`${field.name} is required`);
            if (element) {
                element.classList.add("border-red-500");
            }
            isValid = false;
        }
    });

    // CRITICAL: Validate destination_id is set (for shipping calculation)
    const destinationId = document.getElementById('destination_id');
    if (!destinationId || !destinationId.value) {
        errors.push('Please complete the location selection');
        const subDistrictSelect = document.getElementById('checkout_sub_district_id');
        if (subDistrictSelect) {
            subDistrictSelect.classList.add("border-red-500");
        }
        isValid = false;
    }

    // Validate postal code
    const postalCodeField = document.getElementById('checkout_postal_code');
    if (postalCodeField && !postalCodeField.value.trim()) {
        errors.push('Postal code is required');
        postalCodeField.classList.add("border-red-500");
        isValid = false;
    }

    // Phone number format validation
    const phoneInput = document.getElementById("phone_recipient");
    if (phoneInput && phoneInput.value) {
        const phoneRegex = /^[0-9+\-\s\(\)]{10,}$/;
        if (!phoneRegex.test(phoneInput.value.trim())) {
            errors.push("Please enter a valid phone number (minimum 10 digits)");
            isValid = false;
            phoneInput.classList.add("border-red-500");
        }
    }

    // Address label validation
    const addressLabel = document.querySelector('input[name="address_label"]:checked');
    if (!addressLabel) {
        const rumahOption = document.querySelector('input[name="address_label"][value="Rumah"]');
        if (rumahOption) {
            rumahOption.checked = true;
            if (typeof updateAddressLabelStyles === "function") {
                updateAddressLabelStyles();
            }
        } else {
            errors.push("Please select address label (Kantor or Rumah)");
            isValid = false;
        }
    }

    // Fill legacy fields if validation passes
    if (isValid) {
        fillLegacyFieldsFromHierarchical();
    }

    console.log(`Validation result: ${isValid ? 'PASSED' : 'FAILED'}`);
    return isValid;
}

function fillLegacyFieldsFromHierarchical() {
    console.log("Filling legacy fields from hierarchical system...");

    const streetAddress = document.getElementById("street_address")?.value || "";
    const provinceName = document.getElementById("checkout_province_name")?.value || "";
    const cityName = document.getElementById("checkout_city_name")?.value || "";
    const districtName = document.getElementById("checkout_district_name")?.value || "";
    const subDistrictName = document.getElementById("checkout_sub_district_name")?.value || "";
    const postalCode = document.getElementById("checkout_postal_code")?.value || "";

    // Create full address string
    const fullAddress = `${streetAddress}, ${subDistrictName}, ${districtName}, ${cityName}, ${provinceName} ${postalCode}`.trim();
    const locationString = `${subDistrictName}, ${districtName}, ${cityName}, ${provinceName}`.trim();

    // Set legacy fields
    setFieldValueSafe("legacy_address", fullAddress);
    setFieldValueSafe("legacy_destination_label", locationString);
    setFieldValueSafe("address", fullAddress);
    setFieldValueSafe("destination_label", locationString);

    // CRITICAL FIX: Add backward compatibility fields for server validation
    setFieldValueSafe("province_name", provinceName);
    setFieldValueSafe("city_name", cityName);
    setFieldValueSafe("subdistrict_name", subDistrictName); // Server expects this field name
    setFieldValueSafe("postal_code", postalCode);

    console.log("Legacy fields filled:", {
        address: fullAddress,
        destination_label: locationString,
        subdistrict_name: subDistrictName, // Critical for server validation
    });
}

function validateSavedAddressSelection(errors) {
    const destinationId = document.getElementById("destination_id");
    if (!destinationId || !destinationId.value) {
        errors.push("Please wait for address to load or select a different address");
        return false;
    }
    return true;
}

function showValidationErrors(errors) {
    const existingError = document.getElementById("step2-errors");
    if (existingError) {
        existingError.remove();
    }

    if (errors.length > 0) {
        const errorDiv = document.createElement("div");
        errorDiv.id = "step2-errors";
        errorDiv.className = "bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4";

        const errorList = document.createElement("ul");
        errors.forEach((error) => {
            const li = document.createElement("li");
            li.textContent = error;
            errorList.appendChild(li);
        });

        errorDiv.appendChild(errorList);

        const step2Section = document.getElementById("section-2");
        const buttonsDiv = step2Section.querySelector(".flex.space-x-4");
        if (buttonsDiv && buttonsDiv.parentNode) {
            buttonsDiv.parentNode.insertBefore(errorDiv, buttonsDiv);
            errorDiv.scrollIntoView({ behavior: "smooth", block: "center" });
        }
    }
}

function validateStep3() {
    const shippingMethod = document.getElementById("shipping_method")?.value;
    const shippingCost = document.getElementById("shipping_cost")?.value;

    if (!shippingMethod || !shippingCost) {
        alert("Please select a shipping method.");
        return false;
    }

    return true;
}

// HIERARCHICAL LOCATION HANDLERS
function setupHierarchicalLocationHandlers() {
    console.log("Setting up hierarchical location handlers...");
    
    const subDistrictSelect = document.getElementById('checkout_sub_district_id');

    if (!subDistrictSelect) {
        console.log('Sub-district select element not found');
        return;
    }

    // CRITICAL: Sub-district change handler
    subDistrictSelect.addEventListener('change', function() {
        const subDistrictId = this.value;
        const subDistrictName = this.options[this.selectedIndex].text;
        const zipCode = this.options[this.selectedIndex].getAttribute('data-zip');
        
        console.log('Sub-district selected:', {
            id: subDistrictId,
            name: subDistrictName,
            zipCode: zipCode
        });
        
        if (subDistrictId) {
            // Set hidden field values
            document.getElementById('checkout_sub_district_name').value = subDistrictName;
            
            // CRITICAL: Set destination_id for shipping calculation
            document.getElementById('destination_id').value = subDistrictId;
            
            // Set postal code if available
            if (zipCode) {
                const postalCodeField = document.getElementById('checkout_postal_code');
                const postalCodeDisplayField = document.getElementById('checkout_postal_code_display');
                if (postalCodeField) postalCodeField.value = zipCode;
                if (postalCodeDisplayField) postalCodeDisplayField.value = zipCode;
            }
            
            // Update selectedDestination global variable
            const provinceName = document.getElementById('checkout_province_name').value;
            const cityName = document.getElementById('checkout_city_name').value;
            const districtName = document.getElementById('checkout_district_name').value;
            
            selectedDestination = {
                destination_id: subDistrictId,
                location_id: subDistrictId,
                sub_district_id: subDistrictId,
                sub_district_name: subDistrictName,
                district_name: districtName,
                city_name: cityName,
                province_name: provinceName,
                postal_code: zipCode,
                full_address: `${subDistrictName}, ${districtName}, ${cityName}, ${provinceName}`,
                label: `${subDistrictName}, ${districtName}, ${cityName}, ${provinceName}`
            };
            
            console.log('Destination set:', selectedDestination);
            
            // Auto-calculate shipping if we're on step 3
            if (currentStep >= 3) {
                setTimeout(() => calculateShipping(), 500);
            }
            
        } else {
            document.getElementById('destination_id').value = '';
            selectedDestination = null;
            console.log('Destination cleared');
        }
    });
}

// UTILITY FUNCTIONS
function setFieldValueSafe(fieldId, value) {
    const field = document.getElementById(fieldId);
    if (field) {
        field.value = value || "";
    } else {
        // If field doesn't exist, create hidden input
        const hiddenField = document.createElement("input");
        hiddenField.type = "hidden";
        hiddenField.id = fieldId;
        hiddenField.name = fieldId;
        hiddenField.value = value || "";

        const form = document.getElementById("checkout-form");
        if (form) {
            form.appendChild(hiddenField);
        }
    }
}

// Password toggle function for guest checkout
function togglePassword() {
    const createAccount = document.getElementById("create_account");
    const passwordFields = document.getElementById("password-fields");

    if (createAccount && passwordFields) {
        if (createAccount.checked) {
            passwordFields.classList.remove("hidden");
        } else {
            passwordFields.classList.add("hidden");
        }
    }
}
// Enhanced Checkout JS - Part 3
// Shipping Calculation & Address Integration System

// SHIPPING CALCULATION SYSTEM
async function calculateShipping() {
    if (!selectedDestination) {
        console.log("No destination selected for shipping calculation");
        displayShippingError("Please select your delivery location first");
        return;
    }

    if (isCalculatingShipping) {
        console.log("Shipping calculation already in progress");
        return;
    }

    console.log("Calculating shipping to:", selectedDestination);

    isCalculatingShipping = true;

    const shippingOptions = document.getElementById("shipping-options");
    const loadingDiv = document.getElementById("shipping-loading");

    // Show loading
    if (shippingOptions) shippingOptions.classList.add("hidden");
    if (loadingDiv) loadingDiv.classList.remove("hidden");

    // Get destination ID
    const destinationId = selectedDestination.destination_id || 
                         selectedDestination.id || 
                         selectedDestination.location_id ||
                         document.getElementById("destination_id")?.value;

    if (!destinationId) {
        console.error("No destination_id available:", selectedDestination);
        displayShippingError("Invalid destination selected. Please select location again.");
        isCalculatingShipping = false;
        if (loadingDiv) loadingDiv.classList.add("hidden");
        if (shippingOptions) shippingOptions.classList.remove("hidden");
        return;
    }

    const requestData = {
        destination_id: destinationId,
        destination_label: selectedDestination.label || selectedDestination.full_address || 
                          `${selectedDestination.subdistrict_name}, ${selectedDestination.city_name}`,
        weight: totalWeight,
    };

    console.log("Shipping request data:", requestData);

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content");

    try {
        const response = await fetch("/checkout/calculate-shipping", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-CSRF-TOKEN": csrfToken,
                "X-Requested-With": "XMLHttpRequest",
            },
            body: JSON.stringify(requestData),
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();
        console.log("Shipping calculation response:", data);

        if (data.success && data.options && data.options.length > 0) {
            displayShippingOptions(data.options);
        } else {
            throw new Error(data.error || "No shipping options available");
        }
    } catch (error) {
        console.error("Shipping calculation error:", error);
        displayShippingError(error.message || "Failed to calculate shipping options");
    } finally {
        isCalculatingShipping = false;
        if (loadingDiv) loadingDiv.classList.add("hidden");
        if (shippingOptions) shippingOptions.classList.remove("hidden");
    }
}

function displayShippingOptions(options) {
    const shippingOptions = document.getElementById("shipping-options");
    if (!shippingOptions) return;

    let html = "";
    options.forEach((option, index) => {
        const isChecked = index === 0 ? "checked" : "";
        const cost = parseInt(option.cost || 0);
        const formattedCost = cost === 0 ? "Free" : option.formatted_cost || "Rp " + cost.toLocaleString("id-ID");

        html += `
            <label class="shipping-option flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50 ${
                isChecked ? "border-blue-500 bg-blue-50" : "border-gray-200"
            }">
                <input type="radio" name="shipping_option" value="${option.courier}_${option.service}" 
                       data-cost="${cost}" 
                       data-description="${option.courier} ${option.service} - ${option.description}"
                       onchange="selectShipping(this)" ${isChecked}
                       class="mr-4">
                <div class="shipping-content flex-1">
                    <div class="font-medium flex items-center">
                        ${option.courier_name || option.courier} - ${option.service}
                        ${option.recommended ? '<span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded ml-2">Recommended</span>' : ""}
                        ${option.is_mock || option.type === "mock" ? '<span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded ml-2">Estimate</span>' : ""}
                    </div>
                    <div class="text-sm text-gray-600">${option.description}</div>
                    <div class="text-sm text-gray-600">Estimated delivery: ${option.formatted_etd || option.etd + " days"}</div>
                </div>
                <div class="font-semibold text-blue-600">${formattedCost}</div>
            </label>
        `;
    });

    shippingOptions.innerHTML = html;

    // Auto-select first option and update totals
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

        updateOrderSummaryTotals(originalSubtotal, firstOption.cost, discountAmount, pointsDiscount);
    }
}

function displayShippingError(errorMessage = "Unable to calculate shipping") {
    const shippingOptions = document.getElementById("shipping-options");
    if (!shippingOptions) return;

    shippingOptions.innerHTML = `
        <div class="p-4 text-center border-2 border-dashed border-red-200 rounded-lg">
            <p class="text-red-600 mb-2">${errorMessage}</p>
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
    const shippingMethodEl = document.getElementById("shipping_method");
    const shippingCostEl = document.getElementById("shipping_cost");

    if (shippingMethodEl) shippingMethodEl.value = "";
    if (shippingCostEl) shippingCostEl.value = "0";

    updateOrderSummaryTotals(originalSubtotal, 0, discountAmount, pointsDiscount);
}

function selectShipping(radio) {
    console.log("Selected shipping:", radio.dataset.description);

    const shippingMethodEl = document.getElementById("shipping_method");
    const shippingCostEl = document.getElementById("shipping_cost");
    const shippingCost = parseInt(radio.dataset.cost);

    if (shippingMethodEl) shippingMethodEl.value = radio.dataset.description;
    if (shippingCostEl) shippingCostEl.value = shippingCost;

    updateOrderSummaryTotals(originalSubtotal, shippingCost, discountAmount, pointsDiscount);

    // Update selection styles
    const shippingOptions = document.getElementById("shipping-options");
    if (shippingOptions) {
        shippingOptions.querySelectorAll(".shipping-option").forEach((option) => {
            option.classList.remove("border-blue-500", "bg-blue-50");
            option.classList.add("border-gray-200");
        });

        radio.closest(".shipping-option").classList.add("border-blue-500", "bg-blue-50");
        radio.closest(".shipping-option").classList.remove("border-gray-200");
    }
}

// ADDRESS INTEGRATION SYSTEM
function initializeAddressIntegration() {
    console.log("Initializing address integration");

    setupAddressLabelSelection();
    setupLocationSearch();
    setupSavedAddressSelection();

    // Auto-load primary address if available
    if (userHasPrimaryAddress && primaryAddressId) {
        console.log("Auto-loading primary address:", primaryAddressId);
        setTimeout(() => {
            const primaryRadio = document.querySelector(`input[name="saved_address_id"][value="${primaryAddressId}"]`);
            if (primaryRadio) {
                primaryRadio.checked = true;
                updateSavedAddressStyles();
                loadSavedAddress(primaryAddressId);
            }
        }, 100);
    }
}

function setupSavedAddressSelection() {
    const savedAddressInputs = document.querySelectorAll('input[name="saved_address_id"]');

    savedAddressInputs.forEach((input) => {
        input.addEventListener("change", function () {
            console.log("Address selection changed:", this.value);

            updateSavedAddressStyles();

            if (this.value === "new") {
                showNewAddressForm();
            } else {
                loadSavedAddress(this.value);
            }
        });
    });
}

function updateSavedAddressStyles() {
    // Update saved address selection styles
    document.querySelectorAll("label[data-address-id]").forEach((label) => {
        label.classList.remove("border-orange-500", "bg-orange-50");
        label.classList.add("border-gray-200");
    });

    const checkedInput = document.querySelector('input[name="saved_address_id"]:checked');
    if (checkedInput && checkedInput.value !== "new") {
        const selectedLabel = checkedInput.closest("label");
        if (selectedLabel) {
            selectedLabel.classList.add("border-orange-500", "bg-orange-50");
            selectedLabel.classList.remove("border-gray-200");
        }
    }
}

function loadSavedAddress(addressId) {
    console.log("Loading saved address:", addressId);

    if (addressId === "new") {
        showNewAddressForm();
        return;
    }

    // Hide new address form when using saved address
    const newAddressForm = document.getElementById("new-address-form");
    if (newAddressForm) {
        newAddressForm.classList.add("hidden");
    }

    // Fetch address data
    fetch("/profile/addresses/" + addressId + "/show", {
        headers: {
            Accept: "application/json",
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
        },
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                populateAddressForm(data.address);
            } else {
                console.error("Failed to load address:", data.message);
                showNewAddressForm();
            }
        })
        .catch((error) => {
            console.error("Error loading address:", error);
            showNewAddressForm();
        });
}

function populateAddressForm(address) {
    console.log("Populating address form:", address);

    // Fill address form fields
    fillFieldIfEmpty("recipient_name", address.recipient_name);
    fillFieldIfEmpty("phone_recipient", address.phone_recipient);
    fillFieldIfEmpty("street_address", address.street_address);

    // Fill location fields
    fillFieldIfEmpty("province_name", address.province_name);
    fillFieldIfEmpty("city_name", address.city_name);
    fillFieldIfEmpty("subdistrict_name", address.subdistrict_name);
    fillFieldIfEmpty("postal_code", address.postal_code);
    fillFieldIfEmpty("destination_id", address.destination_id || "");

    // Fill legacy fields for backward compatibility
    fillFieldIfEmpty("legacy_address", address.full_address);
    fillFieldIfEmpty("legacy_destination_label", address.location_string);

    // Set selectedDestination for shipping calculation
    selectedDestination = {
        location_id: address.destination_id,
        label: address.location_string,
        full_address: address.full_address,
    };

    // Show selected location
    const selectedLocation = document.getElementById("selected-location");
    const selectedLocationText = document.getElementById("selected-location-text");

    if (selectedLocation && selectedLocationText) {
        selectedLocationText.textContent = address.location_string;
        selectedLocation.classList.remove("hidden");
    }

    // Set address label
    const labelInput = document.querySelector(`input[name="address_label"][value="${address.label}"]`);
    if (labelInput) {
        labelInput.checked = true;
        updateAddressLabelStyles();
    }

    // Disable save options since this is existing address
    const saveCheckbox = document.querySelector('input[name="save_address"]');
    const primaryCheckbox = document.querySelector('input[name="set_as_primary"]');
    if (saveCheckbox) saveCheckbox.checked = false;
    if (primaryCheckbox) primaryCheckbox.checked = false;
}

function showNewAddressForm() {
    console.log("Showing new address form");

    const newAddressForm = document.getElementById("new-address-form");
    if (newAddressForm) {
        newAddressForm.classList.remove("hidden");
    }

    // Get user data from meta tags and pre-fill
    const authenticatedUserName = document.querySelector('meta[name="authenticated-user-name"]')?.content || "";
    const authenticatedUserPhone = document.querySelector('meta[name="authenticated-user-phone"]')?.content || "";

    // Pre-fill with user data
    fillFieldIfEmpty("recipient_name", authenticatedUserName);
    fillFieldIfEmpty("phone_recipient", authenticatedUserPhone);

    // Clear other fields
    document.getElementById("street_address").value = "";
    document.getElementById("province_name").value = "";
    document.getElementById("city_name").value = "";
    document.getElementById("subdistrict_name").value = "";
    document.getElementById("postal_code").value = "";
    document.getElementById("destination_id").value = "";

    // Clear legacy fields
    const legacyAddress = document.getElementById("legacy_address");
    const legacyDestinationLabel = document.getElementById("legacy_destination_label");
    if (legacyAddress) legacyAddress.value = "";
    if (legacyDestinationLabel) legacyDestinationLabel.value = "";

    // Hide selected location
    const selectedLocation = document.getElementById("selected-location");
    if (selectedLocation) {
        selectedLocation.classList.add("hidden");
    }

    // Reset selectedDestination
    selectedDestination = null;

    // Reset address label to default
    const rumahOption = document.querySelector('input[name="address_label"][value="Rumah"]');
    if (rumahOption) {
        rumahOption.checked = true;
        updateAddressLabelStyles();
    }

    // Enable save options
    const saveCheckbox = document.querySelector('input[name="save_address"]');
    if (saveCheckbox) saveCheckbox.checked = true;
}

function setupAddressLabelSelection() {
    const addressLabelInputs = document.querySelectorAll('input[name="address_label"]');

    addressLabelInputs.forEach((input) => {
        input.addEventListener("change", updateAddressLabelStyles);
    });

    // Set default to "Rumah" if none selected
    if (!document.querySelector('input[name="address_label"]:checked')) {
        const rumahOption = document.querySelector('input[name="address_label"][value="Rumah"]');
        if (rumahOption) {
            rumahOption.checked = true;
            updateAddressLabelStyles();
        }
    }
}

function updateAddressLabelStyles() {
    const labels = document.querySelectorAll('label:has(input[name="address_label"])');

    labels.forEach((label) => {
        const input = label.querySelector('input[name="address_label"]');
        if (input && input.checked) {
            label.classList.add("border-orange-500", "bg-orange-50");
            label.classList.remove("border-gray-300");
        } else {
            label.classList.remove("border-orange-500", "bg-orange-50");
            label.classList.add("border-gray-300");
        }
    });
}
// Enhanced Checkout JS - Part 4
// Location Search & Voucher System

// LOCATION SEARCH SYSTEM
function setupLocationSearch() {
    const locationSearch = document.getElementById("location_search");
    const locationResults = document.getElementById("location-results");

    if (!locationSearch || !locationResults) return;

    locationSearch.addEventListener("input", function () {
        const query = this.value.trim();

        clearTimeout(searchTimeout);

        if (query.length < 2) {
            locationResults.classList.add("hidden");
            return;
        }

        searchTimeout = setTimeout(() => {
            searchLocation(query);
        }, 300);
    });

    // Hide results when clicking outside
    document.addEventListener("click", function (e) {
        if (!locationSearch.contains(e.target) && !locationResults.contains(e.target)) {
            locationResults.classList.add("hidden");
        }
    });
}

async function searchLocation(query) {
    const locationResults = document.getElementById("location-results");
    if (!locationResults) return;

    // Show loading
    locationResults.innerHTML = '<div class="p-3 text-center"><div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600 mx-auto"></div><span class="text-sm text-gray-600 ml-2">Searching...</span></div>';
    locationResults.classList.remove("hidden");

    try {
        const response = await fetch("/checkout/search-destinations?search=" + encodeURIComponent(query) + "&limit=10", {
            headers: {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
            },
        });

        if (response.ok) {
            const data = await response.json();
            displayLocationResults(data.data || []);
        } else {
            console.error("Location search failed:", response.status);
            locationResults.innerHTML = '<div class="p-3 text-center text-red-500">Search failed. Please try again.</div>';
        }
    } catch (error) {
        console.error("Location search error:", error);
        locationResults.innerHTML = '<div class="p-3 text-center text-red-500">Search failed. Please try again.</div>';
    }
}

function displayLocationResults(locations) {
    const locationResults = document.getElementById("location-results");

    if (locations.length === 0) {
        locationResults.innerHTML = '<div class="p-3 text-center text-gray-500">No locations found</div>';
        return;
    }

    locationResults.innerHTML = "";

    locations.forEach((location) => {
        const item = document.createElement("div");
        item.className = "p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0";
        item.innerHTML = `
            <div class="font-medium text-gray-900">${location.display_name || location.subdistrict_name}</div>
            <div class="text-sm text-gray-600">${location.full_address || location.label}</div>
        `;

        item.addEventListener("click", () => selectLocation(location));
        locationResults.appendChild(item);
    });

    locationResults.classList.remove("hidden");
}

function selectLocation(location) {
    console.log("Location selected:", location);

    // Fill location fields
    fillFieldIfEmpty("province_name", location.province_name || "");
    fillFieldIfEmpty("city_name", location.city_name || "");
    fillFieldIfEmpty("subdistrict_name", location.subdistrict_name || "");
    fillFieldIfEmpty("postal_code", location.zip_code || location.postal_code || "");

    // Use the correct destination ID from API response
    const destinationId = location.id || location.location_id || location.destination_id || "";
    fillFieldIfEmpty("destination_id", destinationId);

    console.log("Destination ID set:", destinationId);

    // Fill legacy fields for backward compatibility
    fillFieldIfEmpty("legacy_address", location.full_address || location.label || "");
    fillFieldIfEmpty("legacy_destination_label", location.full_address || location.label || "");

    // Update selectedDestination with correct ID
    selectedDestination = {
        ...location,
        destination_id: destinationId,
        location_id: destinationId,
    };

    // Display selected location
    const selectedLocationDiv = document.getElementById("selected-location");
    const selectedLocationText = document.getElementById("selected-location-text");

    if (selectedLocationDiv && selectedLocationText) {
        selectedLocationText.textContent = location.full_address || location.label ||
            location.subdistrict_name + ", " + location.city_name + ", " + location.province_name;
        selectedLocationDiv.classList.remove("hidden");
    }

    // Hide search results
    document.getElementById("location-results").classList.add("hidden");

    // Clear search input
    document.getElementById("location_search").value = "";

    // Trigger shipping calculation if we're on step 3
    if (currentStep >= 3) {
        setTimeout(() => calculateShipping(), 500);
    }
}

function clearLocation() {
    console.log("Clearing location");

    // Clear fields
    document.getElementById("province_name").value = "";
    document.getElementById("city_name").value = "";
    document.getElementById("subdistrict_name").value = "";
    document.getElementById("postal_code").value = "";
    document.getElementById("destination_id").value = "";

    // Clear legacy fields
    const legacyAddress = document.getElementById("legacy_address");
    const legacyDestinationLabel = document.getElementById("legacy_destination_label");
    if (legacyAddress) legacyAddress.value = "";
    if (legacyDestinationLabel) legacyDestinationLabel.value = "";

    // Hide selected location
    const selectedLocation = document.getElementById("selected-location");
    if (selectedLocation) {
        selectedLocation.classList.add("hidden");
    }

    // Reset selectedDestination
    selectedDestination = null;

    // Focus back to search
    const locationSearch = document.getElementById("location_search");
    if (locationSearch) {
        locationSearch.focus();
    }
}

// VOUCHER SYSTEM INTEGRATION
function initializeVoucherSystem() {
    console.log("Initializing voucher system integration...");
    const voucherInput = document.getElementById("voucher-code");
    if (voucherInput) {
        console.log("Voucher system elements found");
        loadCurrentAppliedVoucher();
        setupVoucherInputHandlers();
    } else {
        console.log("No voucher system elements found");
    }
}

function setupVoucherEventListeners() {
    // Voucher applied
    document.addEventListener("voucherApplied", function (e) {
        console.log("Voucher applied event received:", e.detail);

        const v = e.detail?.voucher || null;
        const discount = Number(v?.discount_amount || e.detail?.discount || 0);
        appliedVoucher = v;
        discountAmount = isNaN(discount) ? 0 : discount;

        // Update totals with voucher discount
        updateOrderSummaryTotals(originalSubtotal, getCurrentShippingCost(), discountAmount, pointsDiscount);

        // Recalculate shipping if needed
        if (currentStep >= 3 && selectedDestination) {
            setTimeout(() => calculateShipping(), 300);
        }

        showNotification("Voucher applied successfully!", "success");
    });

    // Voucher removed
    document.addEventListener("voucherRemoved", function (e) {
        console.log("Voucher removed event received:", e.detail);
        appliedVoucher = null;
        discountAmount = 0;

        updateOrderSummaryTotals(originalSubtotal, getCurrentShippingCost(), 0, pointsDiscount);

        if (currentStep >= 3 && selectedDestination) {
            setTimeout(() => calculateShipping(), 300);
        }

        showNotification("Voucher removed", "info");
    });

    // Points applied event
    document.addEventListener("pointsApplied", function (e) {
        console.log("Points applied event received:", e.detail);

        const pointsData = e.detail || {};
        const discount = Number(pointsData.discount || 0);
        appliedPoints = pointsData;
        pointsDiscount = isNaN(discount) ? 0 : discount;

        // Update totals with points discount
        updateOrderSummaryTotals(originalSubtotal, getCurrentShippingCost(), discountAmount, pointsDiscount);

        if (currentStep >= 3 && selectedDestination) {
            setTimeout(() => calculateShipping(), 300);
        }

        showNotification("Points applied successfully!", "success");
    });

    // Points removed event
    document.addEventListener("pointsRemoved", function (e) {
        console.log("Points removed event received:", e.detail);
        appliedPoints = null;
        pointsDiscount = 0;

        updateOrderSummaryTotals(originalSubtotal, getCurrentShippingCost(), discountAmount, 0);

        if (currentStep >= 3 && selectedDestination) {
            setTimeout(() => calculateShipping(), 300);
        }

        showNotification("Points removed", "info");
    });

    // Backward compatibility: old coupon removed event
    document.addEventListener("couponRemoved", function (e) {
        console.log("[compat] Coupon removed event received:", e.detail);
        appliedVoucher = null;
        discountAmount = 0;

        updateOrderSummaryTotals(originalSubtotal, getCurrentShippingCost(), 0, pointsDiscount);

        if (currentStep >= 3 && selectedDestination) {
            setTimeout(() => calculateShipping(), 300);
        }

        showNotification("Voucher removed", "info");
    });
}

// Check for applied voucher from session
function checkAppliedVoucher() {
    fetch("/api/vouchers/current", {
        headers: {
            Accept: "application/json",
        },
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success && data.voucher) {
                appliedVoucher = data.voucher;
                console.log("Applied voucher found:", appliedVoucher);
                // Update totals with existing discount
                updateOrderSummaryTotals(originalSubtotal, getCurrentShippingCost(), appliedVoucher.discount_amount || 0);
            }
        })
        .catch((error) => {
            console.log("No applied voucher found");
        });
}

// Load current applied voucher
function loadCurrentAppliedVoucher() {
    // Let voucher.js handle this
    if (window.voucherManager && typeof window.voucherManager.loadCurrentVoucher === "function") {
        window.voucherManager.loadCurrentVoucher();
    }
}

// Setup voucher input handlers
function setupVoucherInputHandlers() {
    const voucherInput = document.getElementById("voucher-code");
    if (voucherInput) {
        // Real-time validation (handled by voucher.js)
        // Just ensure we update totals when voucher changes
        voucherInput.addEventListener("focus", function () {
            console.log("Voucher input focused");
        });
    }
}

// Get current shipping cost
function getCurrentShippingCost() {
    const shippingCostEl = document.getElementById("shipping_cost");
    return shippingCostEl ? parseInt(shippingCostEl.value) || 0 : 0;
}

// Notification system
function showNotification(message, type = "info") {
    // Create notification element
    const notification = document.createElement("div");
    notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm transition-all duration-300 transform translate-x-full`;

    // Set colors based on type
    const colors = {
        success: "bg-green-500 text-white",
        error: "bg-red-500 text-white",
        warning: "bg-yellow-500 text-black",
        info: "bg-blue-500 text-white",
    };

    notification.className += ` ${colors[type] || colors.info}`;
    notification.textContent = message;

    // Add to page
    document.body.appendChild(notification);

    // Animate in
    setTimeout(() => {
        notification.classList.remove("translate-x-full");
        notification.classList.add("translate-x-0");
    }, 100);

    // Remove after 3 seconds
    setTimeout(() => {
        notification.classList.add("translate-x-full");
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

function checkAppliedPoints() {
    // Check from session or meta tags
    const appliedPointsMeta = document.querySelector('meta[name="applied-points-used"]');
    const appliedDiscountMeta = document.querySelector('meta[name="applied-points-discount"]');

    if (appliedPointsMeta && appliedDiscountMeta) {
        const pointsUsed = parseInt(appliedPointsMeta.content) || 0;
        const discount = parseInt(appliedDiscountMeta.content) || 0;

        if (pointsUsed > 0) {
            appliedPoints = {
                points_used: pointsUsed,
                discount: discount,
            };
            pointsDiscount = discount;

            console.log("Applied points found from server:", appliedPoints);

            // Update totals with points discount
            updateOrderSummaryTotals(originalSubtotal, getCurrentShippingCost(), discountAmount, pointsDiscount);
        }
    }
}

// EVENT LISTENERS SETUP
function setupEventListeners() {
    // Form submission handling
    const checkoutForm = document.getElementById("checkout-form");
    if (checkoutForm) {
        checkoutForm.addEventListener("submit", function (e) {
            e.preventDefault();

            if (isSubmittingOrder) {
                console.log("Order submission already in progress");
                return false;
            }

            // Validate current step
            if (!validateCurrentStep()) {
                return false;
            }

            // Check payment method
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked')?.value;
            if (!paymentMethod) {
                alert("Please select a payment method.");
                return false;
            }

            handleOrderSubmission(paymentMethod);
        });
    }

    // Listen for shipping cost changes to update totals
    document.addEventListener("change", function (e) {
        if (e.target.name === "shipping_method" || e.target.id === "shipping_cost") {
            const shippingCostElement = document.getElementById("shipping_cost");
            const shippingCost = shippingCostElement ? parseInt(shippingCostElement.value) || 0 : 0;
            updateOrderSummaryTotals(originalSubtotal, shippingCost, discountAmount, pointsDiscount);
        }
    });
}

// TEST RAJAONGKIR CONNECTION
function testRajaOngkirConnection() {
    console.log("Testing RajaOngkir V2 connection...");

    fetch("/checkout/search-destinations?search=jakarta&limit=1", {
        method: "GET",
        headers: {
            Accept: "application/json",
            "X-Requested-With": "XMLHttpRequest",
        },
    })
        .then((response) => {
            console.log("RajaOngkir test response status:", response.status);
            return response.json();
        })
        .then((data) => {
            if (data.success && data.data && data.data.length > 0) {
                console.log("RajaOngkir V2 connection successful");
                showSuccess("Shipping service connected successfully");
            } else {
                console.log("RajaOngkir returned empty data");
            }
        })
        .catch((error) => {
            console.error("RajaOngkir connection failed:", error);
            showError("Failed to connect to shipping service. Using fallback options.");
        });
}

// STATUS MESSAGE FUNCTIONS
function showSuccess(message) {
    const statusEl = document.getElementById("connection-status");
    const statusText = document.getElementById("status-text");
    if (statusEl && statusText) {
        statusEl.className = "mb-4 p-3 rounded-lg border bg-green-50 border-green-200";
        statusText.textContent = message;
        statusEl.classList.remove("hidden");
        setTimeout(() => statusEl.classList.add("hidden"), 5000);
    }
}

function showError(message) {
    const statusEl = document.getElementById("connection-status");
    const statusText = document.getElementById("status-text");
    if (statusEl && statusText) {
        statusEl.className = "mb-4 p-3 rounded-lg border bg-red-50 border-red-200";
        statusText.textContent = message;
        statusEl.classList.remove("hidden");
    }
}
// Enhanced Checkout JS - Part 5 (Final)
// Order Submission & Payment Integration

// ORDER SUBMISSION HANDLING
function handleOrderSubmission(paymentMethod) {
    console.log("Processing order with payment method:", paymentMethod);

    if (isSubmittingOrder) {
        console.log("Order submission already in progress");
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

    // Include voucher data if applied
    if (appliedVoucher) {
        console.log("Adding voucher data to form submission:", appliedVoucher);
        formData.set("applied_voucher_code", appliedVoucher.voucher_code);
        formData.set("applied_voucher_discount", appliedVoucher.discount_amount);
    }

    // Include points data if applied
    const currentPointsData = getCurrentPointsData();
    if (currentPointsData) {
        console.log("Adding points data to form submission:", currentPointsData);
        formData.set("points_used", currentPointsData.points_used);
        formData.set("points_discount", currentPointsData.discount);
    } else {
        // Ensure these fields are set to 0 if no points applied
        formData.set("points_used", "0");
        formData.set("points_discount", "0");
    }

    // Ensure all address fields are properly filled
    validateAndFillAddressFields(formData);

    // Debug form data
    console.log("Form data being sent:");
    for (let [key, value] of formData.entries()) {
        console.log(`${key}: ${value}`);
    }

    // Ensure privacy_accepted is set correctly
    const privacyCheckbox = document.getElementById("privacy_accepted");
    if (privacyCheckbox && privacyCheckbox.checked) {
        formData.set("privacy_accepted", "1");
        console.log("Privacy accepted: true");
    } else {
        console.log("Privacy not accepted");
        resetSubmitButton();
        alert("Please accept the privacy policy to continue.");
        isSubmittingOrder = false;
        return;
    }

    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content");
    console.log("CSRF Token:", csrfToken ? "Found" : "Not found");

    // Submit with enhanced error handling
    fetch("/checkout", {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": csrfToken,
            Accept: "application/json",
            "X-Requested-With": "XMLHttpRequest",
            "Content-Type": "application/x-www-form-urlencoded",
        },
        body: new URLSearchParams(formData),
    })
        .then(async (response) => {
            console.log("Response status:", response.status);

            const contentType = response.headers.get("content-type");
            console.log("Content type:", contentType);

            if (contentType && contentType.includes("application/json")) {
                const data = await response.json();
                console.log("JSON Response received:", data);
                return { success: true, data: data, status: response.status };
            } else {
                const text = await response.text();
                console.log("Non-JSON response received:", text.substring(0, 500));

                try {
                    const data = JSON.parse(text);
                    return { success: true, data: data, status: response.status };
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

                if (data.success) {
                    console.log("Order successful:", data);
                    handleSuccessfulOrder(data, paymentMethod);
                } else if (data.errors) {
                    console.log("Validation errors:", data.errors);
                    handleOrderErrors(data.errors);
                } else if (data.error) {
                    console.log("Order error:", data.error);
                    handleOrderError(data.error);
                } else {
                    console.log("Unexpected response format:", data);
                    handleOrderError("Unexpected response format from server");
                }
            } else {
                console.log("Request failed:", result);
                handleOrderError(result.error || "Server error occurred");
            }
        })
        .catch((error) => {
            console.error("Network error:", error);
            handleOrderError("Failed to connect to server. Please check your internet connection and try again.");
        })
        .finally(() => {
            console.log("Request completed");
            isSubmittingOrder = false;
            resetSubmitButton();
        });
}

function validateAndFillAddressFields(formData) {
    // Ensure all required address fields are filled
    const requiredAddressFields = [
        "recipient_name", "phone_recipient", "province_name", "city_name", 
        "subdistrict_name", "postal_code", "street_address",
    ];

    requiredAddressFields.forEach((field) => {
        const element = document.getElementById(field);
        if (element && element.value) {
            formData.set(field, element.value);
        }
    });

    // Ensure destination_id is set
    const destinationIdElement = document.getElementById("destination_id");
    if (destinationIdElement && destinationIdElement.value) {
        formData.set("destination_id", destinationIdElement.value);
    }

    // Set address label if not set
    if (!formData.get("address_label")) {
        const addressLabelInput = document.querySelector('input[name="address_label"]:checked');
        if (addressLabelInput) {
            formData.set("address_label", addressLabelInput.value);
        } else {
            formData.set("address_label", "Rumah"); // Default
        }
    }

    // Fill legacy address field for backward compatibility
    const streetAddress = formData.get("street_address");
    const locationString = `${formData.get("subdistrict_name")}, ${formData.get("city_name")}, ${formData.get("province_name")} ${formData.get("postal_code")}`;

    if (streetAddress) {
        const fullAddress = `${streetAddress}, ${locationString}`;
        formData.set("address", fullAddress);
        formData.set("destination_label", locationString);
    }
}

// PAYMENT INTEGRATION
async function handleSuccessfulOrder(data, paymentMethod, redirectUrl = null) {
    console.log("Handling successful order with enhanced detection:", data);

    const orderNumber = data.order_number;
    const preferHosted = data.prefer_hosted || false;
    const forceHosted = data.force_hosted || false;

    // helper redirect
    const goRedirect = (why = "fallback") => {
        console.warn(`Fallback redirect triggered (${why})`);
        if (data.redirect_url) {
            console.log("Using provided redirect URL");
            window.location.href = data.redirect_url;
        } else if (orderNumber) {
            console.log("Using order payment page");
            window.location.href = `/checkout/payment/${orderNumber}`;
        } else {
            handleOrderError("Payment session not available. Please refresh the page.");
        }
    };

    // Check force hosted flag
    if (forceHosted || !data.snap_token) {
        console.log("Force hosted payment mode", {
            forceHosted, hasToken: !!data.snap_token,
            reason: forceHosted ? "Network issues detected" : "No token provided",
        });

        showSuccess("Redirecting to secure payment page...");
        setTimeout(() => goRedirect("force_hosted"), 1000);
        return;
    }

    // Network-aware popup attempt
    const popupTimeout = preferHosted ? 2000 : 5000;
    console.log(`Network status: ${preferHosted ? "prefer hosted" : "can try popup"}, timeout: ${popupTimeout}ms`);

    try {
        // Quick timeout for script loading if network is poor
        const scriptTimeout = preferHosted ? 3000 : 8000;

        const loadPromise = loadMidtransScript();
        const timeoutPromise = new Promise((_, reject) => {
            setTimeout(() => reject(new Error("Script timeout")), scriptTimeout);
        });

        await Promise.race([loadPromise, timeoutPromise]);

        showSuccess("Opening payment gateway...");

        // Try popup with network-aware timeout
        const popupPromise = new Promise((resolve, reject) => {
            try {
                window.snap.pay(data.snap_token, {
                    onSuccess: function (result) {
                        console.log("Payment successful:", result);
                        showSuccess("Payment successful! Redirecting...");
                        setTimeout(() => {
                            const oid = result.order_id || orderNumber;
                            window.location.href = `/checkout/success/${oid}?payment=success`;
                        }, 1200);
                        resolve();
                    },
                    onPending: function (result) {
                        console.log("Payment pending:", result);
                        showError("Payment is being processed. You will receive confirmation shortly.");
                        setTimeout(() => {
                            const oid = result.order_id || orderNumber;
                            window.location.href = `/checkout/success/${oid}?payment=pending`;
                        }, 1500);
                        resolve();
                    },
                    onError: function (result) {
                        console.error("Payment error:", result);
                        reject(new Error("Payment error"));
                    },
                    onClose: function () {
                        console.log("Payment popup closed by user");
                        showError("Payment was cancelled. Redirecting to your orders...");
                        setTimeout(() => {
                            window.location.href = "/orders";
                        }, 1000);
                        resolve();
                    },
                });
                console.log("Snap.pay called successfully");
            } catch (snapErr) {
                reject(snapErr);
            }
        });

        const quickTimeout = new Promise((_, reject) => {
            setTimeout(() => reject(new Error("Popup timeout")), popupTimeout);
        });

        await Promise.race([popupPromise, quickTimeout]);
    } catch (e) {
        console.error("Enhanced error handling:", e.message);

        // Smart fallback based on error type
        if (e.message.includes("timeout") || preferHosted) {
            console.log("Network timeout detected, using hosted payment");
            showSuccess("Opening secure payment page...");
        } else {
            console.log("Script error, falling back to hosted payment");
            showError("Loading payment gateway...");
        }

        setTimeout(() => goRedirect(e.message.includes("timeout") ? "network_timeout" : "script_error"), 800);
    }
}

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
    showError("Please fix the errors and try again.");
}

function handleOrderError(message) {
    console.error("Order error:", message);
    alert(message);
    showError(message);
}

function resetSubmitButton() {
    const submitBtn = document.getElementById("place-order-btn");
    if (!submitBtn) return;

    submitBtn.disabled = false;
    submitBtn.textContent = "Continue to Payment";
    submitBtn.className = "flex-1 bg-green-600 text-white py-3 rounded-lg hover:bg-green-700 transition-colors font-medium";
}

function showProcessingMessage(paymentMethod) {
    const statusEl = document.getElementById("connection-status");
    const statusText = document.getElementById("status-text");

    if (!statusEl || !statusText) return;

    const message = "Creating payment session...";

    statusEl.className = "mb-4 p-3 rounded-lg border bg-blue-50 border-blue-200";
    statusText.textContent = message;
    statusEl.classList.remove("hidden");
}

// MIDTRANS INTEGRATION
function loadMidtransScript() {
    return new Promise((resolve, reject) => {
        try {
            if (window.snap) {
                console.log("snap already present");
                return resolve();
            }

            const clientKeyMeta = document.querySelector('meta[name="midtrans-client-key"]');
            const prodMeta = document.querySelector('meta[name="midtrans-production"]');
            const clientKey = clientKeyMeta?.content || "";
            const isProduction = prodMeta?.content === "true";

            if (!clientKey) {
                return reject(new Error("Midtrans client key not found"));
            }

            // Check existing script
            const existing = document.querySelector("script[data-client-key]");
            if (existing) {
                let checkCount = 0;
                const check = setInterval(() => {
                    checkCount++;
                    if (window.snap) {
                        clearInterval(check);
                        resolve();
                    } else if (checkCount > 10) {
                        clearInterval(check);
                        reject(new Error("Existing script timeout"));
                    }
                }, 100);
                return;
            }

            const script = document.createElement("script");
            script.src = isProduction ? "https://app.midtrans.com/snap/snap.js" : "https://app.sandbox.midtrans.com/snap/snap.js";
            script.setAttribute("data-client-key", clientKey);

            const scriptTimeout = setTimeout(() => {
                script.remove();
                reject(new Error("Script loading timeout"));
            }, 1500);

            script.onload = () => {
                clearTimeout(scriptTimeout);
                if (window.snap) {
                    resolve();
                } else {
                    reject(new Error("Snap object not available"));
                }
            };

            script.onerror = (e) => {
                clearTimeout(scriptTimeout);
                script.remove();
                reject(new Error("Failed to load script"));
            };

            document.head.appendChild(script);
        } catch (e) {
            reject(e);
        }
    });
}

// MAKE FUNCTIONS GLOBALLY AVAILABLE
window.nextStep = nextStep;
window.prevStep = prevStep;
window.selectLocation = selectLocation;
window.clearLocation = clearLocation;
window.calculateShipping = calculateShipping;
window.selectShipping = selectShipping;
window.togglePassword = togglePassword;
window.loadSavedAddress = loadSavedAddress;
window.showNewAddressForm = showNewAddressForm;
window.updateAddressLabelStyles = updateAddressLabelStyles;
window.updateOrderSummaryTotals = updateOrderSummaryTotals;
window.validateNewAddressFormHierarchical = validateNewAddressFormHierarchical;
window.fillLegacyFieldsFromHierarchical = fillLegacyFieldsFromHierarchical;
window.setupHierarchicalLocationHandlers = setupHierarchicalLocationHandlers;
window.initializeStepNavigation = initializeStepNavigation;

console.log("Enhanced Checkout COMPLETE - HIERARCHICAL SYSTEM READY!");
console.log("Key features implemented:");
console.log("- Hierarchical location selection (Province → City → District → Sub District)");
console.log("- Fixed step navigation system");
console.log("- Server validation compatibility");
console.log("- Auto-fill personal information");
console.log("- Enhanced saved address loading");
console.log("- Order summary with voucher & points support");
console.log("- Shipping calculation with proper destination ID");
console.log("- Midtrans payment integration with fallback");
console.log("- NO TAX calculations throughout");

// END OF ENHANCED CHECKOUT JS - COMPLETE SYSTEM