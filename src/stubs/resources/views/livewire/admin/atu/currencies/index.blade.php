<?php

use Livewire\Attributes\On;
use Livewire\WithPagination;
use Livewire\Volt\Component;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\DB;
use App\Traits\Vrm\Livewire\WithNotifications;

new class extends Component {
    use WithPagination;
    use WithNotifications;

    public $search = '';
    public $perPage = 10;
    public $expandedRows = []; // Track expanded rows by currency ID

    public function toggleRow($currencyId)
    {
        if (in_array($currencyId, $this->expandedRows)) {
            $this->expandedRows = array_values(array_diff($this->expandedRows, [$currencyId]));
        } else {
            $this->expandedRows[] = $currencyId;
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    #[Computed]
    public function results()
    {
        $query = DB::table('atu_multicurrency_currencies');

        // Apply search filter
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('code', 'like', '%' . $this->search . '%')
                  ->orWhere('symbol', 'like', '%' . $this->search . '%');
            });
        }

        // Order by created_at desc by default
        $query->orderBy('created_at', 'desc');

        return $query->paginate($this->perPage);
    }

    public function toggleActive($currencyId)
    {
        try {
            $currency = DB::table('atu_multicurrency_currencies')->where('id', $currencyId)->first();
            
            if (!$currency) {
                $this->notifyError(__('Currency not found.'));
                return;
            }

            // Prevent deactivating default currency
            if ($currency->is_default && $currency->is_active) {
                $this->notifyError(__('Cannot deactivate the default currency.'));
                return;
            }

            DB::table('atu_multicurrency_currencies')
                ->where('id', $currencyId)
                ->update(['is_active' => !$currency->is_active]);

            $this->notifySuccess(__('Currency status updated successfully!'));
        } catch (\Exception $e) {
            $this->notifyError(__('Failed to update currency status: ' . $e->getMessage()));
        }
    }

    public function delete($currencyId)
    {
        try {
            $currency = DB::table('atu_multicurrency_currencies')->where('id', $currencyId)->first();
            
            if (!$currency) {
                $this->notifyError(__('Currency not found.'));
                return;
            }

            // Prevent deleting default currency
            if ($currency->is_default) {
                $this->notifyError(__('Cannot delete the default currency.'));
                return;
            }

            DB::table('atu_multicurrency_currencies')->where('id', $currencyId)->delete();

            $this->notifySuccess(__('Currency deleted successfully!'));
        } catch (\Exception $e) {
            $this->notifyError(__('Failed to delete currency: ' . $e->getMessage()));
        }
    }
}; ?>

<div>
	<x-admin-panel>
		<x-slot name="header">{{ __('Currencies') }}</x-slot>
		<x-slot name="desc">
			{{ __('Manage the currencies available in the application.') }}
			{{ __('You can create, edit, activate/deactivate, or delete currencies here.') }}
		</x-slot>
		<x-slot name="button">
			<div class="float-right flex gap-2">
				<a href="{{ route('admin.atu.currencies.logs') }}"
					class="bg-gray-500 dark:bg-gray-600 text-white hover:bg-gray-600 dark:hover:bg-gray-700 px-3 py-2 rounded-md text-sm font-bold">
					<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 inline-block">
						<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
					</svg>
					View Logs
				</a>
				<a href="{{ route('admin.atu.currencies.create') }}"
					class="bg-blue-500 dark:bg-blue-600 text-white hover:bg-blue-600 dark:hover:bg-blue-700 px-3 py-2 rounded-md text-sm font-bold">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-4 inline-block">
						<path fill-rule="evenodd"
							d="M12 3.75a.75.75 0 0 1 .75.75v6.75h6.75a.75.75 0 0 1 0 1.5h-6.75v6.75a.75.75 0 0 1-1.5 0v-6.75H4.5a.75.75 0 0 1 0-1.5h6.75V4.5a.75.75 0 0 1 .75-.75Z"
							clip-rule="evenodd" />
					</svg>
					Add New Currency
				</a>
			</div>
		</x-slot>

		{{-- Search & Filter --}}
		<div class="my-4">
			<div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
				<div class="px-4 py-5 sm:p-6">
					<h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Search & Filter data</h3>
					<form class="sm:flex sm:items-center">
						<div class="w-full sm:max-w-xs">
							<input type="text" wire:model.live.debounce.300ms="search"
								class="block w-full rounded-md bg-white dark:bg-gray-700 px-3 py-1.5 text-base text-gray-900 dark:text-gray-100 outline-1 -outline-offset-1 outline-gray-300 dark:outline-gray-600 placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6"
								placeholder="Search currencies..." />
						</div>
					</form>
				</div>
			</div>
		</div>

		{{-- Display notifications --}}
		{!! $this->renderNotification() !!}

		{{-- List --}}
		<div class="overflow-hidden shadow-sm ring-1 ring-black/5 dark:ring-white/10 sm:rounded-lg mt-2">
			<table class="min-w-full divide-y divide-gray-300 dark:divide-gray-600">
				<thead class="bg-gray-50 dark:bg-gray-700">
					<tr>
						<th scope="col" class="py-3.5 pr-3 pl-4 text-left text-sm font-semibold text-gray-900 dark:text-gray-100 sm:pl-3 w-12"></th>
						<th scope="col" class="py-3.5 pr-3 pl-4 text-left text-sm font-semibold text-gray-900 dark:text-gray-100 sm:pl-3">#ID</th>
						<th scope="col" class="py-3.5 pr-3 pl-4 text-left text-sm font-semibold text-gray-900 dark:text-gray-100 sm:pl-3">Code</th>
						<th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Symbol</th>
						<th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Rate</th>
						<th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Type</th>
						<th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Fee</th>
						<th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Country</th>
						<th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Status</th>
						<th scope="col" class="relative py-3.5 pr-4 pl-3 sm:pr-3">
							<span class="sr-only">Actions</span>
						</th>
					</tr>
				</thead>
				<tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
					@if ($this->results->isNotEmpty())
						@foreach ($this->results as $row)
							@php
								// Get country name if country_taxonomy_id exists
								$_country_name = '-';
								if ($row->country_taxonomy_id) {
									$_country = \App\Models\Vrm\Taxonomy::find($row->country_taxonomy_id);
									$_country_name = $_country ? $_country->name : '-';
								}

								// Get default currency code for rate display
								$_default_currency = DB::table('atu_multicurrency_currencies')
									->where('is_default', true)
									->first();
								$_default_code = $_default_currency ? $_default_currency->code : 'USD';

								// Check if row is expanded
								$_is_expanded = in_array($row->id, $this->expandedRows);
							@endphp
							<tr class="even:bg-gray-50 dark:even:bg-gray-800/50 hover:bg-gray-100 dark:hover:bg-gray-700">
								{{-- Expand/Collapse Button --}}
								<td class="py-4 pr-3 pl-4 text-sm font-medium whitespace-nowrap text-gray-900 dark:text-gray-100 sm:pl-3">
									<button wire:click="toggleRow({{ $row->id }})" type="button"
										class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none">
										@if ($_is_expanded)
											<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
												stroke="currentColor" class="size-5">
												<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15" />
											</svg>
										@else
											<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
												stroke="currentColor" class="size-5">
												<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
											</svg>
										@endif
									</button>
								</td>
								<td class="py-4 pr-3 pl-4 text-sm font-medium whitespace-nowrap text-gray-900 dark:text-gray-100 sm:pl-3">{{ $row->id }}</td>
								<td class="py-4 pr-3 pl-4 text-sm font-medium whitespace-nowrap text-gray-900 dark:text-gray-100 sm:pl-3">
									{{ $row->code }}
									@if ($row->is_default)
										<span class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-sm bg-blue-400 text-white">
											Default
										</span>
									@endif
								</td>
								<td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">{{ $row->symbol }}</td>
								<td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">
									1 {{ $row->code }} = {{ number_format($row->rate, 4) }} {{ $_default_code }}
								</td>
								<td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">
									@if ($row->is_auto)
										<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-sm bg-green-400 text-white">
											Auto
										</span>
									@else
										<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-sm bg-gray-400 text-white">
											Manual
										</span>
									@endif
								</td>
								<td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">
									{{ $row->fee ? number_format($row->fee, 2) : '-' }}
								</td>
								<td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">{{ $_country_name }}</td>
								<td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500">
									@if ($row->is_active)
										<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-sm bg-green-400 text-white">
											Active
										</span>
									@else
										<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-sm bg-red-400 text-white">
											Inactive
										</span>
									@endif
								</td>
								<td class="relative py-4 pr-4 pl-3 text-right text-sm font-medium whitespace-nowrap sm:pr-3">
									<div class="flex items-center justify-end gap-2">
										<a href="{{ route('admin.atu.currencies.edit', $row->id) }}"
											class="inline-flex items-center gap-x-1.5 rounded-md bg-indigo-600 px-2.5 py-1 text-xs font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
											<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
												stroke="currentColor" class="size-4">
												<path stroke-linecap="round" stroke-linejoin="round"
													d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
											</svg>
											Edit
										</a>
										@if (!$row->is_default)
											<button wire:click="toggleActive({{ $row->id }})" wire:confirm="Are you sure you want to {{ $row->is_active ? 'deactivate' : 'activate' }} this currency?"
												class="inline-flex items-center gap-x-1.5 rounded-md {{ $row->is_active ? 'bg-yellow-600 hover:bg-yellow-500' : 'bg-green-600 hover:bg-green-500' }} px-2.5 py-1 text-xs font-semibold text-white shadow-xs focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
												{{ $row->is_active ? 'Deactivate' : 'Activate' }}
											</button>
											<button wire:click="delete({{ $row->id }})" wire:confirm="Are you sure you want to delete this currency? This action cannot be undone."
												class="inline-flex items-center gap-x-1.5 rounded-md bg-red-600 px-2.5 py-1 text-xs font-semibold text-white shadow-xs hover:bg-red-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
												<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
													stroke="currentColor" class="size-4">
													<path stroke-linecap="round" stroke-linejoin="round"
														d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
												</svg>
												Delete
											</button>
										@endif
									</div>
								</td>
							</tr>
							{{-- Expanded Details Row --}}
							@if ($_is_expanded)
								<tr class="bg-gray-50 dark:bg-gray-700/50">
									<td colspan="10" class="px-4 py-4">
										<dl class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
											<div>
												<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Default Currency</dt>
												<dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
													{{ $row->is_default ? 'Yes' : 'No' }}
												</dd>
											</div>
											<div>
												<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Rate Source</dt>
												<dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
													{{ $row->is_auto ? 'API-managed' : 'Manual' }}
												</dd>
											</div>
											<div>
												<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Additional Fee</dt>
												<dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
													{{ $row->fee ? number_format($row->fee, 4) : 'None' }}
												</dd>
											</div>
											<div>
												<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Country</dt>
												<dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $_country_name }}</dd>
											</div>
											<div>
												<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Created At</dt>
												<dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
													{{ $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('Y-m-d H:i:s') : '-' }}
												</dd>
											</div>
											<div>
												<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Updated At</dt>
												<dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
													{{ $row->updated_at ? \Carbon\Carbon::parse($row->updated_at)->format('Y-m-d H:i:s') : '-' }}
												</dd>
											</div>
										</dl>
									</td>
								</tr>
							@endif
						@endforeach
					@else
						<tr class="even:bg-gray-50 dark:even:bg-gray-800/50">
							<td colspan="10"
								class="py-4 pr-3 pl-4 text-sm font-medium whitespace-nowrap text-gray-900 dark:text-gray-100 sm:pl-3 text-center">
								<span class="text-gray-500 dark:text-gray-400 text-2xl font-bold">No results found</span>
							</td>
						</tr>
					@endif
				</tbody>
			</table>
		</div>

		{{-- Pagination --}}
		<div class="mt-8">
			@if ($this->results->hasPages())
				<div class="p-2">
					{{ $this->results->links() }}
				</div>
			@endif
		</div>
	</x-admin-panel>
</div>
