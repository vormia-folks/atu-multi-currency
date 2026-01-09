<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Validate;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\DB;
use App\Traits\Vrm\Livewire\WithNotifications;
use Vormia\ATUMultiCurrency\Support\CurrencySyncService;

new class extends Component {
    use WithNotifications;

    #[Validate('nullable|string|min:3|max:4|unique:atu_multicurrency_currencies,code')]
    public $code = '';

    #[Validate('nullable|string|max:10')]
    public $symbol = '';

    #[Validate('nullable|string|max:255')]
    public $name = '';

    #[Validate('required|numeric|min:0.00000001')]
    public $rate = 1.0;

    #[Validate('required|boolean')]
    public $is_auto = false;

    #[Validate('nullable|numeric|min:0')]
    public $fee = null;

    #[Validate('nullable|integer|exists:vrm_taxonomies,id')]
    public $country_taxonomy_id = null;

    public function mount()
    {
        // Get default currency for rate display
        $defaultCurrency = DB::table('atu_multicurrency_currencies')
            ->where('is_default', true)
            ->first();
        
        if ($defaultCurrency) {
            $this->rate = 1.0; // Default rate is 1:1 with default currency
        }
    }

    #[Computed]
    public function country_list()
    {
        return \App\Models\Vrm\Taxonomy::where('group', 'country')
            ->where('is_active', true)
            ->get();
    }

    #[Computed]
    public function default_currency()
    {
        return DB::table('atu_multicurrency_currencies')
            ->where('is_default', true)
            ->first();
    }

    public function save()
    {
        // Apply automatic fallback logic: if code is empty, use symbol; if symbol is empty, use code
        if (empty(trim($this->code)) && !empty(trim($this->symbol))) {
            $this->code = $this->symbol;
        } elseif (empty(trim($this->symbol)) && !empty(trim($this->code))) {
            $this->symbol = $this->code;
        }

        // Validate that at least one of code or symbol is provided
        if (empty(trim($this->code)) && empty(trim($this->symbol))) {
            $this->notifyError(__('Either Currency Code or Currency Symbol must be provided.'));
            return;
        }

        // Validate code length after fallback
        if (!empty(trim($this->code))) {
            $codeLength = strlen(trim($this->code));
            if ($codeLength < 3 || $codeLength > 4) {
                $this->notifyError(__('Currency Code must be between 3 and 4 characters.'));
                return;
            }
        }

        $this->validate();

        try {
            // Check if code already exists
            $exists = DB::table('atu_multicurrency_currencies')
                ->where('code', strtoupper($this->code))
                ->exists();

            if ($exists) {
                $this->notifyError(__('Currency code already exists.'));
                return;
            }

            $isDefault = false;
            
            // Check if this should be the default currency (only if no default exists)
            $existingDefault = DB::table('atu_multicurrency_currencies')
                ->where('is_default', true)
                ->exists();
            
            if (!$existingDefault) {
                $isDefault = true;
                $this->rate = 1.0; // Default currency must have rate 1.0
            }

            DB::table('atu_multicurrency_currencies')->insert([
                'code' => strtoupper(trim($this->code)),
                'symbol' => trim($this->symbol),
                'name' => !empty(trim($this->name)) ? trim($this->name) : null,
                'rate' => $isDefault ? 1.0 : $this->rate,
                'is_auto' => $this->is_auto,
                'fee' => $this->fee,
                'country_taxonomy_id' => $this->country_taxonomy_id,
                'is_default' => $isDefault,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // If this is the default currency, sync with A2Commerce
            if ($isDefault) {
                $syncService = app(CurrencySyncService::class);
                $syncService->syncToA2Commerce();
            }

            $this->notifySuccess(__('Currency created successfully!'));
        } catch (\Exception $e) {
            $this->notifyError(__('Failed to create currency: ' . $e->getMessage()));
        }
    }

    public function cancel()
    {
        $this->notifyInfo(__('Currency creation cancelled!'));
    }
}; ?>

<div>
	<x-admin-panel>
		<x-slot name="header">{{ __('Add New Currency') }}</x-slot>
		<x-slot name="desc">
			{{ __('Add a new currency to the system.') }}
		</x-slot>

		<x-slot name="button">
			<a href="{{ route('admin.atu.currencies.index') }}"
				class="bg-black dark:bg-gray-700 text-white hover:bg-gray-800 dark:hover:bg-gray-600 px-3 py-2 rounded-md float-right text-sm font-bold">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-4 inline-block">
					<path fill-rule="evenodd"
						d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm-4.28 9.22a.75.75 0 0 0 0 1.06l3 3a.75.75 0 1 0 1.06-1.06l-1.72-1.72h5.69a.75.75 0 0 0 0-1.5h-5.69l1.72-1.72a.75.75 0 0 0-1.06-1.06l-3 3Z"
						clip-rule="evenodd" />
				</svg>
				Go Back
			</a>
		</x-slot>

		{{-- Create Form --}}
		<div class="overflow-hidden shadow-sm ring-1 ring-black/5 dark:ring-white/10 sm:rounded-lg px-4 py-5 mb-5 sm:p-6">
			{{-- Display notifications --}}
			{!! $this->renderNotification() !!}

			<form wire:submit="save">
				<div class="space-y-12">
					<div class="grid grid-cols-1 gap-x-8 gap-y-10 pb-12 md:grid-cols-3">
						<div>
							<h2 class="text-base/7 font-semibold text-gray-900 dark:text-gray-100">Currency Details</h2>
							<p class="mt-1 text-sm/6 text-gray-600 dark:text-gray-300">Enter the currency information below.</p>
						</div>

						<div class="grid max-w-2xl grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6 md:col-span-2">
							<div class="col-span-full sm:col-span-3">
								<label for="code" class="block text-sm/6 font-medium text-gray-900 dark:text-gray-100">Currency Code</label>
								<div class="mt-2">
									<div
										class="flex items-center rounded-md bg-white dark:bg-gray-700 pl-3 outline-1 -outline-offset-1 outline-gray-300 dark:outline-gray-600 focus-within:outline-2 focus-within:-outline-offset-2 focus-within:outline-indigo-600">
										<input type="text" id="code" wire:model="code" placeholder="USD, EUR, ZAR, etc."
											maxlength="4"
											class="block min-w-0 grow py-1.5 pr-3 pl-1 text-base text-gray-900 dark:text-gray-100 placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:outline-none sm:text-sm/6 uppercase" />
									</div>
									<span class="text-red-500 text-sm italic"> {{ $errors->first('code') }}</span>
									<p class="mt-1 text-sm text-gray-500 dark:text-gray-400">ISO 4217 currency code (3-4 characters, e.g., USD, EUR, ZAR). If empty, will use Currency Symbol.</p>
								</div>
							</div>

							<div class="col-span-full sm:col-span-3">
								<label for="symbol" class="block text-sm/6 font-medium text-gray-900 dark:text-gray-100">Currency Symbol</label>
								<div class="mt-2">
									<div
										class="flex items-center rounded-md bg-white dark:bg-gray-700 pl-3 outline-1 -outline-offset-1 outline-gray-300 dark:outline-gray-600 focus-within:outline-2 focus-within:-outline-offset-2 focus-within:outline-indigo-600">
										<input type="text" id="symbol" wire:model="symbol" placeholder="$, €, R, etc."
											maxlength="10"
											class="block min-w-0 grow py-1.5 pr-3 pl-1 text-base text-gray-900 dark:text-gray-100 placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:outline-none sm:text-sm/6" />
									</div>
									<span class="text-red-500 text-sm italic"> {{ $errors->first('symbol') }}</span>
									<p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Currency symbol to display (e.g., $, €, R). If empty, will use Currency Code.</p>
								</div>
							</div>

							<div class="col-span-full">
								<label for="name" class="block text-sm/6 font-medium text-gray-900 dark:text-gray-100">Currency Name</label>
								<div class="mt-2">
									<div
										class="flex items-center rounded-md bg-white dark:bg-gray-700 pl-3 outline-1 -outline-offset-1 outline-gray-300 dark:outline-gray-600 focus-within:outline-2 focus-within:-outline-offset-2 focus-within:outline-indigo-600">
										<input type="text" id="name" wire:model="name" placeholder="United States Dollar, South African Rand, etc."
											maxlength="255"
											class="block min-w-0 grow py-1.5 pr-3 pl-1 text-base text-gray-900 dark:text-gray-100 placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:outline-none sm:text-sm/6" />
									</div>
									<span class="text-red-500 text-sm italic"> {{ $errors->first('name') }}</span>
									<p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Optional: Full descriptive name of the currency (e.g., "South African Rand", "United States Dollar")</p>
								</div>
							</div>

							<div class="col-span-full">
								<label for="rate" class="block text-sm/6 font-medium text-gray-900 dark:text-gray-100 required">Conversion Rate</label>
								<div class="mt-2">
									<div
										class="flex items-center rounded-md bg-white dark:bg-gray-700 pl-3 outline-1 -outline-offset-1 outline-gray-300 dark:outline-gray-600 focus-within:outline-2 focus-within:-outline-offset-2 focus-within:outline-indigo-600">
										<input type="number" id="rate" wire:model="rate" step="0.00000001" min="0.00000001"
											class="block min-w-0 grow py-1.5 pr-3 pl-1 text-base text-gray-900 dark:text-gray-100 placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:outline-none sm:text-sm/6" />
									</div>
									<span class="text-red-500 text-sm italic"> {{ $errors->first('rate') }}</span>
									<p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
										Conversion rate against default currency ({{ $this->default_currency ? $this->default_currency->code : 'USD' }}). 
										Example: 1 {{ $this->code ?: 'XXX' }} = {{ $this->rate ?: '1.00' }} {{ $this->default_currency ? $this->default_currency->code : 'USD' }}
									</p>
								</div>
							</div>

							<div class="col-span-full">
								<label class="block text-sm/6 font-medium text-gray-900 dark:text-gray-100 required">Use Auto or Manual</label>
								<div class="mt-2">
									<div class="flex gap-6">
										<div class="flex items-center">
											<input type="radio" id="is_auto_1" wire:model="is_auto" value="1"
												class="h-4 w-4 text-indigo-600 focus:ring-indigo-600 border-gray-300 dark:border-gray-600" />
											<label for="is_auto_1" class="ml-2 block text-sm text-gray-900 dark:text-gray-100">
												Auto (API-managed)
											</label>
										</div>
										<div class="flex items-center">
											<input type="radio" id="is_auto_0" wire:model="is_auto" value="0"
												class="h-4 w-4 text-indigo-600 focus:ring-indigo-600 border-gray-300 dark:border-gray-600" />
											<label for="is_auto_0" class="ml-2 block text-sm text-gray-900 dark:text-gray-100">
												Manual
											</label>
										</div>
									</div>
									<span class="text-red-500 text-sm italic"> {{ $errors->first('is_auto') }}</span>
									<p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
										Choose whether exchangeratesapi manages rates (auto) or manual rate above will be used
									</p>
								</div>
							</div>

							<div class="col-span-full">
								<label for="fee" class="block text-sm/6 font-medium text-gray-900 dark:text-gray-100">Additional Fee</label>
								<div class="mt-2">
									<div
										class="flex items-center rounded-md bg-white dark:bg-gray-700 pl-3 outline-1 -outline-offset-1 outline-gray-300 dark:outline-gray-600 focus-within:outline-2 focus-within:-outline-offset-2 focus-within:outline-indigo-600">
										<input type="number" id="fee" wire:model="fee" step="0.01" min="0"
											class="block min-w-0 grow py-1.5 pr-3 pl-1 text-base text-gray-900 dark:text-gray-100 placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:outline-none sm:text-sm/6" />
									</div>
									<span class="text-red-500 text-sm italic"> {{ $errors->first('fee') }}</span>
									<p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
										Optional fee to add on checkout. Example: if the rate is 10 and the fee is 2, every time on checkout a fee of 2 will be added on the total cart
									</p>
								</div>
							</div>

							<div class="col-span-full">
								<label for="country_taxonomy_id" class="block text-sm/6 font-medium text-gray-900 dark:text-gray-100">Country</label>
								<div class="mt-2">
									<div wire:ignore>
										<select wire:model="country_taxonomy_id" id="country_select"
											class="w-full country-select select2 py-2.5 px-3 border border-gray-300 dark:border-gray-600 rounded-md text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:ring-blue-500">
											<option value="">-- No Country --</option>
											@foreach ($this->country_list as $_index => $_country)
												<option value="{{ $_country->id }}" @selected($this->country_taxonomy_id == $_country->id)>
													{{ $_country->name }}
												</option>
											@endforeach
										</select>
									</div>
									<span class="text-red-500 text-sm italic"> {{ $errors->first('country_taxonomy_id') }}</span>
									<p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Optional: Select a country related to this currency</p>
								</div>
							</div>

							<div class="col-span-full">
								<div class="flex items-center justify-end gap-x-3 border-t border-gray-900/10 dark:border-gray-100/10 pt-4">
									<button type="button" wire:click="cancel"
										class="text-sm font-semibold text-gray-900 dark:text-gray-100">Cancel</button>

									<button type="submit" wire:loading.attr="disabled"
										class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
										<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
											stroke="currentColor" class="size-6 inline-block">
											<path stroke-linecap="round" stroke-linejoin="round"
												d="M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0 1 18 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v9.375m-8.25-3 1.5 1.5 3-3.75" />
										</svg>
										<span wire:loading.remove>Create Currency</span>
										<span wire:loading>Creating...</span>
									</button>
								</div>
							</div>
						</div>
					</div>
				</div>
			</form>
		</div>
	</x-admin-panel>
</div>

<script>
	document.addEventListener('livewire:init', () => {
		Livewire.on('livewire:load', () => {
			// Initialize Select2 for country dropdown
			if (jQuery && jQuery.fn.select2) {
				jQuery('#country_select').select2({
					theme: 'bootstrap-5',
					width: '100%'
				}).on('change', function (e) {
					@this.set('country_taxonomy_id', e.target.value);
				});
			}
		});
	});
</script>
