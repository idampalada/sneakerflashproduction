/**
 * Enhanced Checkout Profile Integration with Field Locking
 * File: public/js/checkout-profile.js
 */

document.addEventListener("DOMContentLoaded", function () {
    console.log(
        "Enhanced Checkout Profile Integration with Field Locking loaded"
    );

    // Check if we're on checkout page
    const checkoutForm = document.getElementById("checkout-form");
    if (!checkoutForm) return;

    // Check if user is logged in
    const userLoggedIn = document.querySelector(
        'meta[name="user-authenticated"]'
    );

    if (userLoggedIn && userLoggedIn.content === "true") {
        console.log("User is logged in - loading profile data");
        showProfileLoadingIndicator();
        loadUserProfileData();
    }

    // Initialize basic form enhancements
    initializeBasicValidation();
});

/**
 * Show loading indicator for profile data
 */
function showProfileLoadingIndicator() {
    const personalInfoSection = document.querySelector("#section-1 .bg-white");
    if (personalInfoSection) {
        const loadingDiv = document.createElement("div");
        loadingDiv.id = "profile-loading-indicator";
        loadingDiv.className =
            "bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4";
        loadingDiv.innerHTML = `
            <div class="flex items-center">
                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
                <span class="ml-2 text-sm text-blue-700">Loading your profile data...</span>
            </div>
        `;

        const heading = personalInfoSection.querySelector("h2");
        if (heading) {
            heading.parentNode.insertBefore(loadingDiv, heading.nextSibling);
        }
    }
}

/**
 * Remove loading indicator
 */
function removeProfileLoadingIndicator() {
    const indicator = document.getElementById("profile-loading-indicator");
    if (indicator) {
        indicator.remove();
    }
}

/**
 * Load user profile data for auto-fill with field locking
 */
async function loadUserProfileData() {
    try {
        const response = await fetch("/api/profile/data", {
            method: "GET",
            headers: {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN":
                    document.querySelector('meta[name="csrf-token"]')
                        ?.content || "",
            },
        });

        if (response.ok) {
            const result = await response.json();

            if (result.success && result.data) {
                populateCheckoutFormWithLocking(result.data);
                removeProfileLoadingIndicator();
                showProfileSuccessNotification(result.data);
                updateProfileCompletionIndicator(result.data);
            } else {
                console.warn("Profile data request failed:", result);
                removeProfileLoadingIndicator();
                showProfileErrorNotification("Failed to load profile data");
            }
        } else {
            console.warn("Profile data request failed:", response.status);
            removeProfileLoadingIndicator();
            showProfileErrorNotification("Unable to load profile data");
        }
    } catch (error) {
        console.error("Error loading profile data:", error);
        removeProfileLoadingIndicator();
        showProfileErrorNotification("Error loading profile data");
    }
}

/**
 * Populate checkout form with profile data and apply field locking
 * Similar to profile page behavior - filled fields become locked
 */
function populateCheckoutFormWithLocking(profileData) {
    console.log(
        "Populating form with profile data and applying locks:",
        profileData
    );

    // Helper function to set field value and lock if filled
    const setFieldValueAndLock = (fieldId, value, fieldName = "") => {
        const field = document.getElementById(fieldId);
        if (!field) return false;

        if (value && value.trim() !== "") {
            // Field has data - populate and lock
            field.value = value.trim();
            lockField(field, fieldName);
            return true;
        } else {
            // Field is empty - keep editable
            unlockField(field, fieldName);
            return false;
        }
    };

    // Helper function to set gender and lock if filled
    const setGenderAndLock = (genderValue) => {
        if (!genderValue || genderValue.trim() === "") return false;

        const genderRadio = document.querySelector(
            `input[name="gender"][value="${genderValue.trim().toLowerCase()}"]`
        );

        if (genderRadio) {
            genderRadio.checked = true;

            // Lock all gender radio buttons
            const allGenderRadios = document.querySelectorAll(
                'input[name="gender"]'
            );
            allGenderRadios.forEach((radio) => {
                radio.disabled = true;
                // Add visual indicator to parent label
                const parentLabel = radio.closest("label");
                if (parentLabel) {
                    parentLabel.classList.add(
                        "opacity-60",
                        "cursor-not-allowed"
                    );
                }
            });

            return true;
        }
        return false;
    };

    let fieldsPopulated = 0;
    let fieldsLocked = 0;

    // Handle name fields - split full name and lock if data exists
    if (profileData.name && profileData.name.trim()) {
        const nameParts = profileData.name.trim().split(" ");
        const firstName = nameParts[0];
        const lastName = nameParts.slice(1).join(" ") || firstName;

        if (setFieldValueAndLock("first_name", firstName, "First Name")) {
            fieldsPopulated++;
            fieldsLocked++;
        }
        if (setFieldValueAndLock("last_name", lastName, "Last Name")) {
            fieldsPopulated++;
            fieldsLocked++;
        }
    }

    // Handle email - always lock for logged in users
    if (profileData.email && profileData.email.trim()) {
        const emailField = document.getElementById("email");
        if (emailField) {
            emailField.value = profileData.email.trim();
            lockField(emailField, "Email", true); // Force lock for email
            fieldsPopulated++;
            fieldsLocked++;
        }
    }

    // Handle phone - lock if filled
    if (setFieldValueAndLock("phone", profileData.phone, "Phone Number")) {
        fieldsPopulated++;
        fieldsLocked++;
    }

    // Handle birthdate - lock if filled
    if (setFieldValueAndLock("birthdate", profileData.birthdate, "Birthdate")) {
        fieldsPopulated++;
        fieldsLocked++;
    }

    // Handle gender - lock if filled
    if (setGenderAndLock(profileData.gender)) {
        fieldsPopulated++;
        fieldsLocked++;
    }

    // Handle newsletter subscription suggestion
    if (
        profileData.suggestions &&
        profileData.suggestions.newsletter_subscribe
    ) {
        const newsletterCheckbox = document.getElementById(
            "newsletter_subscribe"
        );
        if (newsletterCheckbox && !newsletterCheckbox.checked) {
            newsletterCheckbox.checked = true;
        }
    }

    console.log(
        `✅ ${fieldsPopulated} fields populated, ${fieldsLocked} fields locked`
    );

    // Summary notification removed - keeping it clean
}

/**
 * Lock a field and apply visual indicators
 */
function lockField(field, fieldName = "", forceEmailLock = false) {
    // Disable the field
    field.disabled = true;

    // Apply locked styling
    field.classList.add(
        "bg-gray-100",
        "text-gray-600",
        "cursor-not-allowed",
        "border-gray-300"
    );
    field.classList.remove("border-red-500", "border-green-500");

    // Remove any existing indicators and explanations
    const label = field.closest("div").querySelector("label");
    if (label) {
        const existingIndicators = label.querySelectorAll(
            ".lock-indicator, .editable-indicator"
        );
        existingIndicators.forEach((indicator) => indicator.remove());
    }

    // Remove existing explanation
    const existingExplanation =
        field.parentNode.querySelector(".field-explanation");
    if (existingExplanation) {
        existingExplanation.remove();
    }
}

/**
 * Unlock a field and apply editable styling
 */
function unlockField(field, fieldName = "") {
    // Enable the field
    field.disabled = false;

    // Apply editable styling
    field.classList.remove(
        "bg-gray-100",
        "text-gray-600",
        "cursor-not-allowed",
        "border-gray-300"
    );
    field.classList.add(
        "border-gray-300",
        "focus:ring-2",
        "focus:ring-blue-500"
    );

    // Remove any existing indicators and explanations
    const label = field.closest("div").querySelector("label");
    if (label) {
        const existingIndicators = label.querySelectorAll(
            ".lock-indicator, .editable-indicator"
        );
        existingIndicators.forEach((indicator) => indicator.remove());
    }

    // Remove existing explanation
    const existingExplanation =
        field.parentNode.querySelector(".field-explanation");
    if (existingExplanation) {
        existingExplanation.remove();
    }
}

// Remove unused functions
// addFieldExplanation and showFieldLockIndicator functions removed

// Remove unused showFieldLockingSummary function

/**
 * Update profile completion indicator
 */
function updateProfileCompletionIndicator(profileData) {
    const personalInfoSection = document.querySelector("#section-1");
    if (!personalInfoSection) return;

    const completionPercentage = profileData.profile_completion_percentage || 0;
    const isComplete = profileData.is_profile_complete || false;

    // Remove existing indicator
    const existingIndicator = document.getElementById(
        "profile-completion-indicator"
    );
    if (existingIndicator) {
        existingIndicator.remove();
    }

    // Only show if profile is not complete
    if (!isComplete) {
        const indicatorDiv = document.createElement("div");
        indicatorDiv.id = "profile-completion-indicator";
        indicatorDiv.className =
            "bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4";

        indicatorDiv.innerHTML = `
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-yellow-800">
                    Profile Completion: ${completionPercentage}%
                </span>
                <a href="/profile" class="text-yellow-600 text-sm hover:underline">Complete Profile</a>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-yellow-600 h-2 rounded-full transition-all" style="width: ${completionPercentage}%"></div>
            </div>
            <p class="text-xs text-yellow-700 mt-2">Complete your profile to speed up future checkouts and protect your data</p>
        `;

        // Insert after the heading
        const heading = personalInfoSection.querySelector("h2");
        if (heading) {
            heading.parentNode.insertBefore(indicatorDiv, heading.nextSibling);
        }
    }
}

/**
 * Enhanced notification functions
 */
function showProfileSuccessNotification(profileData) {
    const fieldsLoaded = [];
    if (profileData.name) fieldsLoaded.push("Name");
    if (profileData.email) fieldsLoaded.push("Email");
    if (profileData.phone) fieldsLoaded.push("Phone");
    if (profileData.gender) fieldsLoaded.push("Gender");
    if (profileData.birthdate) fieldsLoaded.push("Birthdate");

    const message = `Profile loaded! Auto-filled: ${fieldsLoaded.join(", ")}`;
    showSimpleNotification(message, "success");
}

function showProfileErrorNotification(message) {
    showSimpleNotification(message, "error");
}

/**
 * Initialize basic form validation for unlocked fields
 */
function initializeBasicValidation() {
    // Email validation (will be locked for logged in users)
    const emailField = document.getElementById("email");
    if (emailField) {
        emailField.addEventListener("blur", function () {
            if (!this.disabled && this.value && !isValidEmail(this.value)) {
                this.classList.add("border-red-500");
                showFieldError(this, "Please enter a valid email address");
            } else if (!this.disabled) {
                this.classList.remove("border-red-500");
                hideFieldError(this);
            }
        });
    }

    // Phone validation (will be locked if user has phone)
    const phoneField = document.getElementById("phone");
    if (phoneField) {
        phoneField.addEventListener("blur", function () {
            if (!this.disabled && this.value && this.value.length < 10) {
                this.classList.add("border-red-500");
                showFieldError(this, "Please enter a valid phone number");
            } else if (!this.disabled) {
                this.classList.remove("border-red-500");
                hideFieldError(this);
            }
        });
    }

    // Birthdate validation
    const birthdateField = document.getElementById("birthdate");
    if (birthdateField) {
        birthdateField.addEventListener("change", function () {
            if (!this.disabled && this.value) {
                const selectedDate = new Date(this.value);
                const today = new Date();

                if (selectedDate >= today) {
                    this.classList.add("border-red-500");
                    showFieldError(this, "Birthdate must be in the past");
                } else {
                    this.classList.remove("border-red-500");
                    hideFieldError(this);
                }
            }
        });
    }
}

/**
 * Utility Functions
 */
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function showFieldError(field, message) {
    hideFieldError(field);

    const errorElement = document.createElement("p");
    errorElement.className = "field-error text-red-600 text-sm mt-1";
    errorElement.textContent = message;

    field.parentNode.insertBefore(errorElement, field.nextSibling);
}

function hideFieldError(field) {
    const errorElement = field.parentNode.querySelector(".field-error");
    if (errorElement) {
        errorElement.remove();
    }
}

function showSimpleNotification(message, type = "info") {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll(
        ".checkout-notification"
    );
    existingNotifications.forEach((notif) => notif.remove());

    const colorClasses = {
        success: "bg-green-100 border-green-400 text-green-700",
        error: "bg-red-100 border-red-400 text-red-700",
        info: "bg-blue-100 border-blue-400 text-blue-700",
    };

    const notification = document.createElement("div");
    notification.className = `checkout-notification fixed top-4 right-4 ${colorClasses[type]} px-4 py-3 rounded shadow-lg z-50 max-w-md`;
    notification.innerHTML = `
        <div class="flex items-center justify-between">
            <span class="text-sm">${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-4 hover:opacity-75">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
    `;

    document.body.appendChild(notification);

    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

// Export enhanced functions for global use
window.checkoutProfile = {
    loadUserProfileData,
    populateCheckoutFormWithLocking,
    showSimpleNotification,
    showProfileSuccessNotification,
    showProfileErrorNotification,
};
