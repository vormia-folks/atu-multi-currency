<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Validate;
use App\Traits\Vrm\Livewire\WithNotifications;
use Vormia\ATUMultiCurrency\Models\Currency;
use Vormia\ATUMultiCurrency\Support\SettingsManager;

new class extends Component {
    use WithNotifications;

    #[Validate('required|boolean')]
    public $apply_fees = true;

    #[Validate('required|boolean')]
    public $log_conversions = true;

    #[Validate('required|integer|min:0|max:10')]
    public $round_precision = 2;

    public $default_currency_code = 'USD';
    public $settings_source = 'file';

    public function mount()
    {
        $settingsManager = app(SettingsManager::class);

        // Get default currency
        $defaultCurrency = Currency::where('is_default', true)->first();
        $this->default_currency_code = $defaultCurrency ? $defaultCurrency->code : 'USD';

        // Get settings source
        $this->settings_source = config('atu-multi-currency.settings_source', 'file');

        // Get conversion settings
        $conversionSettings = $settingsManager->getSetting('conversion', [
            'apply_fees' => true,
            'log_conversions' => true,
            'round_precision' => 2,
        ]);

        if (is_array($conversionSettings)) {
            $this->apply_fees = $conversionSettings['apply_fees'] ?? true;
            $this->log_conversions = $conversionSettings['log_conversions'] ?? true;
            $this->round_precision = $conversionSettings['round_precision'] ?? 2;
        } else {
            // Fallback to config if not array
            $this->apply_fees = config('atu-multi-currency.conversion.apply_fees', true);
            $this->log_conversions = config('atu-multi-currency.conversion.log_conversions', true);
            $this->round_precision = config('atu-multi-currency.conversion.round_precision', 2);
        }
    }

    public function save()
    {
        $this->validate();

        $settingsManager = app(SettingsManager::class);

        // Check if settings source is database
        if (!$settingsManager->isDatabaseSource()) {
            $this->notifyError(__('Settings are currently managed from config file. Set ATU_CURRENCY_SETTINGS_SOURCE=database in .env to enable database settings.'));
            return;
        }

        try {
            // Save conversion settings as JSON
            $conversionSettings = [
                'apply_fees' => $this->apply_fees,
                'log_conversions' => $this->log_conversions,
                'round_precision' => $this->round_precision,
            ];

            $settingsManager->setSetting('conversion', $conversionSettings);

            $this->notifySuccess(__('Currency settings updated successfully!'));
        } catch (\Exception $e) {
            $this->notifyError(__('Failed to update currency settings: ' . $e->getMessage()));
        }
    }
}; ?>

<div>
	<x-admin-panel>
		<x-slot name="header">{{ __('Currency Settings') }}</x-slot>
		<x-slot name="desc">
			{{ __('Manage currency conversion settings and behavior.') }}
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

		<div class="overflow-hidden shadow-sm ring-1 ring-black/5 dark:ring-white/10 sm:rounded-lg px-4 py-5 mb-5 sm:p-6">
			{{-- Display notifications --}}
			{!! $this->renderNotification() !!}

			<form wire:submit="save">
				<div class="space-y-12">
					<div class="grid grid-cols-1 gap-x-8 gap-y-10 pb-12 md:grid-cols-3">
						<div>
							<h2 class="text-base/7 font-semibold text-gray-900 dark:text-gray-100">Conversion Settings</h2>
							<p class="mt-1 text-sm/6 text-gray-600 dark:text-gray-300">Configure how currency conversions are handled.</p>
						</div>

						<div class="grid max-w-2xl grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6 md:col-span-2">
							<div class="col-span-full">
								<label for="default_currency_code" class="block text-sm/6 font-medium text-gray-900 dark:text-gray-100">Default Currency</label>
								<div class="mt-2">
									<div
										class="flex items-center rounded-md bg-gray-100 dark:bg-gray-800 pl-3 outline-1 -outline-offset-1 outline-gray-300 dark:outline-gray-600">
										<input type="text" id="default_currency_code" value="{{ $this->default_currency_code }}" readonly
											class="block min-w-0 grow py-1.5 pr-3 pl-1 text-base text-gray-900 dark:text-gray-100 sm:text-sm/6 cursor-not-allowed" />
									</div>
									<p class="mt-1 text-sm text-gray-500 dark:text-gray-400">The default currency cannot be changed from this interface.</p>
								</div>
							</div>

							<div class="col-span-full">
								<label for="settings_source" class="block text-sm/6 font-medium text-gray-900 dark:text-gray-100">Settings Source</label>
								<div class="mt-2">
									<div
										class="flex items-center rounded-md bg-gray-100 dark:bg-gray-800 pl-3 outline-1 -outline-offset-1 outline-gray-300 dark:outline-gray-600">
										<input type="text" id="settings_source" value="{{ ucfirst($this->settings_source) }}" readonly
											class="block min-w-0 grow py-1.5 pr-3 pl-1 text-base text-gray-900 dark:text-gray-100 sm:text-sm/6 cursor-not-allowed" />
									</div>
									<p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
										Settings are currently loaded from {{ $this->settings_source === 'database' ? 'database' : 'config file' }}. 
										@if($this->settings_source === 'file')
											To enable database settings, set <code class="text-xs bg-gray-200 dark:bg-gray-700 px-1 py-0.5 rounded">ATU_CURRENCY_SETTINGS_SOURCE=database</code> in your .env file.
										@endif
									</p>
								</div>
							</div>

							<div class="col-span-full">
								<div class="flex items-start">
									<div class="flex h-6 items-center">
										<input type="checkbox" id="apply_fees" wire:model="apply_fees"
											class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-600" />
									</div>
									<div class="ml-3 text-sm leading-6">
										<label for="apply_fees" class="font-medium text-gray-900 dark:text-gray-100">Apply Fees</label>
										<p class="text-gray-500 dark:text-gray-400">Enable automatic fee application during currency conversion. Fees are added to the converted amount.</p>
									</div>
								</div>
								<span class="text-red-500 text-sm italic"> {{ $errors->first('apply_fees') }}</span>
							</div>

							<div class="col-span-full">
								<div class="flex items-start">
									<div class="flex h-6 items-center">
										<input type="checkbox" id="log_conversions" wire:model="log_conversions"
											class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-600" />
									</div>
									<div class="ml-3 text-sm leading-6">
										<label for="log_conversions" class="font-medium text-gray-900 dark:text-gray-100">Log Conversions</label>
										<p class="text-gray-500 dark:text-gray-400">Enable logging of all currency conversions to the conversion log table for audit and reporting purposes.</p>
									</div>
								</div>
								<span class="text-red-500 text-sm italic"> {{ $errors->first('log_conversions') }}</span>
							</div>

							<div class="col-span-full">
								<label for="round_precision" class="block text-sm/6 font-medium text-gray-900 dark:text-gray-100 required">Round Precision</label>
								<div class="mt-2">
									<div
										class="flex items-center rounded-md bg-white dark:bg-gray-700 pl-3 outline-1 -outline-offset-1 outline-gray-300 dark:outline-gray-600 focus-within:outline-2 focus-within:-outline-offset-2 focus-within:outline-indigo-600">
										<input type="number" id="round_precision" wire:model="round_precision" min="0" max="10"
											class="block min-w-0 grow py-1.5 pr-3 pl-1 text-base text-gray-900 dark:text-gray-100 placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:outline-none sm:text-sm/6" />
									</div>
									<span class="text-red-500 text-sm italic"> {{ $errors->first('round_precision') }}</span>
									<p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Number of decimal places to round converted amounts to (0-10).</p>
								</div>
							</div>

							<div class="col-span-full">
								<div class="flex items-center justify-end gap-x-3 border-t border-gray-900/10 dark:border-gray-100/10 pt-4">
									<button type="submit" wire:loading.attr="disabled"
										class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
										<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
											stroke="currentColor" class="size-6 inline-block">
											<path stroke-linecap="round" stroke-linejoin="round"
												d="M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0 1 18 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v9.375m-8.25-3 1.5 1.5 3-3.75" />
										</svg>
										<span wire:loading.remove>Save Settings</span>
										<span wire:loading>Saving...</span>
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
