<?php

namespace App\Http\Controllers\Api\Atu\Multicurrency;

use Illuminate\Http\Request;
use Vormia\ATUMultiCurrency\Models\CurrencyConversionLog;

class CurrencyLogsController extends ApiController
{
    public function conversionLogs(Request $request)
    {
        try {
            $validated = $request->validate([
                'search' => 'nullable|string|max:100',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $query = CurrencyConversionLog::with(['currency', 'user']);

            $search = $validated['search'] ?? null;
            if (is_string($search) && trim($search) !== '') {
                $searchTerm = $search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('entity_type', 'like', '%' . $searchTerm . '%')
                        ->orWhere('base_currency_code', 'like', '%' . $searchTerm . '%')
                        ->orWhere('target_currency_code', 'like', '%' . $searchTerm . '%')
                        ->orWhereHas('currency', function ($currencyQuery) use ($searchTerm) {
                            $currencyQuery->where('code', 'like', '%' . $searchTerm . '%');
                        })
                        ->orWhereHas('user', function ($userQuery) use ($searchTerm) {
                            $userQuery->where('name', 'like', '%' . $searchTerm . '%')
                                ->orWhere('email', 'like', '%' . $searchTerm . '%');
                        });
                });
            }

            $perPage = (int) ($validated['per_page'] ?? 10);
            $paginator = $query->orderBy('occurred_at', 'desc')->paginate($perPage);

            return $this->successPaginated(
                $paginator->items(),
                [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                ],
                'Conversion logs fetched',
                200
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors(), $e->getMessage());
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), 500);
        }
    }
}

