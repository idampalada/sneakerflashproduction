<?php

// File: app/Http/Controllers/Frontend/AddressController.php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AddressController extends Controller
{
    /**
     * Display user addresses
     */
    public function index()
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to manage addresses.');
        }

        try {
            $user = Auth::user();
            // Query langsung seperti controller lain yang bersih
            $addresses = UserAddress::where('user_id', $user->id)
                            ->where('is_active', true)
                            ->orderBy('is_primary', 'desc')
                            ->orderBy('created_at', 'desc')
                            ->paginate(10);
            
            Log::info('Address management page accessed', [
                'user_id' => $user->id,
                'address_count' => $addresses->total()
            ]);

            return view('frontend.profile.addresses.index', compact('addresses'));

        } catch (\Exception $e) {
            Log::error('Error loading addresses: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('profile.index')->with('error', 'Failed to load addresses.');
        }
    }

    /**
     * Show form to create new address
     */
    public function create()
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to add address.');
        }

        return view('frontend.profile.addresses.create');
    }

    /**
     * Store new address
     */
    public function store(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to add address.');
        }

        try {
            $validated = $request->validate([
                'label' => 'required|string|in:Kantor,Rumah',
                'recipient_name' => 'required|string|max:255',
                'phone_recipient' => 'required|string|max:20|regex:/^[0-9+\-\s\(\)]{10,}$/',
                'province_name' => 'required|string|max:100',
                'city_name' => 'required|string|max:100',
                'subdistrict_name' => 'required|string|max:100',
                'postal_code' => 'required|string|size:5|regex:/^[0-9]{5}$/',
                'destination_id' => 'nullable|string|max:50',
                'street_address' => 'required|string|min:10|max:500',
                'notes' => 'nullable|string|max:500',
                'is_primary' => 'nullable|boolean'
            ], [
                'label.required' => 'Please select address label (Kantor or Rumah)',
                'label.in' => 'Address label must be either Kantor or Rumah',
                'recipient_name.required' => 'Recipient name is required',
                'phone_recipient.required' => 'Recipient phone number is required',
                'phone_recipient.regex' => 'Please enter a valid phone number (minimum 10 digits)',
                'province_name.required' => 'Please select a location',
                'city_name.required' => 'Please select a location',
                'subdistrict_name.required' => 'Please select a location',
                'postal_code.required' => 'Postal code is required',
                'postal_code.size' => 'Postal code must be exactly 5 digits',
                'postal_code.regex' => 'Postal code must contain only numbers',
                'street_address.required' => 'Street address is required',
                'street_address.min' => 'Street address must be at least 10 characters long'
            ]);

            $user = Auth::user();

            // Use database transaction for data consistency
            $address = DB::transaction(function () use ($user, $validated) {
                // Create the address - query langsung
                $address = UserAddress::create([
                    'user_id' => $user->id,
                    'label' => $validated['label'],
                    'recipient_name' => trim($validated['recipient_name']),
                    'phone_recipient' => preg_replace('/[^0-9+\-\s\(\)]/', '', $validated['phone_recipient']),
                    'province_name' => $validated['province_name'],
                    'city_name' => $validated['city_name'],
                    'subdistrict_name' => $validated['subdistrict_name'],
                    'postal_code' => $validated['postal_code'],
                    'destination_id' => $validated['destination_id'] ?? null,
                    'street_address' => trim($validated['street_address']),
                    'notes' => !empty($validated['notes']) ? trim($validated['notes']) : null,
                    'is_primary' => false, // Will be set by model events if this is the first address
                    'is_active' => true
                ]);

                // Set as primary if explicitly requested and not already set by model events
                if (($validated['is_primary'] ?? false) && !$address->is_primary) {
                    $address->setPrimary();
                }

                return $address;
            });

            Log::info('New address created', [
                'user_id' => $user->id,
                'address_id' => $address->id,
                'label' => $address->label,
                'recipient_name' => $address->recipient_name,
                'is_primary' => $address->is_primary,
                'location' => "{$address->city_name}, {$address->province_name}"
            ]);

            return redirect()->route('profile.addresses.index')
                           ->with('success', 'Address added successfully!');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                           ->withErrors($e->validator)
                           ->withInput();
        } catch (\Exception $e) {
            Log::error('Error creating address: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request_data' => $request->except(['_token']),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                           ->withErrors(['error' => 'Failed to add address: ' . $e->getMessage()])
                           ->withInput();
        }
    }

    /**
     * Show form to edit address
     */
    public function edit($id)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to edit address.');
        }

        try {
            $user = Auth::user();
            $address = UserAddress::where('user_id', $user->id)
                          ->where('is_active', true)
                          ->findOrFail($id);

            return view('frontend.profile.addresses.edit', compact('address'));

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('Address not found for edit', [
                'user_id' => Auth::id(),
                'address_id' => $id
            ]);

            return redirect()->route('profile.addresses.index')
                           ->with('error', 'Address not found or has been deleted.');
        } catch (\Exception $e) {
            Log::error('Error loading address for edit: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'address_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('profile.addresses.index')
                           ->with('error', 'Failed to load address for editing.');
        }
    }

    /**
     * Update address
     */
    public function update(Request $request, $id)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to update address.');
        }

        try {
            $validated = $request->validate([
                'label' => 'required|string|in:Kantor,Rumah',
                'recipient_name' => 'required|string|max:255',
                'phone_recipient' => 'required|string|max:20|regex:/^[0-9+\-\s\(\)]{10,}$/',
                'province_name' => 'required|string|max:100',
                'city_name' => 'required|string|max:100',
                'subdistrict_name' => 'required|string|max:100',
                'postal_code' => 'required|string|size:5|regex:/^[0-9]{5}$/',
                'destination_id' => 'nullable|string|max:50',
                'street_address' => 'required|string|min:10|max:500',
                'notes' => 'nullable|string|max:500',
                'is_primary' => 'nullable|boolean'
            ], [
                'recipient_name.required' => 'Recipient name is required',
                'phone_recipient.required' => 'Recipient phone number is required',
                'phone_recipient.regex' => 'Please enter a valid phone number (minimum 10 digits)',
                'province_name.required' => 'Please select a location',
                'city_name.required' => 'Please select a location',
                'subdistrict_name.required' => 'Please select a location',
                'postal_code.required' => 'Postal code is required',
                'postal_code.size' => 'Postal code must be exactly 5 digits',
                'postal_code.regex' => 'Postal code must contain only numbers',
                'street_address.required' => 'Street address is required',
                'street_address.min' => 'Street address must be at least 10 characters long'
            ]);

            $user = Auth::user();
            $address = UserAddress::where('user_id', $user->id)
                          ->where('is_active', true)
                          ->findOrFail($id);

            // Use database transaction for data consistency
            DB::transaction(function () use ($address, $validated) {
                // Update address fields
                $address->update([
                    'label' => $validated['label'],
                    'recipient_name' => trim($validated['recipient_name']),
                    'phone_recipient' => preg_replace('/[^0-9+\-\s\(\)]/', '', $validated['phone_recipient']),
                    'province_name' => $validated['province_name'],
                    'city_name' => $validated['city_name'],
                    'subdistrict_name' => $validated['subdistrict_name'],
                    'postal_code' => $validated['postal_code'],
                    'destination_id' => $validated['destination_id'] ?? null,
                    'street_address' => trim($validated['street_address']),
                    'notes' => !empty($validated['notes']) ? trim($validated['notes']) : null,
                ]);

                // Set as primary if requested
                if ($validated['is_primary'] ?? false) {
                    $address->setPrimary();
                }
            });

            Log::info('Address updated', [
                'user_id' => $user->id,
                'address_id' => $address->id,
                'updated_fields' => array_keys($validated),
                'is_primary' => $address->is_primary
            ]);

            return redirect()->route('profile.addresses.index')
                           ->with('success', 'Address updated successfully!');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                           ->withErrors($e->validator)
                           ->withInput();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('Address not found for update', [
                'user_id' => Auth::id(),
                'address_id' => $id
            ]);

            return redirect()->route('profile.addresses.index')
                           ->with('error', 'Address not found or has been deleted.');
        } catch (\Exception $e) {
            Log::error('Error updating address: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'address_id' => $id,
                'request_data' => $request->except(['_token']),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                           ->withErrors(['error' => 'Failed to update address: ' . $e->getMessage()])
                           ->withInput();
        }
    }

    /**
     * Set address as primary
     */
    public function setPrimary($id)
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Authentication required'], 401);
        }

        try {
            $user = Auth::user();
            $address = UserAddress::where('user_id', $user->id)
                          ->where('is_active', true)
                          ->findOrFail($id);
            
            $address->setPrimary();

            Log::info('Address set as primary', [
                'user_id' => $user->id,
                'address_id' => $address->id,
                'label' => $address->label
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Primary address updated successfully!',
                    'address' => [
                        'id' => $address->id,
                        'label' => $address->label,
                        'is_primary' => $address->is_primary
                    ]
                ]);
            }

            return redirect()->route('profile.addresses.index')
                           ->with('success', 'Primary address updated successfully!');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('Address not found for set primary', [
                'user_id' => Auth::id(),
                'address_id' => $id
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Address not found'
                ], 404);
            }

            return redirect()->route('profile.addresses.index')
                           ->with('error', 'Address not found.');
        } catch (\Exception $e) {
            Log::error('Error setting primary address: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'address_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update primary address'
                ], 500);
            }

            return redirect()->route('profile.addresses.index')
                           ->with('error', 'Failed to update primary address.');
        }
    }

    /**
     * Soft delete address
     */
    public function destroy($id)
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Authentication required'], 401);
        }

        try {
            $user = Auth::user();
            $address = UserAddress::where('user_id', $user->id)
                          ->where('is_active', true)
                          ->findOrFail($id);

            // Use the model's soft delete method which handles primary address logic
            if (!$address->softDelete()) {
                $errorMessage = 'Cannot delete primary address. Please set another address as primary first.';
                
                if (request()->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMessage
                    ], 400);
                }

                return redirect()->route('profile.addresses.index')
                               ->with('error', $errorMessage);
            }

            $label = $address->label;

            Log::info('Address soft deleted', [
                'user_id' => $user->id,
                'address_id' => $id,
                'label' => $label
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Address deleted successfully!'
                ]);
            }

            return redirect()->route('profile.addresses.index')
                           ->with('success', 'Address deleted successfully!');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('Address not found for deletion', [
                'user_id' => Auth::id(),
                'address_id' => $id
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Address not found'
                ], 404);
            }

            return redirect()->route('profile.addresses.index')
                           ->with('error', 'Address not found.');
        } catch (\Exception $e) {
            Log::error('Error deleting address: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'address_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete address'
                ], 500);
            }

            return redirect()->route('profile.addresses.index')
                           ->with('error', 'Failed to delete address.');
        }
    }

    /**
     * Get address data for API/AJAX requests
     */
    public function show($id)
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Authentication required'], 401);
        }

        try {
            $user = Auth::user();
            $address = UserAddress::where('user_id', $user->id)
                          ->where('is_active', true)
                          ->findOrFail($id);

            return response()->json([
                'success' => true,
                'address' => [
                    'id' => $address->id,
                    'label' => $address->label,
                    'recipient_name' => $address->recipient_name,
                    'phone_recipient' => $address->phone_recipient,
                    'province_name' => $address->province_name,
                    'city_name' => $address->city_name,
                    'subdistrict_name' => $address->subdistrict_name,
                    'postal_code' => $address->postal_code,
                    'destination_id' => $address->destination_id,
                    'street_address' => $address->street_address,
                    'notes' => $address->notes,
                    'is_primary' => $address->is_primary,
                    'full_address' => $address->full_address,
                    'location_string' => $address->location_string,
                    'recipient_info' => $address->recipient_info
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error getting address data: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'address_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get address data'
            ], 500);
        }
    }

    /**
     * Get all user addresses for API/AJAX requests
     */
    public function getAddresses()
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Authentication required'], 401);
        }

        try {
            $user = Auth::user();
            // Query langsung - konsisten dengan pattern controller bersih
            $addresses = UserAddress::where('user_id', $user->id)
                            ->where('is_active', true)
                            ->orderBy('is_primary', 'desc')
                            ->orderBy('created_at', 'desc')
                            ->get();

            return response()->json([
                'success' => true,
                'addresses' => $addresses->map(function($address) {
                    return [
                        'id' => $address->id,
                        'label' => $address->label,
                        'recipient_name' => $address->recipient_name,
                        'phone_recipient' => $address->phone_recipient,
                        'province_name' => $address->province_name,
                        'city_name' => $address->city_name,
                        'subdistrict_name' => $address->subdistrict_name,
                        'postal_code' => $address->postal_code,
                        'destination_id' => $address->destination_id,
                        'street_address' => $address->street_address,
                        'notes' => $address->notes,
                        'is_primary' => $address->is_primary,
                        'full_address' => $address->full_address,
                        'location_string' => $address->location_string,
                        'recipient_info' => $address->recipient_info
                    ];
                })
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting addresses: ' . $e->getMessage(), [
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get addresses'
            ], 500);
        }
    }

    /**
     * Get primary address for API/AJAX requests
     */
    public function getPrimaryAddress()
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Authentication required'], 401);
        }

        try {
            $user = Auth::user();
            // Query langsung - konsisten dengan pattern controller bersih
            $primaryAddress = UserAddress::where('user_id', $user->id)
                                 ->where('is_active', true)
                                 ->where('is_primary', true)
                                 ->first();

            if (!$primaryAddress) {
                return response()->json([
                    'success' => false,
                    'message' => 'No primary address found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'address' => [
                    'id' => $primaryAddress->id,
                    'label' => $primaryAddress->label,
                    'recipient_name' => $primaryAddress->recipient_name,
                    'phone_recipient' => $primaryAddress->phone_recipient,
                    'province_name' => $primaryAddress->province_name,
                    'city_name' => $primaryAddress->city_name,
                    'subdistrict_name' => $primaryAddress->subdistrict_name,
                    'postal_code' => $primaryAddress->postal_code,
                    'destination_id' => $primaryAddress->destination_id,
                    'street_address' => $primaryAddress->street_address,
                    'notes' => $primaryAddress->notes,
                    'is_primary' => $primaryAddress->is_primary,
                    'full_address' => $primaryAddress->full_address,
                    'location_string' => $primaryAddress->location_string,
                    'recipient_info' => $primaryAddress->recipient_info
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting primary address: ' . $e->getMessage(), [
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get primary address'
            ], 500);
        }
    }
}