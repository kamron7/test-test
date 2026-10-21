<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function purchase(Request $request)
    {
        $data = $request->validate([
            'provider_id' => 'required|exists:providers,id',
            'storage_id' => 'required|exists:storages,id',
            'products' => 'required|array',
            'products.*.id' => 'required|integer|distinct',
            'products.*.qty' => 'required|integer|min:1',
            'products.*.price' => 'required|numeric|min:0',
        ]);

        $ids = array_column($data['products'], 'id');
        abort_if(DB::table('products')->whereIn('id', $ids)->count() < count($ids), 422, 'Unknown product');

        return DB::transaction(function () use ($data) {
            $batch = Batch::create($data);
            $lines = $movements = [];

            foreach ($data['products'] as $product) {
                $lines[] = ['batch_id' => $batch->id, 'product_id' => $product['id'], 'price' => $product['price']];
                $movements[] = ['batch_id' => $batch->id, 'product_id' => $product['id'], 'qty' => $product['qty'], 'created_at' => $batch->created_at];
            }

            DB::table('batch_products')->insert($lines);
            DB::table('stock_movements')->insert($movements);

            return $batch;
        });
    }

    public function refund(Request $request, Batch $batch)
    {
        $data = $request->validate([
            'products' => 'required|array',
            'products.*.id' => 'required|integer|distinct',
            'products.*.qty' => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($data, $batch) {
            DB::table('products')->whereIn('id', array_column($data['products'], 'id'))->lockForUpdate()->get();

            $stock = DB::table('stock_movements')
                ->where('batch_id', $batch->id)
                ->groupBy('product_id')
                ->selectRaw('product_id, SUM(qty) as qty')
                ->pluck('qty', 'product_id');

            $movements = [];

            foreach ($data['products'] as $product) {
                abort_if(($stock[$product['id']] ?? 0) < $product['qty'], 422, "Not enough unsold product {$product['id']} in this batch");

                $movements[] = ['batch_id' => $batch->id, 'product_id' => $product['id'], 'qty' => -$product['qty'], 'created_at' => now()];
            }

            DB::table('stock_movements')->insert($movements);
        });

        return response()->noContent();
    }

    public function products()
    {
        return DB::table('stock_movements as m')
            ->join('products as p', 'p.id', '=', 'm.product_id')
            ->join('categories as c', 'c.id', '=', 'p.category_id')
            ->groupBy('p.id', 'p.name', 'c.name', 'p.price')
            ->havingRaw('SUM(m.qty) > 0')
            ->get(['p.id', 'p.name', 'c.name as category_name', 'p.price', DB::raw('SUM(m.qty) as qty')]);
    }

    public function order(Request $request)
    {
        $data = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'products' => 'required|array',
            'products.*.id' => 'required|integer|distinct',
            'products.*.qty' => 'required|integer|min:1',
        ]);

        $ids = array_column($data['products'], 'id');

        return DB::transaction(function () use ($data, $ids) {
            $prices = DB::table('products')->whereIn('id', $ids)->lockForUpdate()->pluck('price', 'id');

            $lots = DB::table('stock_movements')
                ->whereIn('product_id', $ids)
                ->groupBy('batch_id', 'product_id')
                ->havingRaw('SUM(qty) > 0')
                ->orderBy('batch_id')
                ->get(['batch_id', 'product_id', DB::raw('SUM(qty) as qty')]);

            $order = Order::create($data);
            $movements = [];

            foreach ($data['products'] as $product) {
                $need = $product['qty'];

                foreach ($lots->where('product_id', $product['id']) as $lot) {
                    $take = min($need, $lot->qty);
                    $need -= $take;
                    $movements[] = ['batch_id' => $lot->batch_id, 'product_id' => $product['id'], 'order_id' => $order->id, 'qty' => -$take, 'price' => $prices[$product['id']], 'created_at' => $order->created_at];

                    if ($need == 0) {
                        break;
                    }
                }

                abort_if($need > 0, 422, "Not enough stock for product {$product['id']}");
            }

            DB::table('stock_movements')->insert($movements);

            return $order;
        });
    }

    public function stock(Request $request)
    {
        $date = $request->validate(['date' => 'required|date'])['date'];

        return DB::table('stock_movements as m')
            ->join('batches as b', 'b.id', '=', 'm.batch_id')
            ->join('storages as s', 's.id', '=', 'b.storage_id')
            ->join('products as p', 'p.id', '=', 'm.product_id')
            ->whereDate('m.created_at', '<=', $date)
            ->groupBy('s.id', 's.name', 'p.id', 'p.name')
            ->havingRaw('SUM(m.qty) > 0')
            ->get(['s.id as storage_id', 's.name as storage_name', 'p.id as product_id', 'p.name as product_name', DB::raw('SUM(m.qty) as qty')]);
    }

    public function profit()
    {
        return DB::table('stock_movements as m')
            ->join('batch_products as bp', fn ($join) => $join->on('bp.batch_id', '=', 'm.batch_id')->on('bp.product_id', '=', 'm.product_id'))
            ->groupBy('m.batch_id')
            ->get(['m.batch_id', DB::raw('SUM(IF(m.order_id IS NULL, 0, -m.qty * (m.price - bp.price))) as profit')]);
    }
}
