@if (auth()->user()?->isAdminOrSuperAdmin())
	<hr />

	<flux:navlist.item icon="currency-dollar" :href="route('admin.atu.currencies.index')"
		:current="request()->routeIs('admin.atu.currencies.*')" wire:navigate>
		{{ __('Currencies') }}
	</flux:navlist.item>
@endif
