<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    /**
     * Display the user's profile.
     */
    public function index()
    {
        // Manual auth check like your OrderController
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to access your profile.');
        }

        try {
            $user = Auth::user();
            
            // Auto-calculate zodiac if birthdate exists but zodiac is null
            if ($user->birthdate && !$user->zodiac) {
                $zodiac = $this->calculateZodiac($user->birthdate);
                if ($zodiac) {
                    $user->zodiac = $zodiac;
                    $user->save();
                }
            }
            
            // Check if profile is locked (completed) - using existing fields only
            $isProfileLocked = $this->isProfileLocked($user);
            
            // Get user statistics - use existing total_orders and total_spent columns if available
            $totalOrders = $user->total_orders ?? Order::where('user_id', $user->id)->count();
            $totalSpent = $user->total_spent ?? Order::where('user_id', $user->id)
                              ->whereIn('status', ['paid', 'processing', 'shipped', 'delivered'])
                              ->sum('total_amount');
            
            // Get recent orders for display
            $recentOrders = Order::with(['orderItems.product'])
                               ->where('user_id', $user->id)
                               ->orderBy('created_at', 'desc')
                               ->limit(5)
                               ->get();

            // Check which fields are locked (have data and cannot be edited)
            $lockedFields = [
                'name' => $this->isFieldLocked($user, 'name'),
                'email' => $this->isFieldLocked($user, 'email'),
                'phone' => $this->isFieldLocked($user, 'phone'),
                'gender' => $this->isFieldLocked($user, 'gender'),
                'birthdate' => $this->isFieldLocked($user, 'birthdate'),
            ];

            // Get zodiac information if user has zodiac
            $zodiacInfo = null;
            if ($user->zodiac) {
                $zodiacInfo = $this->getZodiacInfo($user->zodiac);
            }

            // Calculate profile completion
            $profileCompletion = $this->calculateProfileCompletion($user);

            Log::info('Profile page accessed', [
                'user_id' => $user->id,
                'total_orders' => $totalOrders,
                'total_spent' => $totalSpent,
                'profile_locked' => $isProfileLocked,
                'locked_fields' => $lockedFields,
                'zodiac' => $user->zodiac,
                'profile_completion' => $profileCompletion
            ]);

            return view('frontend.profile.index', compact(
                'user', 
                'totalOrders', 
                'totalSpent', 
                'recentOrders',
                'isProfileLocked',
                'lockedFields',
                'zodiacInfo',
                'profileCompletion'
            ));

        } catch (\Exception $e) {
            Log::error('Error loading profile page: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('home')->with('error', 'Failed to load profile. Please try again.');
        }
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request)
    {
        // Manual auth check
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to update profile.');
        }

        try {
            $user = Auth::user();
            
            // Check which fields are locked (cannot be changed once filled)
            if ($this->isFieldLocked($user, 'name') && $request->filled('name') && $request->name !== $user->name) {
                return redirect()->back()->with('error', 'Name cannot be changed once set.');
            }
            
            if ($this->isFieldLocked($user, 'email') && $request->filled('email') && $request->email !== $user->email) {
                return redirect()->back()->with('error', 'Email cannot be changed once set.');
            }
            
            if ($this->isFieldLocked($user, 'phone') && $request->filled('phone') && $request->phone !== $user->phone) {
                return redirect()->back()->with('error', 'Phone number cannot be changed once set.');
            }
            
            // Validation rules - only validate fields that can be updated
            $rules = [];
            
            // Only validate name if it's not locked or if it's the first time setting it
            if (!$this->isFieldLocked($user, 'name')) {
                $rules['name'] = 'required|string|max:255';
            }
            
            // Only validate email if it's not locked or if it's the first time setting it
            if (!$this->isFieldLocked($user, 'email')) {
                $rules['email'] = ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)];
            }
            
            // Only validate phone if it's not locked or if it's the first time setting it
            if (!$this->isFieldLocked($user, 'phone') && $request->has('phone')) {
                $rules['phone'] = 'required|string|max:20';
            }

            // Gender and birthdate can always be updated (optional fields)
            if ($request->has('gender')) {
                $rules['gender'] = 'nullable|in:mens,womens,kids';
            }

            if ($request->has('birthdate')) {
                $rules['birthdate'] = 'nullable|date|before:today';
            }

            $validated = $request->validate($rules);

            // Update fields - only update non-locked fields
            $updateData = [];
            
            // Update name only if not locked and provided
            if (!$this->isFieldLocked($user, 'name') && array_key_exists('name', $validated)) {
                $updateData['name'] = $validated['name'];
            }
            
            // Update email only if not locked and provided
            if (!$this->isFieldLocked($user, 'email') && array_key_exists('email', $validated)) {
                $updateData['email'] = $validated['email'];
            }
            
            // Update phone only if not locked and provided
            if (!$this->isFieldLocked($user, 'phone') && array_key_exists('phone', $validated)) {
                $updateData['phone'] = $validated['phone'];
            }
            
            // Gender and birthdate can always be updated
            if (array_key_exists('gender', $validated)) {
                $updateData['gender'] = $validated['gender'];
            }
            
            if (array_key_exists('birthdate', $validated)) {
                $updateData['birthdate'] = $validated['birthdate'];
                
                // Auto-calculate zodiac if birthdate is provided
                if ($validated['birthdate']) {
                    $updateData['zodiac'] = $this->calculateZodiac($validated['birthdate']);
                } else {
                    $updateData['zodiac'] = null;
                }
            }

            // Update user fields
            foreach ($updateData as $field => $value) {
                $user->$field = $value;
            }
            
            $saved = $user->save();

            if ($saved) {
                // Check if profile is now complete after update
                $isNowComplete = $this->calculateProfileCompletion($user) >= 100;
                
                Log::info('Profile updated successfully', [
                    'user_id' => $user->id,
                    'updated_fields' => array_keys($updateData),
                    'profile_completion' => $this->calculateProfileCompletion($user),
                    'is_complete' => $isNowComplete,
                    'zodiac' => $user->zodiac
                ]);

                $message = $isNowComplete ? 
                    'Profile completed successfully! All required information has been filled.' :
                    'Profile updated successfully!';

                return redirect()->route('profile.index')
                               ->with('success', $message);
            }

            throw new \Exception('Save operation returned false');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                           ->withErrors($e->validator)
                           ->withInput();
        } catch (\Exception $e) {
            Log::error('Error updating profile: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request_data' => $request->except(['_token']),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                           ->withErrors(['error' => 'Failed to update profile: ' . $e->getMessage()])
                           ->withInput();
        }
    }

    /**
     * Update password
     */
    public function updatePassword(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to change password.');
        }

        try {
            $user = Auth::user();

            $validated = $request->validate([
                'current_password' => 'required',
                'password' => 'required|min:8|confirmed',
            ]);

            if (!Hash::check($validated['current_password'], $user->password)) {
                Log::warning('Incorrect current password attempt', [
                    'user_id' => $user->id
                ]);

                return redirect()->back()
                               ->withErrors(['current_password' => 'Current password is incorrect.']);
            }

            $user->password = Hash::make($validated['password']);
            $saved = $user->save();

            if ($saved) {
                Log::info('Password updated successfully', [
                    'user_id' => $user->id
                ]);

                return redirect()->route('profile.index')
                               ->with('success', 'Password updated successfully!');
            }

            throw new \Exception('Password save operation failed');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                           ->withErrors($e->validator)
                           ->withInput();
        } catch (\Exception $e) {
            Log::error('Error updating password: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                           ->withErrors(['error' => 'Failed to update password: ' . $e->getMessage()]);
        }
    }

    /**
     * Get user profile data for checkout auto-fill (API endpoint).
     */
    public function getProfileData()
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'error' => 'Authentication required.'
            ], 401);
        }

        try {
            $user = Auth::user();
            
            // Auto-calculate zodiac if missing
            if ($user->birthdate && !$user->zodiac) {
                $zodiac = $this->calculateZodiac($user->birthdate);
                if ($zodiac) {
                    $user->zodiac = $zodiac;
                    $user->save();
                }
            }
            
            $data = [
                'name' => $user->name ?? '',
                'email' => $user->email ?? '',
                'phone' => $user->phone ?? '',
                'gender' => $user->gender ?? '',
                'birthdate' => $user->birthdate ? $user->birthdate->format('Y-m-d') : '',
                'zodiac' => $user->zodiac ?? '',
                'zodiac_info' => $user->zodiac ? $this->getZodiacInfo($user->zodiac) : null,
                'profile_completion_percentage' => $this->calculateProfileCompletion($user),
                'is_profile_complete' => $this->calculateProfileCompletion($user) >= 100,
            ];

            Log::info('Profile data accessed via API', [
                'user_id' => $user->id,
                'completion_percentage' => $data['profile_completion_percentage'],
                'zodiac' => $user->zodiac
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting profile data: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get profile data.'
            ], 500);
        }
    }

    /**
     * API endpoint to get zodiac information
     */
    public function getZodiacData($zodiacName)
    {
        try {
            $zodiacInfo = $this->getZodiacInfo(strtoupper($zodiacName));
            
            if ($zodiacInfo) {
                return response()->json([
                    'success' => true,
                    'data' => $zodiacInfo
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Zodiac not found'
            ], 404);
            
        } catch (\Exception $e) {
            Log::error('Error getting zodiac data: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get zodiac data'
            ], 500);
        }
    }

    /**
     * Calculate zodiac sign from birthdate
     */
    private function calculateZodiac($birthdate)
{
    if (!$birthdate) return null;
    
    // Convert to Carbon if it's a string
    if (is_string($birthdate)) {
        $birthdate = \Carbon\Carbon::parse($birthdate);
    }
    
    $month = $birthdate->month;
    $day = $birthdate->day;
    $monthDay = $month * 100 + $day;
    
    if (($monthDay >= 1222) || ($monthDay <= 119)) return 'CAPRICORN';
    if ($monthDay >= 120 && $monthDay <= 218) return 'AQUARIUS';
    if ($monthDay >= 219 && $monthDay <= 320) return 'PISCES';
    if ($monthDay >= 321 && $monthDay <= 419) return 'ARIES';
    if ($monthDay >= 420 && $monthDay <= 520) return 'TAURUS';
    if ($monthDay >= 521 && $monthDay <= 620) return 'GEMINI';
    if ($monthDay >= 621 && $monthDay <= 722) return 'CANCER';
    if ($monthDay >= 723 && $monthDay <= 822) return 'LEO';
    if ($monthDay >= 823 && $monthDay <= 922) return 'VIRGO';
    if ($monthDay >= 923 && $monthDay <= 1022) return 'LIBRA';
    if ($monthDay >= 1023 && $monthDay <= 1121) return 'SCORPIO';
    if ($monthDay >= 1122 && $monthDay <= 1221) return 'SAGITARIUS';
    
    return null;
}

    /**
     * Get zodiac information and details
     */
    private function getZodiacInfo($zodiac)
{
    $zodiacData = [
        'PISCES' => [
            'name' => 'PISCES',
            'dates' => '19 FEB - 20 MAR',
            'description' => 'Sensitif, intuitif, dan kreatif. Memiliki empati yang tinggi.',
            'element' => 'Air',
            'ruling_planet' => 'Neptunus',
            'symbol' => '♓',
            'traits' => ['Kreatif', 'Empati tinggi', 'Intuitif', 'Sensitif']
        ],
        'ARIES' => [
            'name' => 'ARIES',
            'dates' => '21 MAR - 19 APR',
            'description' => 'Energik, berani, dan memiliki jiwa kepemimpinan yang kuat.',
            'element' => 'Api',
            'ruling_planet' => 'Mars',
            'symbol' => '♈',
            'traits' => ['Energik', 'Berani', 'Pemimpin', 'Spontan']
        ],
        'TAURUS' => [
            'name' => 'TAURUS',
            'dates' => '20 APR - 20 MEI',
            'description' => 'Praktis, stabil, dan menyukai kenyamanan serta keindahan.',
            'element' => 'Tanah',
            'ruling_planet' => 'Venus',
            'symbol' => '♉',
            'traits' => ['Praktis', 'Stabil', 'Loyal', 'Menyukai keindahan']
        ],
        'GEMINI' => [
            'name' => 'GEMINI',
            'dates' => '21 MEI - 20 JUNI',
            'description' => 'Komunikatif, adaptif, dan memiliki rasa ingin tahu yang tinggi.',
            'element' => 'Udara',
            'ruling_planet' => 'Merkurius',
            'symbol' => '♊',
            'traits' => ['Komunikatif', 'Adaptif', 'Intelektual', 'Fleksibel']
        ],
        'CANCER' => [
            'name' => 'CANCER',
            'dates' => '21 JUN - 22 JUL',
            'description' => 'Penyayang, protektif, dan sangat menghargai keluarga.',
            'element' => 'Air',
            'ruling_planet' => 'Bulan',
            'symbol' => '♋',
            'traits' => ['Penyayang', 'Protektif', 'Intuitif', 'Emosional']
        ],
        'LEO' => [
            'name' => 'LEO',
            'dates' => '23 JULI - 22 AGUS',
            'description' => 'Percaya diri, murah hati, dan memiliki karisma yang kuat.',
            'element' => 'Api',
            'ruling_planet' => 'Matahari',
            'symbol' => '♌',
            'traits' => ['Percaya diri', 'Karismatik', 'Murah hati', 'Dramatis']
        ],
        'VIRGO' => [
            'name' => 'VIRGO',
            'dates' => '23 AGUS - 22 SEP',
            'description' => 'Analitis, perfeksionis, dan sangat memperhatikan detail.',
            'element' => 'Tanah',
            'ruling_planet' => 'Merkurius',
            'symbol' => '♍',
            'traits' => ['Analitis', 'Perfeksionis', 'Praktis', 'Detail-oriented']
        ],
        'LIBRA' => [
            'name' => 'LIBRA',
            'dates' => '23 SEP - 22 OKT',
            'description' => 'Diplomatik, harmonis, dan menghargai keseimbangan dalam hidup.',
            'element' => 'Udara',
            'ruling_planet' => 'Venus',
            'symbol' => '♎',
            'traits' => ['Diplomatik', 'Harmonis', 'Adil', 'Sosial']
        ],
        'SCORPIO' => [
            'name' => 'SCORPIO',
            'dates' => '23 OKT - 21 NOV',
            'description' => 'Intens, misterius, dan memiliki intuisi yang tajam.',
            'element' => 'Air',
            'ruling_planet' => 'Pluto',
            'symbol' => '♏',
            'traits' => ['Intens', 'Misterius', 'Loyal', 'Transformatif']
        ],
        'SAGITARIUS' => [
            'name' => 'SAGITARIUS',
            'dates' => '22 NOV - 21 DES',
            'description' => 'Petualang, optimis, dan menyukai kebebasan.',
            'element' => 'Api',
            'ruling_planet' => 'Jupiter',
            'symbol' => '♐',
            'traits' => ['Petualang', 'Optimis', 'Filosofis', 'Bebas']
        ],
        'CAPRICORN' => [
            'name' => 'CAPRICORN',
            'dates' => '22 DES - 19 JAN',
            'description' => 'Ambisius, disiplin, dan memiliki tanggung jawab yang tinggi.',
            'element' => 'Tanah',
            'ruling_planet' => 'Saturnus',
            'symbol' => '♑',
            'traits' => ['Ambisius', 'Disiplin', 'Bertanggung jawab', 'Praktis']
        ],
        'AQUARIUS' => [
            'name' => 'AQUARIUS',
            'dates' => '20 JAN - 18 FEB',
            'description' => 'Inovatif, independen, dan memiliki visi yang unik.',
            'element' => 'Udara',
            'ruling_planet' => 'Uranus',
            'symbol' => '♒',
            'traits' => ['Inovatif', 'Independen', 'Humanis', 'Progresif']
        ]
    ];
    
    return $zodiacData[$zodiac] ?? null;
}

    /**
     * Check if a specific field is locked (cannot be edited)
     */
    private function isFieldLocked($user, $field)
    {
        // Fields are locked if they already have data and user has made purchases
        $hasOrders = $user->total_orders > 0 || Order::where('user_id', $user->id)->exists();
        
        return $hasOrders && !empty($user->$field);
    }

    /**
     * Check if profile is locked (completed and user has orders)
     */
    private function isProfileLocked($user)
    {
        $hasOrders = $user->total_orders > 0 || Order::where('user_id', $user->id)->exists();
        $isComplete = $this->calculateProfileCompletion($user) >= 100;
        
        return $hasOrders && $isComplete;
    }

    /**
     * Calculate profile completion percentage
     */
    private function calculateProfileCompletion($user)
    {
        $requiredFields = ['name', 'email', 'phone'];
        $optionalFields = ['gender', 'birthdate'];
        $filledRequired = 0;
        $filledOptional = 0;
        
        // Check required fields (70% weight)
        foreach ($requiredFields as $field) {
            if (!empty($user->$field)) {
                $filledRequired++;
            }
        }
        
        // Check optional fields (30% weight)
        foreach ($optionalFields as $field) {
            if (!empty($user->$field)) {
                $filledOptional++;
            }
        }
        
        $requiredPercentage = (count($requiredFields) > 0) ? ($filledRequired / count($requiredFields)) * 70 : 0;
        $optionalPercentage = (count($optionalFields) > 0) ? ($filledOptional / count($optionalFields)) * 30 : 0;
        
        return round($requiredPercentage + $optionalPercentage);
    }
}