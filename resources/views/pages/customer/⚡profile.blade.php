<?php

use Livewire\Component;
use Illuminate\Support\Facades\Hash;
use App\Models\Address;

new class extends Component
{
    // Profile fields
    public $name = '';
    public $phone = '';
    public $email = '';
    public $date_of_birth = '';
    public $gender = '';

    // Password fields
    public $current_password = '';
    public $new_password = '';
    public $new_password_confirmation = '';

    // Address fields
    public $showAddressForm = false;
    public $address_id = null;
    public $address_full_name = '';
    public $address_phone = '';
    public $address_line_1 = '';
    public $address_line_2 = '';
    public $address_city = '';
    public $address_state = '';
    public $address_country = 'KH';
    public $address_is_default = false;

    public function mount()
    {
        $user = auth('customer')->user();
        $this->name = $user->name;
        $this->phone = $user->phone;
        $this->email = $user->email;
        $this->date_of_birth = $user->date_of_birth;
        $this->gender = $user->gender;
    }

    public function updateProfile()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|string|max:255',
        ]);

        auth('customer')->user()->update([
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'date_of_birth' => $this->date_of_birth,
            'gender' => $this->gender,
        ]);

        session()->flash('profile_success', 'Profile information updated successfully');
    }

    public function updatePassword()
    {
        $this->validate([
            'current_password' => 'required|string|max:255',
            'new_password' => 'required|string|min:8|max:255',
            'new_password_confirmation' => 'required|string|same:new_password',
        ]);

        if (!Hash::check($this->current_password, auth('customer')->user()->password)) {
            session()->flash('password_error', 'Current password is incorrect');
            return;
        }

        auth('customer')->user()->update([
            'password' => Hash::make($this->new_password),
        ]);

        $this->reset('current_password', 'new_password', 'new_password_confirmation');
        session()->flash('password_success', 'Password updated successfully');
    }

    public function addAddress()
    {
        $this->resetAddressFields();
        $this->showAddressForm = true;
    }

    public function editAddress($id)
    {
        $address = Address::where('id', $id)
            ->where('customer_id', auth('customer')->id())
            ->firstOrFail();

        $this->address_id = $address->id;
        $this->address_full_name = $address->full_name;
        $this->address_phone = $address->phone;
        $this->address_line_1 = $address->address_line_1;
        $this->address_line_2 = $address->address_line_2;
        $this->address_city = $address->city;
        $this->address_state = $address->state;
        $this->address_country = $address->country ?? 'KH';
        $this->address_is_default = $address->is_default;

        $this->showAddressForm = true;
    }

    public function saveAddress()
    {
        $this->validate([
            'address_full_name' => 'required|string|max:255',
            'address_phone' => 'required|string|max:255',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'address_city' => 'required|string|max:255',
            'address_state' => 'nullable|string|max:255',
        ]);

        $data = [
            'customer_id' => auth('customer')->id(),
            'full_name' => $this->address_full_name,
            'phone' => $this->address_phone,
            'address_line_1' => $this->address_line_1,
            'address_line_2' => $this->address_line_2,
            'city' => $this->address_city,
            'state' => $this->address_state,
            'country' => $this->address_country,
            'is_default' => $this->address_is_default,
        ];

        if ($this->address_id) {
            Address::where('id', $this->address_id)
                ->where('customer_id', auth('customer')->id())
                ->update($data);
        } else {
            Address::create($data);
        }

        // Ensure only one default address exists
        if ($this->address_is_default) {
            Address::where('customer_id', auth('customer')->id())
                ->where('id', '!=', $this->address_id ?? Address::latest()->first()->id)
                ->update(['is_default' => false]);
        }

        $this->showAddressForm = false;
        $this->resetAddressFields();
        session()->flash('address_success', 'Address saved successfully');
    }

    public function deleteAddress($id)
    {
        Address::where('id', $id)
            ->where('customer_id', auth('customer')->id())
            ->delete();

        session()->flash('address_success', 'Address deleted successfully');
    }

    public function cancelAddressForm()
    {
        $this->showAddressForm = false;
        $this->resetAddressFields();
    }

    private function resetAddressFields()
    {
        $this->reset([
            'address_id', 'address_full_name', 'address_phone', 'address_line_1',
            'address_line_2', 'address_city', 'address_state', 'address_is_default'
        ]);
        $this->address_country = 'KH';
    }

    public function with()
    {
        $customer = auth('customer')->user();

        $addresses = method_exists($customer, 'addresses')
            ? $customer->addresses()->get()
            : ($customer->address()->get() ?? collect());

        return [
            'addresses' => $addresses
        ];
    }
};
?>

<div class="bg-gray-50 py-8">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">My Profile</h1>
            <nav class="text-sm">
                <ol class="flex items-center gap-2">
                    <li><a href="{{ route('customer.dashboard') }}" class="text-gray-500 hover:text-blue-600">Account</a></li>
                    <li class="text-gray-400">/</li>
                    <li class="text-gray-900 font-medium">Profile</li>
                </ol>
            </nav>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex flex-col items-center text-center mb-6">
                        <div class="w-24 h-24 bg-blue-600 text-white rounded-full flex items-center justify-center text-3xl font-bold mb-4">
                            {{ substr(auth('customer')->user()->name, 0, 1) }}
                        </div>
                        <h2 class="text-xl font-bold text-gray-900">{{ auth('customer')->user()->name }}</h2>
                        <p class="text-gray-600">{{ auth('customer')->user()->email }}</p>
                    </div>
                    <div class="border-t pt-4">
                        <p class="text-sm text-gray-600">Member since</p>
                        <p class="font-medium text-gray-900">{{ auth('customer')->user()->created_at->format('M d, Y') }}</p>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">Profile Information</h2>

                    @if (session()->has('profile_success'))
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                            {{ session('profile_success') }}
                        </div>
                    @endif

                    <form wire:submit="updateProfile" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                            <input type="text"
                                   wire:model="name"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            @error('name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                            <input type="email"
                                   wire:model="email"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            @error('email') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                            <input type="tel"
                                   wire:model="phone"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            @error('phone') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Date of Birth</label>
                                <input type="date"
                                       wire:model="date_of_birth"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Gender</label>
                                <select wire:model="gender"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                                    <option value="">Select</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>

                        <button type="submit"
                                class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-indigo-700 transition font-semibold">
                            Update Profile
                        </button>
                    </form>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">Change Password</h2>

                    @if (session()->has('password_success'))
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                            {{ session('password_success') }}
                        </div>
                    @endif
                    @if (session()->has('password_error'))
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                            {{ session('password_error') }}
                        </div>
                    @endif

                    <form wire:submit="updatePassword" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                            <input type="password"
                                   wire:model="current_password"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            @error('current_password') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                            <input type="password"
                                   wire:model="new_password"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            @error('new_password') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                            <input type="password"
                                   wire:model="new_password_confirmation"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <button type="submit"
                                class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-indigo-700 transition font-semibold">
                            Change Password
                        </button>
                    </form>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-900">Saved Addresses</h2>
                        @if(!$showAddressForm)
                            <button wire:click="addAddress"
                                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition font-medium">
                                + Add Address
                            </button>
                        @endif
                    </div>

                    @if (session()->has('address_success'))
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                            {{ session('address_success') }}
                        </div>
                    @endif

                    @if($showAddressForm)
                        <div class="border rounded-lg p-4 mb-4">
                            <h3 class="font-semibold text-gray-900 mb-4">
                                {{ $address_id ? 'Edit Address' : 'Add New Address' }}
                            </h3>
                            <form wire:submit="saveAddress" class="space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                                        <input type="text"
                                               wire:model="address_full_name"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                                        @error('address_full_name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Phone *</label>
                                        <input type="tel"
                                               wire:model="address_phone"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                                        @error('address_phone') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Address Line 1 *</label>
                                    <input type="text"
                                           wire:model="address_line_1"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                                    @error('address_line_1') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Address Line 2</label>
                                    <input type="text"
                                           wire:model="address_line_2"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">City/Province *</label>
                                        <input type="text"
                                               wire:model="address_city"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                                        @error('address_city') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">District/Khan</label>
                                        <input type="text"
                                               wire:model="address_state"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                                    </div>
                                </div>

                                <div>
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox"
                                               wire:model="address_is_default"
                                               class="w-4 h-4 text-blue-600 rounded">
                                        <span class="text-sm text-gray-700">Set as default address</span>
                                    </label>
                                </div>

                                <div class="flex gap-2">
                                    <button type="submit"
                                            class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-indigo-700 transition font-semibold">
                                        Save Address
                                    </button>
                                    <button type="button"
                                            wire:click="cancelAddressForm"
                                            class="flex-1 bg-gray-200 text-gray-900 py-2 px-4 rounded-lg hover:bg-gray-300 transition font-semibold">
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif

                    @if($addresses->count() > 0)
                        <div class="space-y-4">
                            @foreach($addresses as $address)
                                <div wire:key="address-{{ $address->id }}" class="border rounded-lg p-4">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <p class="font-semibold text-gray-900">{{ $address->full_name }}</p>
                                            <p class="text-gray-600">{{ $address->phone }}</p>

                                            <p class="text-gray-600 mt-2">
                                                {{ $address->address_line_1 }}
                                                @if($address->address_line_2), {{ $address->address_line_2 }}@endif
                                                <br>
                                                {{ $address->city }}@if($address->state), {{ $address->state }}@endif
                                            </p>

                                            @if($address->is_default)
                                                <span class="inline-block mt-2 bg-indigo-100 text-indigo-800 text-xs px-2 py-1 rounded">
                                                    Default
                                                </span>
                                            @endif
                                        </div>
                                        <div class="flex gap-2">
                                            <button wire:click="editAddress({{ $address->id }})"
                                                    class="text-blue-600 hover:text-indigo-700">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <button wire:click="deleteAddress({{ $address->id }})"
                                                    wire:confirm="Are you sure you want to delete this address?"
                                                    class="text-red-600 hover:text-red-700">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        @if(!$showAddressForm)
                            <p class="text-gray-600 text-center py-4">No saved addresses</p>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>  
