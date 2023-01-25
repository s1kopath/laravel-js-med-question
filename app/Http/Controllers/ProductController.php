<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariantPrice;
use App\Models\Variant;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function index()
    {
        $products = Product::with(
            'productToProductVariantPrice',
            'productToProductVariantPrice.variantOnePriceToVarianProduct',
            'productToProductVariantPrice.variantTwoPriceToVarianProduct',
            'productToProductVariantPrice.variantThreePriceToVarianProduct'
        )
            ->paginate(2);
        $variantPrices = ProductVariantPrice::with(
            'variantOnePriceToVarianProduct',
            'variantTwoPriceToVarianProduct',
            'variantThreePriceToVarianProduct'
        )->get();

        return view('products.index', compact('products', 'variantPrices'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function create()
    {
        $variants = Variant::all();
        return view('products.create', compact('variants'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        dd($request->all());
    }


    /**
     * Display the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function show($product)
    {
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        $variants = Variant::all();
        return view('products.edit', compact('variants'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        //
    }

    public function filterProducts(Request $request)
    {
        $rawProducts = Product::with(
            'productToProductVariantPrice',
            'productToProductVariantPrice.variantOnePriceToVarianProduct',
            'productToProductVariantPrice.variantTwoPriceToVarianProduct',
            'productToProductVariantPrice.variantThreePriceToVarianProduct'
        );

        if ($request->title) {
            $rawProducts->where('title', $request->title);
        }

        if ($request->variant) {
            $variant = $request->variant;
            $rawProducts->whereHas('productToProductVariantPrice', function ($query) use ($variant) {
                $query->where('id', $variant);
            });
        }

        if ($request->date) {
            $rawProducts->whereBetween('created_at', [$request->date . ' 08:18:53', $request->date . ' 23:59:59']);
        }

        if ($request->price_from) {
            $price_from = $request->price_from;
            $rawProducts->whereHas('productToProductVariantPrice', function ($query) use ($price_from) {
                $query->where('price', '>=', $price_from);
            });
        }

        if ($request->price_to) {
            $price_to = $request->price_to;
            $rawProducts->whereHas('productToProductVariantPrice', function ($query) use ($price_to) {
                $query->where('price', '<=', $price_to);
            });
        }

        $products = $rawProducts->paginate(2);

        $variantPrices = ProductVariantPrice::with(
            'variantOnePriceToVarianProduct',
            'variantTwoPriceToVarianProduct',
            'variantThreePriceToVarianProduct'
        )->get();

        return view('products.index', compact('products', 'variantPrices'));
    }
}
