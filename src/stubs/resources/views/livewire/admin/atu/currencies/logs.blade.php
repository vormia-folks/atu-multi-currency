<?php

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
    public $expandedRows = []; // Track expanded rows by log ID

    public function toggleRow($logId)
    {
        if (in_array($logId, $this->expandedRows)) {
            $this->expandedRows = array_values(array_diff($this->expandedRows, [$logId]));
        } else {
            $this->expandedRows[] = $logId;
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
        $query = DB::table('atu_multicurrency_currency_conversion_log')
            ->leftJoin('atu_multicurrency_currencies', 'atu_multicurrency_currency_conversion_log.currency_id', '=', 'atu_multicurrency_currencies.id')
            ->leftJoin('users', 'atu_multicurrency_currency_conversion_log.user_id', '=', 'users.id')
            ->select(
                'atu_multicurrency_currency_conversion_log.*',
                'atu_multicurrency_currencies.code as currency_code',
                'atu_multicurrency_currencies.symbol as currency_symbol',
                'users.name as user_name',
                'users.email as user_email'
            );

        // Apply search filter
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('atu_multicurrency_currency_conversion_log.entity_type', 'like', '%' . $this->search . '%')
                  ->orWhere('atu_multicurrency_currency_conversion_log.base_currency_code', 'like', '%' . $this->search . '%')
                  ->orWhere('atu_multicurrency_currency_conversion_log.target_currency_code', 'like', '%' . $this->search . '%')
                  ->orWhere('atu_multicurrency_currencies.code', 'like', '%' . $this->search . '%')
                  ->orWhere('users.name', 'like', '%' . $this->search . '%')
                  ->orWhere('users.email', 'like', '%' . $this->search . '%');
            });
        }

        // Order by occurred_at desc by default
        $query->orderBy('atu_multicurrency_currency_conversion_log.occurred_at', 'desc');

        return $query->paginate($this->perPage);
    }
}; ?>

<div>
	<x-admin-panel>
		<x-slot name="header">{{ __('Currency Conversion Logs') }}</x-slot>
		<x-slot name="desc">
			{{ __('View all currency conversion logs and audit trail.') }}
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

		{{-- Search & Filter --}}
		<div class="my-4">
			<div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
				<div class="px-4 py-5 sm:p-6">
					<h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Search & Filter data</h3>
					<form class="sm:flex sm:items-center">
						<div class="w-full sm:max-w-xs">
							<input type="text" wire:model.live.debounce.300ms="search"
								class="block w-full rounded-md bg-white dark:bg-gray-700 px-3 py-1.5 text-base text-gray-900 dark:text-gray-100 outline-1 -outline-offset-1 outline-gray-300 dark:outline-gray-600 placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6"
								placeholder="Search logs..." />
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
						<th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Entity</th>
						<th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Context</th>
						<th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Conversion</th>
						<th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Rate</th>
						<th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Source</th>
						<th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">User</th>
						<th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Date</th>
					</tr>
				</thead>
				<tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
					@if ($this->results->isNotEmpty())
						@foreach ($this->results as $row)
							@php
								// Format entity display
								$_entity_display = $row->entity_type;
								if ($row->entity_id) {
									$_entity_display .= ' #' . $row->entity_id;
								}

								// Format conversion display
								$_conversion_display = number_format($row->base_amount, 2) . ' ' . $row->base_currency_code . ' → ' . number_format($row->converted_amount, 2) . ' ' . $row->target_currency_code;

								// Format user display
								$_user_display = $row->user_name ?: ($row->user_email ?: 'System');

								// Format date
								$_date_display = $row->occurred_at ? \Carbon\Carbon::parse($row->occurred_at)->format('Y-m-d H:i:s') : '-';

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
								<td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">
									<span class="font-medium">{{ ucfirst($row->entity_type) }}</span>
									@if ($row->entity_id)
										<span class="text-gray-400">#{{ $row->entity_id }}</span>
									@endif
								</td>
								<td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">
									<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-sm bg-blue-400 text-white">
										{{ ucfirst($row->context) }}
									</span>
								</td>
								<td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">
									{{ $_conversion_display }}
								</td>
								<td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">
									{{ number_format($row->rate_used, 6) }}
								</td>
								<td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">
									@if ($row->rate_source === 'api')
										<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-sm bg-green-400 text-white">
											API
										</span>
									@else
										<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-sm bg-gray-400 text-white">
											Manual
										</span>
									@endif
								</td>
								<td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">{{ $_user_display }}</td>
								<td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">{{ $_date_display }}</td>
							</tr>
							{{-- Expanded Details Row --}}
							@if ($_is_expanded)
								<tr class="bg-gray-50 dark:bg-gray-700/50">
									<td colspan="9" class="px-4 py-4">
										<dl class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
											<div>
												<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Base Amount</dt>
												<dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
													{{ number_format($row->base_amount, 6) }} {{ $row->base_currency_code }}
												</dd>
											</div>
											<div>
												<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Converted Amount</dt>
												<dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
													{{ number_format($row->converted_amount, 6) }} {{ $row->target_currency_code }}
												</dd>
											</div>
											<div>
												<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Rate Used</dt>
												<dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
													{{ number_format($row->rate_used, 8) }}
												</dd>
											</div>
											<div>
												<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Fee Applied</dt>
												<dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
													{{ $row->fee_applied ? number_format($row->fee_applied, 4) : 'None' }}
												</dd>
											</div>
											<div>
												<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Currency</dt>
												<dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
													{{ $row->currency_code }} ({{ $row->currency_symbol }})
												</dd>
											</div>
											<div>
												<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">User</dt>
												<dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
													{{ $_user_display }}
													@if ($row->user_email)
														<br><span class="text-gray-400 text-xs">{{ $row->user_email }}</span>
													@endif
												</dd>
											</div>
											<div>
												<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Entity Type</dt>
												<dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ ucfirst($row->entity_type) }}</dd>
											</div>
											<div>
												<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Entity ID</dt>
												<dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $row->entity_id ?: 'N/A' }}</dd>
											</div>
											<div>
												<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Context</dt>
												<dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ ucfirst($row->context) }}</dd>
											</div>
											<div>
												<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Occurred At</dt>
												<dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $_date_display }}</dd>
											</div>
											<div>
												<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Created At</dt>
												<dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
													{{ $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('Y-m-d H:i:s') : '-' }}
												</dd>
											</div>
										</dl>
									</td>
								</tr>
							@endif
						@endforeach
					@else
						<tr class="even:bg-gray-50 dark:even:bg-gray-800/50">
							<td colspan="9"
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
