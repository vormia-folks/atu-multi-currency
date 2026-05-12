@if (auth()->user()?->isAdminOrSuperAdmin())
	<hr />

	{{-- Currencies Menu Item --}}
	<flux:navlist.item icon="currency-dollar" :href="route('admin.atu.currencies.index')"
		:current="request()->routeIs('admin.atu.currencies.index') || request()->routeIs('admin.atu.currencies.create') || request()->routeIs('admin.atu.currencies.edit')" wire:navigate>
		{{ __('Currencies') }}
	</flux:navlist.item>

	{{-- Currency Logs Menu Item --}}
	<flux:navlist.item icon="document-text" :href="route('admin.atu.currencies.logs')"
		:current="request()->routeIs('admin.atu.currencies.logs')" wire:navigate>
		{{ __('Currency Logs') }}
	</flux:navlist.item>

	{{-- Currency Settings Menu Item --}}
	<flux:navlist.item icon="cog-6-tooth" :href="route('admin.atu.currencies.settings')"
		:current="request()->routeIs('admin.atu.currencies.settings')" wire:navigate>
		{{ __('Currency Settings') }}
	</flux:navlist.item>
@endif
