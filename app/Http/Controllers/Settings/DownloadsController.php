<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Data\DownloadData;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Support\Number;
use Inertia\Inertia;
use Inertia\Response;

class DownloadsController extends Controller
{
    public function __construct(
        #[CurrentUser]
        private readonly User $user,
    ) {
        //
    }

    public function __invoke(): Response
    {
        $completedOrderIds = Order::query()
            ->whereBelongsTo($this->user)
            ->completed()
            ->pluck('id');

        $downloads = collect();

        $products = Product::query()
            ->active()
            ->whereHas('orderItems', function ($query) use ($completedOrderIds): void {
                $query->whereIn('order_id', $completedOrderIds);
            })
            ->whereHas('files')
            ->get();

        foreach ($products as $product) {
            $orderDate = Order::query()
                ->whereIn('id', $completedOrderIds)
                ->whereHas('items', function ($query) use ($product): void {
                    $query->where('product_id', $product->id);
                })
                ->latest()
                ->value('created_at');

            foreach ($product->files as $file) {
                $downloads->push(DownloadData::from([
                    'id' => (string) $file->id,
                    'name' => $file->name,
                    'description' => $file->description ?? 'File from '.$product->name,
                    'file_size' => Number::fileSize((int) $file->size ?? 0),
                    'file_type' => $file->mime ?? 'application/octet-stream',
                    'download_url' => $file->url,
                    'product_name' => $product->name,
                    'created_at' => $orderDate?->toISOString() ?? $file->created_at->toISOString(),
                ]));
            }
        }

        return Inertia::render('settings/downloads', [
            'downloads' => $downloads,
        ]);
    }
}
