<?php

namespace Vormia\ATUMultiCurrency\Http\Controllers\Api\Atu\Multicurrency;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Vormia\ATUMultiCurrency\Models\Currency;
use Vormia\ATUMultiCurrency\Support\CurrencySyncService;

class CurrencyController extends ApiController
{
    private const SESSION_KEY = 'atu_currency';

    public function index(Request $request)
    {
        try {
            $validated = $request->validate([
                'search' => 'nullable|string|max:100',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $query = Currency::query()->where('is_active', true);

            $search = $validated['search'] ?? null;
            if (is_string($search) && trim($search) !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('code', 'like', '%' . $search . '%')
                        ->orWhere('symbol', 'like', '%' . $search . '%')
                        ->orWhere('name', 'like', '%' . $search . '%');
                });
            }

            $perPage = (int) ($validated['per_page'] ?? 10);
            $paginator = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return $this->successPaginated(
                $paginator->items(),
                [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                ],
                'Currencies fetched',
                200
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors(), $e->getMessage());
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), 500);
        }
    }

    public function current(Request $request)
    {
        try {
            $default = Currency::where('is_default', true)->first();
            $selectedCode = (string) $request->session()->get(self::SESSION_KEY, '');

            $current = null;
            if ($selectedCode !== '') {
                $current = Currency::where('code', strtoupper($selectedCode))->where('is_active', true)->first();
            }

            if (! $current && $default) {
                $current = $default;
            }

            return $this->success(
                [
                    'current' => $current,
                    'default' => $default,
                ],
                'Current currency fetched',
                200
            );
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), 500);
        }
    }

    public function switch(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|min:3|max:4',
            ]);

            $code = strtoupper(trim((string) $validated['code']));
            $currency = Currency::where('code', $code)->where('is_active', true)->first();

            if (! $currency) {
                return $this->notFound('Currency not found or inactive');
            }

            $request->session()->put(self::SESSION_KEY, $currency->code);

            return $this->success(
                [
                    'current' => $currency,
                ],
                'Currency switched',
                200
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors(), $e->getMessage());
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'nullable|string|min:3|max:4',
                'symbol' => 'nullable|string|max:10',
                'name' => 'nullable|string|max:255',
                'rate' => 'required|numeric|min:0.00000001',
                'is_auto' => 'required|boolean',
                'fee' => 'nullable|numeric|min:0',
                'country_taxonomy_id' => 'nullable|integer|exists:vrm_taxonomies,id',
            ]);

            $code = trim((string) ($validated['code'] ?? ''));
            $symbol = trim((string) ($validated['symbol'] ?? ''));

            if ($code === '' && $symbol !== '') {
                $code = $symbol;
            } elseif ($symbol === '' && $code !== '') {
                $symbol = $code;
            }

            if ($code === '' && $symbol === '') {
                return $this->error('Either Currency Code or Currency Symbol must be provided.', 422);
            }

            $code = strtoupper(trim($code));

            $codeLength = strlen($code);
            if ($codeLength < 3 || $codeLength > 4) {
                return $this->error('Currency Code must be between 3 and 4 characters.', 422);
            }

            if (Currency::where('code', $code)->exists()) {
                return $this->error('Currency code already exists.', 422);
            }

            $isDefault = ! Currency::where('is_default', true)->exists();
            $rate = $isDefault ? 1.0 : (float) $validated['rate'];

            $currency = Currency::create([
                'code' => $code,
                'symbol' => $symbol,
                'name' => isset($validated['name']) && trim((string) $validated['name']) !== '' ? trim((string) $validated['name']) : null,
                'rate' => $rate,
                'is_auto' => (bool) $validated['is_auto'],
                'fee' => $validated['fee'] ?? null,
                'country_taxonomy_id' => $validated['country_taxonomy_id'] ?? null,
                'is_default' => $isDefault,
                'is_active' => true,
            ]);

            if ($isDefault) {
                app(CurrencySyncService::class)->syncToA2Commerce();
            }

            return $this->success($currency, 'Currency created', 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors(), $e->getMessage());
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), 500);
        }
    }

    public function update(Request $request, int $id)
    {
        try {
            $currency = Currency::find($id);
            if (! $currency) {
                return $this->notFound('Currency not found');
            }

            $validated = $request->validate([
                'code' => 'nullable|string|min:3|max:4',
                'symbol' => 'nullable|string|max:10',
                'name' => 'nullable|string|max:255',
                'rate' => 'required|numeric|min:0.00000001',
                'is_auto' => 'required|boolean',
                'fee' => 'nullable|numeric|min:0',
                'country_taxonomy_id' => 'nullable|integer|exists:vrm_taxonomies,id',
            ]);

            $code = trim((string) ($validated['code'] ?? ''));
            $symbol = trim((string) ($validated['symbol'] ?? ''));

            if ($code === '' && $symbol !== '') {
                $code = $symbol;
            } elseif ($symbol === '' && $code !== '') {
                $symbol = $code;
            }

            if ($code === '' && $symbol === '') {
                return $this->error('Either Currency Code or Currency Symbol must be provided.', 422);
            }

            $code = strtoupper(trim($code));

            $codeLength = strlen($code);
            if ($codeLength < 3 || $codeLength > 4) {
                return $this->error('Currency Code must be between 3 and 4 characters.', 422);
            }

            $codeExists = Currency::where('code', $code)->where('id', '!=', $currency->id)->exists();
            if ($codeExists) {
                return $this->error('Currency code already exists.', 422);
            }

            $rate = (float) $validated['rate'];
            if ($currency->is_default && $rate != 1.0) {
                return $this->error('Default currency rate must be 1.0.', 422);
            }

            $currency->update([
                'code' => $code,
                'symbol' => $symbol,
                'name' => isset($validated['name']) && trim((string) $validated['name']) !== '' ? trim((string) $validated['name']) : null,
                'rate' => $currency->is_default ? 1.0 : $rate,
                'is_auto' => (bool) $validated['is_auto'],
                'fee' => $validated['fee'] ?? null,
                'country_taxonomy_id' => $validated['country_taxonomy_id'] ?? null,
            ]);

            if ($currency->is_default) {
                app(CurrencySyncService::class)->syncToA2Commerce();
            }

            $currency->refresh();

            return $this->success($currency, 'Currency updated', 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors(), $e->getMessage());
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), 500);
        }
    }

    public function destroy(int $id)
    {
        try {
            $currency = Currency::find($id);
            if (! $currency) {
                return $this->notFound('Currency not found');
            }

            if ($currency->is_default) {
                return $this->error('Cannot delete the default currency.', 422);
            }

            $currency->delete();

            return $this->success(null, 'Currency deleted', 200);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), 500);
        }
    }

    public function toggleActive(int $id)
    {
        try {
            $currency = Currency::find($id);
            if (! $currency) {
                return $this->notFound('Currency not found');
            }

            if ($currency->is_default && $currency->is_active) {
                return $this->error('Cannot deactivate the default currency.', 422);
            }

            $currency->update(['is_active' => ! $currency->is_active]);
            $currency->refresh();

            return $this->success($currency, 'Currency status updated', 200);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), 500);
        }
    }

    public function setDefault(int $id)
    {
        try {
            $currency = Currency::find($id);
            if (! $currency) {
                return $this->notFound('Currency not found');
            }

            if (! $currency->is_active) {
                return $this->error('Cannot set an inactive currency as default.', 422);
            }

            if ($currency->is_default) {
                return $this->success($currency, 'This currency is already the default currency.', 200);
            }

            DB::beginTransaction();

            Currency::where('is_default', true)->update(['is_default' => false]);
            $currency->update([
                'is_default' => true,
                'rate' => '1.00000000',
            ]);

            app(CurrencySyncService::class)->syncToA2Commerce();

            DB::commit();

            $currency->refresh();

            return $this->success($currency, 'Default currency updated', 200);
        } catch (\Throwable $th) {
            DB::rollBack();

            return $this->error($th->getMessage(), 500);
        }
    }
}
