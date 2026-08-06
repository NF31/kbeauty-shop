<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReturnRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    private const PER_SECTION = 5;

    /**
     * Palette globale (Ctrl+K) du back-office : chaque section n'est
     * peuplee que si l'utilisateur a le droit de voir la page cible - une
     * meme requete pour un membre "support" ne doit pas laisser fuiter des
     * commandes/produits qu'il ne peut pas ouvrir (memes gardes que
     * routes/admin.php).
     *
     * Requetes jointes/regroupees plutot que des ->with() Eloquent separes :
     * chaque aller-retour vers Neon coute ~75ms (connexion directe, cf. .env)
     * et cet endpoint est tape a chaque frappe - 8 sections en Eloquent
     * "naif" auraient coute ~12 requetes (dont les with()), ramene a 6 ici.
     */
    public function suggest(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q'));
        $user = $request->user();

        $empty = [
            'orders' => [],
            'products' => [],
            'brands' => [],
            'categories' => [],
            'productLines' => [],
            'returnRequests' => [],
            'contactMessages' => [],
            'users' => [],
        ];

        if (mb_strlen($term) < 2) {
            return response()->json($empty);
        }

        if ($user->can('orders.manage')) {
            $empty['orders'] = $this->searchOrders($term);
            $empty['returnRequests'] = $this->searchReturnRequests($term);
        }

        if ($user->can('products.manage')) {
            $empty['products'] = $this->searchProducts($term);
            [$empty['brands'], $empty['categories'], $empty['productLines']] = $this->searchCatalogTerms($term);
        }

        $empty['contactMessages'] = $this->searchContactMessages($term);

        if ($user->hasRole('admin')) {
            $empty['users'] = $this->searchUsers($term);
        }

        return response()->json($empty);
    }

    /**
     * @return array<int, array{label: string, url: string}>
     */
    private function searchOrders(string $term): array
    {
        $like = "%{$term}%";

        return DB::table('orders')
            ->leftJoin('users', 'users.id', '=', 'orders.user_id')
            ->select('orders.id', 'orders.order_number', 'users.name as user_name')
            ->where(fn ($q) => $q
                ->whereLike('orders.order_number', $like)
                ->orWhereLike('users.name', $like)
                ->orWhereLike('users.email', $like))
            ->orderByDesc('orders.placed_at')
            ->limit(self::PER_SECTION)
            ->get()
            ->map(fn ($row) => [
                'label' => $row->order_number.($row->user_name ? ' — '.$row->user_name : ''),
                'url' => route('admin.orders.show', $row->id),
            ])
            ->all();
    }

    /**
     * @return array<int, array{label: string, url: string}>
     */
    private function searchProducts(string $term): array
    {
        return Product::search($term)
            ->take(self::PER_SECTION)
            ->get()
            ->map(fn (Product $product) => [
                'label' => $product->name,
                'url' => route('admin.products.edit', $product),
            ])
            ->all();
    }

    /**
     * Marques, categories et gammes ont la meme forme (id, name) - une seule
     * requete UNION ALL plutot que 3 requetes separees pour ces listes de
     * reference, chaque sous-requete gardant sa propre limite.
     *
     * @return array{0: array<int, array{label: string, url: string}>, 1: array<int, array{label: string, url: string}>, 2: array<int, array{label: string, url: string}>}
     */
    private function searchCatalogTerms(string $term): array
    {
        $like = "%{$term}%";

        $rows = DB::table('brands')
            ->select('id', 'name', DB::raw("'brand' as type"))
            ->whereLike('name', $like)
            ->orderBy('name')
            ->limit(self::PER_SECTION)
            ->unionAll(
                DB::table('categories')
                    ->select('id', 'name', DB::raw("'category' as type"))
                    ->whereLike('name', $like)
                    ->orderBy('name')
                    ->limit(self::PER_SECTION)
            )
            ->unionAll(
                DB::table('product_lines')
                    ->select('id', 'name', DB::raw("'product_line' as type"))
                    ->whereLike('name', $like)
                    ->orderBy('name')
                    ->limit(self::PER_SECTION)
            )
            ->get();

        $brands = [];
        $categories = [];
        $productLines = [];

        foreach ($rows as $row) {
            $item = ['label' => $row->name, 'url' => match ($row->type) {
                'brand' => route('admin.brands.edit', $row->id),
                'category' => route('admin.categories.edit', $row->id),
                default => route('admin.product-lines.edit', $row->id),
            }];

            match ($row->type) {
                'brand' => $brands[] = $item,
                'category' => $categories[] = $item,
                default => $productLines[] = $item,
            };
        }

        return [$brands, $categories, $productLines];
    }

    /**
     * @return array<int, array{label: string, url: string}>
     */
    private function searchReturnRequests(string $term): array
    {
        return DB::table('return_requests')
            ->join('orders', 'orders.id', '=', 'return_requests.order_id')
            ->select('return_requests.id', 'orders.order_number', 'return_requests.status')
            ->whereLike('orders.order_number', "%{$term}%")
            ->orderByDesc('return_requests.id')
            ->limit(self::PER_SECTION)
            ->get()
            ->map(fn ($row) => [
                'label' => $row->order_number.' — '.ReturnRequestStatus::from($row->status)->label(),
                'url' => route('admin.return-requests.show', $row->id),
            ])
            ->all();
    }

    /**
     * @return array<int, array{label: string, url: string}>
     */
    private function searchContactMessages(string $term): array
    {
        return ContactMessage::query()
            ->where(fn ($q) => $q
                ->whereLike('name', "%{$term}%")
                ->orWhereLike('email', "%{$term}%")
                ->orWhereLike('subject', "%{$term}%"))
            ->latest('id')
            ->take(self::PER_SECTION)
            ->get()
            ->map(fn (ContactMessage $message) => [
                'label' => $message->subject.' — '.$message->name,
                'url' => route('admin.contact-messages.show', $message),
            ])
            ->all();
    }

    /**
     * @return array<int, array{label: string, url: string}>
     */
    private function searchUsers(string $term): array
    {
        return User::query()
            ->where(fn ($q) => $q
                ->whereLike('name', "%{$term}%")
                ->orWhereLike('email', "%{$term}%"))
            ->orderBy('name')
            ->take(self::PER_SECTION)
            ->get()
            ->map(fn (User $user) => [
                'label' => $user->name.' — '.$user->email,
                'url' => route('admin.users.index'),
            ])
            ->all();
    }
}
