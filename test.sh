#!/usr/bin/env bash
set -e

# ============================================
# 🧠  GINEE API MANUAL TEST (v2 READ-ONLY SAFE)
# ============================================

API_URL="https://api.ginee.com"
ACCESS_KEY="6505d28a3bb0b621"
SECRET_KEY="f88d75ae803fbbdd"
COUNTRY="ID"
DATE=$(date -u +"%Y-%m-%dT%H:%M:%SZ")

# ✅ Body yang digunakan untuk request
BODY='{"page":1,"size":10}'

# ✅ SHA256 (bukan MD5) untuk body
BODY_HASH=$(echo -n "$BODY" | openssl dgst -sha256 | awk '{print $2}' | tr 'a-z' 'A-Z')

# ============================================
# 🔹 Helper function untuk sign & call API
# ============================================
call_ginee_api_v2() {
  local ENDPOINT_PATH="$1"
  local TITLE="$2"

  # 🔑 String to sign (HMAC-SHA256, uppercase hex digest)
  STRING_TO_SIGN=$(printf "%s\n%s\n%s\n%s\n%s" \
    "POST" \
    "$ENDPOINT_PATH" \
    "$BODY_HASH" \
    "$DATE" \
    "$COUNTRY")

  SIGNATURE=$(printf "%s" "$STRING_TO_SIGN" | \
    openssl dgst -sha256 -hmac "$SECRET_KEY" | \
    awk '{print $2}' | tr 'a-z' 'A-Z')

  echo "====================================================="
  echo "📦  Testing $TITLE"
  echo "====================================================="
  echo "PATH        : $ENDPOINT_PATH"
  echo "DATE        : $DATE"
  echo "ACCESS_KEY  : $ACCESS_KEY"
  echo "BODY_HASH   : $BODY_HASH"
  echo "STRING_SIGN :"
  echo "$STRING_TO_SIGN"
  echo "SIGNATURE   : $SIGNATURE"
  echo "====================================================="

  # 🧩 Execute curl
  RESPONSE=$(curl -s -w "\nHTTP %{http_code}\n" -X POST "${API_URL}${ENDPOINT_PATH}" \
    -H "Content-Type: application/json" \
    -H "Authorization: ${ACCESS_KEY}:${SIGNATURE}" \
    -H "X-Advai-Date: ${DATE}" \
    -H "X-Advai-Country: ${COUNTRY}" \
    -d "$BODY")

  echo "$RESPONSE"
  echo
}

# ============================================
# 🚀 Run tests (read-only)
# ============================================

call_ginee_api_v2 "/openapi/warehouse/search" "Warehouse v2 Search (READ ONLY)"
call_ginee_api_v2 "/openapi/warehouse-inventory/sku/list" "Warehouse Inventory v2 SKU List (READ ONLY)"

echo "====================================================="
echo "✅ Test Completed - Check JSON responses above"
echo "====================================================="
