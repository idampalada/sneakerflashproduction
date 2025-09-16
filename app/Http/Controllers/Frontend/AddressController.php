<?php

// File: app/Http/Controllers/Frontend/AddressController.php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\RajaOngkirService;
use Illuminate\Support\Facades\Validator;

class AddressController extends Controller
{
    protected $rajaOngkirService;
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
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => 'Authentication required'], 401);
        }
        return redirect()->route('login')->with('error', 'Please login to add address.');
    }

    try {
        // Validation sesuai dengan struktur database yang sudah dibersihkan
        $validated = $request->validate([
            'label' => 'required|in:Kantor,Rumah',
            'recipient_name' => 'required|string|max:255',
            'phone_recipient' => 'required|string|max:20|regex:/^[0-9+\-\s\(\)]{10,}$/',
            
            // Hierarchical ID fields (dari dropdown frontend)
            'province_id' => 'required|integer|min:1',
            'city_id' => 'required|integer|min:1', 
            'district_id' => 'required|integer|min:1',
            'sub_district_id' => 'required|integer|min:1',
            
            // Hierarchical name fields (auto-filled oleh JavaScript)
            'province_name' => 'required|string|max:255',
            'city_name' => 'required|string|max:255',
            'district_name' => 'required|string|max:255',
            'sub_district_name' => 'required|string|max:255',
            
            'postal_code' => 'nullable|string|max:10',
            'destination_id' => 'nullable|string|max:50',
            'street_address' => 'required|string|max:1000',
            'notes' => 'nullable|string|max:500',
            'is_primary' => 'nullable|boolean'
        ]);

        DB::transaction(function() use ($validated) {
            // Set primary address logic
            if ($validated['is_primary'] ?? false) {
                UserAddress::where('user_id', Auth::id())
                          ->update(['is_primary' => false]);
            }

            // Create new address dengan field yang sesuai struktur database
            UserAddress::create([
                'user_id' => Auth::id(),
                'label' => $validated['label'],
                'recipient_name' => $validated['recipient_name'],
                'phone_recipient' => $validated['phone_recipient'],
                
                // Hierarchical IDs
                'province_id' => $validated['province_id'],
                'city_id' => $validated['city_id'],
                'district_id' => $validated['district_id'],
                'sub_district_id' => $validated['sub_district_id'],
                
                // Hierarchical names
                'province_name' => $validated['province_name'],
                'city_name' => $validated['city_name'],
                'district_name' => $validated['district_name'],
                'sub_district_name' => $validated['sub_district_name'],
                
                // Optional fields with fallback
                'postal_code' => $validated['postal_code'] ?? null,
                'destination_id' => $validated['destination_id'] ?? null,
                'street_address' => $validated['street_address'],
                'notes' => $validated['notes'] ?? null,
                'is_primary' => $validated['is_primary'] ?? false,
                'is_active' => true,
                
                // Keep search_location for backward compatibility
                'search_location' => $validated['city_name'] . ', ' . $validated['province_name']
            ]);
        });

        // Handle different request types
        if ($request->expectsJson()) {
            // For AJAX requests
            return response()->json([
                'success' => true,
                'message' => 'Address saved successfully!'
            ]);
        } else {
            // For form submissions - redirect to addresses index
            return redirect()->route('profile.addresses.index')
                           ->with('success', 'Address saved successfully!');
        }

    } catch (\Illuminate\Validation\ValidationException $e) {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } else {
            return redirect()->back()
                           ->withErrors($e->validator)
                           ->withInput();
        }
    } catch (\Exception $e) {
        Log::error('Error saving address: ' . $e->getMessage(), [
            'user_id' => Auth::id(),
            'request_data' => $request->except(['_token']),
            'trace' => $e->getTraceAsString()
        ]);
        
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save address'
            ], 500);
        } else {
            return redirect()->back()
                           ->withErrors(['error' => 'Failed to save address. Please try again.'])
                           ->withInput();
        }
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
        Log::warning('Address not found for editing', [
            'user_id' => Auth::id(),
            'address_id' => $id
        ]);

        return redirect()->route('profile.addresses.index')
                       ->with('error', 'Address not found or has been deleted.');
    } catch (\Exception $e) {
        Log::error('Error loading address for editing: ' . $e->getMessage(), [
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
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => 'Authentication required'], 401);
        }
        return redirect()->route('login')->with('error', 'Please login to update address.');
    }

    try {
        $validated = $request->validate([
            'label' => 'required|string|in:Kantor,Rumah',
            'recipient_name' => 'required|string|max:255',
            'phone_recipient' => 'required|string|max:20|regex:/^[0-9+\-\s\(\)]{10,}$/',
            
            // Hierarchical ID fields (dari dropdown frontend)
            'province_id' => 'required|integer|min:1',
            'city_id' => 'required|integer|min:1', 
            'district_id' => 'required|integer|min:1',
            'sub_district_id' => 'required|integer|min:1',
            
            // Hierarchical name fields (auto-filled oleh JavaScript)
            'province_name' => 'required|string|max:255',
            'city_name' => 'required|string|max:255',
            'district_name' => 'required|string|max:255',
            'sub_district_name' => 'required|string|max:255',
            
            'postal_code' => 'nullable|string|max:10',
            'destination_id' => 'nullable|string|max:50',
            'street_address' => 'required|string|max:1000',
            'notes' => 'nullable|string|max:500',
            'is_primary' => 'nullable|boolean'
        ], [
            'recipient_name.required' => 'Recipient name is required',
            'phone_recipient.required' => 'Recipient phone number is required',
            'phone_recipient.regex' => 'Please enter a valid phone number (minimum 10 digits)',
            'province_name.required' => 'Please select a location',
            'city_name.required' => 'Please select a location',
            'district_name.required' => 'Please select a location',
            'sub_district_name.required' => 'Please select a location',
            'street_address.required' => 'Street address is required'
        ]);

        $user = Auth::user();
        $address = UserAddress::where('user_id', $user->id)
                      ->where('is_active', true)
                      ->findOrFail($id);

        // Use database transaction for data consistency
        DB::transaction(function () use ($address, $validated) {
            // Handle primary address logic
            if ($validated['is_primary'] ?? false) {
                // Remove primary from other addresses first
                UserAddress::where('user_id', $address->user_id)
                           ->where('id', '!=', $address->id)
                           ->update(['is_primary' => false]);
            }

            // Update address fields
            $address->update([
                'label' => $validated['label'],
                'recipient_name' => trim($validated['recipient_name']),
                'phone_recipient' => $validated['phone_recipient'],
                
                // Hierarchical IDs
                'province_id' => $validated['province_id'],
                'city_id' => $validated['city_id'],
                'district_id' => $validated['district_id'],
                'sub_district_id' => $validated['sub_district_id'],
                
                // Hierarchical names
                'province_name' => $validated['province_name'],
                'city_name' => $validated['city_name'],
                'district_name' => $validated['district_name'],
                'sub_district_name' => $validated['sub_district_name'],
                
                'postal_code' => $validated['postal_code'] ?? null,
                'destination_id' => $validated['destination_id'] ?? null,
                'street_address' => $validated['street_address'],
                'notes' => $validated['notes'] ?? null,
                'is_primary' => $validated['is_primary'] ?? false,
                
                // Update search_location for backward compatibility
                'search_location' => $validated['city_name'] . ', ' . $validated['province_name']
            ]);
        });

        Log::info('Address updated', [
            'user_id' => $user->id,
            'address_id' => $address->id,
            'updated_fields' => array_keys($validated),
            'is_primary' => $address->is_primary
        ]);

        // Handle different request types
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Address updated successfully!'
            ]);
        } else {
            return redirect()->route('profile.addresses.index')
                           ->with('success', 'Address updated successfully!');
        }

    } catch (\Illuminate\Validation\ValidationException $e) {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } else {
            return redirect()->back()
                           ->withErrors($e->validator)
                           ->withInput();
        }
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        Log::warning('Address not found for update', [
            'user_id' => Auth::id(),
            'address_id' => $id
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found'
            ], 404);
        } else {
            return redirect()->route('profile.addresses.index')
                           ->with('error', 'Address not found or has been deleted.');
        }
    } catch (\Exception $e) {
        Log::error('Error updating address: ' . $e->getMessage(), [
            'user_id' => Auth::id(),
            'address_id' => $id,
            'request_data' => $request->except(['_token']),
            'trace' => $e->getTraceAsString()
        ]);
        
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update address'
            ], 500);
        } else {
            return redirect()->back()
                           ->withErrors(['error' => 'Failed to update address. Please try again.'])
                           ->withInput();
        }
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
        if (request()->expectsJson()) {
            return response()->json(['success' => false, 'message' => 'Authentication required'], 401);
        }
        return redirect()->route('login')->with('error', 'Please login to delete address.');
    }

    try {
        $user = Auth::user();
        $address = UserAddress::where('user_id', $user->id)
                      ->where('is_active', true)
                      ->findOrFail($id);

        // Check if this is the only address
        $addressCount = UserAddress::where('user_id', $user->id)
                           ->where('is_active', true)
                           ->count();

        if ($addressCount === 1) {
            $errorMessage = 'Cannot delete the only address. Please add another address first.';
            
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 400);
            }

            return redirect()->route('profile.addresses.index')
                           ->with('error', $errorMessage);
        }

        // Check if this is primary address and there are other addresses
        if ($address->is_primary && $addressCount > 1) {
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

        // Soft delete by setting is_active = false
        $address->update(['is_active' => false]);

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
    $address = UserAddress::where('id', $id)
                         ->where('user_id', Auth::id())
                         ->first();
    
    if (!$address) {
        return response()->json([
            'success' => false,
            'message' => 'Address not found'
        ], 404);
    }
    
    // Pastikan semua field ada
    $addressData = [
        'id' => $address->id,
        'label' => $address->label,
        'recipient_name' => $address->recipient_name,
        'phone_recipient' => $address->phone_recipient,
        'street_address' => $address->street_address,
        
        // Hierarchical IDs (CRITICAL)
        'province_id' => $address->province_id,
        'city_id' => $address->city_id,
        'district_id' => $address->district_id,
        'sub_district_id' => $address->sub_district_id,
        
        // Names
        'province_name' => $address->province_name,
        'city_name' => $address->city_name,
        'district_name' => $address->district_name,
        'sub_district_name' => $address->sub_district_name,
        
        // Other
        'postal_code' => $address->postal_code,
        'destination_id' => $address->destination_id,
        'full_address' => $address->full_address,
        'location_string' => $address->location_string
    ];
    
    return response()->json([
        'success' => true,
        'address' => $addressData
    ]);
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
    Public function __construct(RajaOngkirService $rajaOngkirService)
    {
        $this->rajaOngkirService = $rajaOngkirService;
    }

    // Tambahkan method-method baru ini:

    public function getProvinces()
{
    try {
        $provinces = $this->rajaOngkirService->getProvincesHierarchical();
        return response()->json([
            'success' => true,
            'data' => $provinces
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to get provinces'
        ], 500);
    }
}

// Tambahkan logging di method getCitiesByProvince di AddressController
public function getCitiesByProvince($provinceId)
{
    try {
        Log::info('🎯 Controller getCitiesByProvince called', ['province_id' => $provinceId]);
        
        $cities = $this->rajaOngkirService->getCitiesByProvinceId($provinceId);
        
        Log::info('🎯 Controller cities result', [
            'count' => count($cities),
            'sample' => array_slice($cities, 0, 2)
        ]);
        
        return response()->json([
            'success' => true,
            'data' => $cities,
            'total' => count($cities)
        ]);
    } catch (\Exception $e) {
        Log::error('🎯 Controller error', ['error' => $e->getMessage()]);
        return response()->json([
            'success' => false,
            'message' => 'Failed to get cities: ' . $e->getMessage()
        ], 500);
    }
}

public function getDistrictsByCity($cityId)
{
    try {
        $districts = $this->rajaOngkirService->getDistrictsByCityId($cityId);
        return response()->json([
            'success' => true,
            'data' => $districts
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to get districts'
        ], 500);
    }
}

public function getSubDistrictsByDistrict($districtId)
{
    try {
        $subDistricts = $this->rajaOngkirService->getSubDistrictsByDistrictId($districtId);
        return response()->json([
            'success' => true,
            'data' => $subDistricts
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to get sub-districts'
        ], 500);
    }
}
    /**
     * Search locations (legacy method)
     */
    public function searchLocations(Request $request)
    {
        try {
            $searchTerm = $request->input('q');
            $limit = $request->input('limit', 10);

            $results = $this->rajaOngkirService->searchDestinations($searchTerm, $limit);

            return response()->json([
                'success' => true,
                'data' => $results,
                'total' => count($results)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test all hierarchical endpoints
     */
    public function testEndpoints()
    {
        try {
            $testResults = $this->rajaOngkirService->testHierarchicalEndpoints();

            return response()->json([
                'success' => $testResults['success'],
                'message' => $testResults['message'],
                'data' => $testResults['results']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Test failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get complete hierarchy for form initialization
     */
    public function getCompleteHierarchy(Request $request)
    {
        try {
            $result = ['provinces' => $this->rajaOngkirService->getProvinces()];

            if ($request->has('province_id')) {
                $result['cities'] = $this->rajaOngkirService->getCitiesByProvince($request->province_id);
            }

            if ($request->has('city_id')) {
                $result['districts'] = $this->rajaOngkirService->getDistrictsByCity($request->city_id);
            }

            if ($request->has('district_id')) {
                $result['sub_districts'] = $this->rajaOngkirService->getSubDistrictsByDistrict($request->district_id);
            }

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get hierarchy',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
