<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\StoreAccountAddressRequest;
use App\Http\Requests\Storefront\UpdateAccountAddressRequest;
use App\Models\Address;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AccountAddressController extends Controller
{
    public function index(Request $request): Response
    {
        $addresses = $request->user()
            ->addresses()
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get();

        return Inertia::render('storefront/account-addresses', [
            'addresses' => $addresses->map(fn (Address $address) => $this->format($address)),
        ]);
    }

    public function store(StoreAccountAddressRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $data = $request->validated();

            if ($data['is_default'] ?? false) {
                $this->clearExistingDefault($request, $data['type']);
            }

            $request->user()->addresses()->create($data);
        });

        return back();
    }

    public function update(UpdateAccountAddressRequest $request, Address $address): RedirectResponse
    {
        abort_if($address->user_id !== $request->user()->id, 403);

        DB::transaction(function () use ($request, $address): void {
            $data = $request->validated();

            if ($data['is_default'] ?? false) {
                $this->clearExistingDefault($request, $data['type'], $address->id);
            }

            $address->update($data);
        });

        return back();
    }

    public function destroy(Request $request, Address $address): RedirectResponse
    {
        abort_if($address->user_id !== $request->user()->id, 403);

        $address->delete();

        return back();
    }

    /**
     * Un seul type d'adresse par défaut par client — sans ça, `storeAddress()`
     * (9.1) qui préremplit la commande depuis `is_default` ne saurait plus
     * laquelle choisir.
     */
    private function clearExistingDefault(Request $request, string $type, ?int $exceptId = null): void
    {
        $request->user()->addresses()
            ->where('type', $type)
            ->when($exceptId, fn ($query) => $query->whereNot('id', $exceptId))
            ->update(['is_default' => false]);
    }

    /**
     * @return array<string, mixed>
     */
    private function format(Address $address): array
    {
        return [
            'id' => $address->id,
            'type' => $address->type->value,
            'typeLabel' => $address->type->label(),
            'fullName' => $address->full_name,
            'line1' => $address->line1,
            'line2' => $address->line2,
            'postalCode' => $address->postal_code,
            'city' => $address->city,
            'countryCode' => $address->country_code,
            'phone' => $address->phone,
            'isDefault' => $address->is_default,
        ];
    }
}
