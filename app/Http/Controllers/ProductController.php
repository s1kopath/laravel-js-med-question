<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
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
        $product = Product::create(['title' => $request->title, 'sku' => $request->sku, 'description' => $request->description]);

        $product_image = new ProductImage();
        if ($request->hasFile('product_image')) {
            foreach ($request->file('product_image') as $img) {
                $file = $img;
                $filename = time() . '-' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/products'), $filename);
                $product_image->create(['product_id' => $product->id, 'file_path' => $filename]);
            }
        }

        $product_variant = new ProductVariant();
        foreach ($request->product_variant as $variant) {
            $variant = json_decode($variant);
            foreach ($variant->tags as $tag) {
                $product_variant->create(['variant' => $tag, 'variant_id' => $variant->option, 'product_id' => $product->id]);
            }
        }

        foreach ($request->product_variant_prices as $price) {
            $pv_prices = new ProductVariantPrice();
            $price = json_decode($price);
            $attrs = explode("/", $price->title);

            $product_variant_ids = [];
            for ($i = 0; $i < count($attrs) - 1; $i++) {
                $product_variant_ids[] = ProductVariant::select('id')->where('variant', $attrs[$i])->latest()->first()->id;
            }

            for ($i = 1; $i <= count($product_variant_ids); $i++) {
                $pv_prices->{'product_variant_' . $i} = $product_variant_ids[$i - 1];
            }
            $pv_prices->price = $price->price;
            $pv_prices->stock = $price->stock;
            $pv_prices->product_id = $product->id;
            $pv_prices->save();
        }
        return response('product added successfully');
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
        $product = Product::with(['prices', 'productToVariants'])->find($product->id);
        $variants = Variant::all();
        return view('products.edit', compact('variants', 'product'));
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
        $p_id = $product->id;
        $product = Product::where('id', $product->id)->update(['title' => $request->title, 'sku' => $request->sku, 'description' => $request->description]);

        //  if there is image
        if ($request->hasFile('product_image')) {
            // remove previous image
            $this->removeImage($p_id);
            $product_image = new ProductImage();
            foreach ($request->file('product_image') as $img) {
                $file = $img;
                $filename = time() . '-' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/products'), $filename);
                // save filename to database
                $product_image->create(['product_id' => $p_id, 'file_path' => $filename]);
            }
        }
        $product_variant = new ProductVariant();
        foreach ($request->product_variant as $variant) {
            $variant = json_decode($variant);
            $product_variants = $product_variant->where('variant_id', $variant->option)->where('product_id', $p_id)->get();
            $num_tags = 0;
            $num_product_variants = count($product_variants);
            foreach ($variant->tags as $index => $tag) {
                $num_tags += 1;
                if ($num_product_variants >= $index + 1) {
                    $product_variants[$index]->update(['variant' => $tag]);
                } else {
                    $product_variant->create(['variant' => $tag, 'variant_id' => $variant->option, 'product_id' => $p_id]);
                }
            }
            for ($i = 1; $i <= $num_product_variants - $num_tags; $i++) {
                $product_variants[$num_product_variants - $i]->delete();
            }
        }

        $num_req_prices = 0;
        foreach ($request->product_variant_prices as $index => $price) {
            $price = json_decode($price);
            $attrs = explode("/", $price->title);
            $product_variant_ids = [];
            for ($i = 0; $i < count($attrs) - 1; $i++) {
                $product_variant_ids[] = ProductVariant::select('id')->where('variant', $attrs[$i])->latest()->first()->id;
            }

            $new_pv_prices = new ProductVariantPrice();
            $pv_prices = ProductVariantPrice::where('product_id', $p_id)->get();
            $num_pv_prices = count($pv_prices);

            $num_req_prices += 1;

            if ($num_pv_prices >= $index + 1) {
                for ($i = 1; $i <= count($product_variant_ids); $i++) {
                    $pv_prices[$index]->{'product_variant_' . $i} = $product_variant_ids[$i - 1];
                }
                $pv_prices[$index]->price = $price->price;
                $pv_prices[$index]->stock = $price->stock;
                $pv_prices[$index]->product_id = $p_id;
                $pv_prices[$index]->save();
            } else {
                for ($i = 1; $i <= count($product_variant_ids); $i++) {
                    $new_pv_prices->{'product_variant_' . $i} = $product_variant_ids[$i - 1];
                }
                $new_pv_prices->price = $price->price;
                $new_pv_prices->stock = $price->stock;
                $new_pv_prices->product_id = $p_id;
                $new_pv_prices->save();
            }
        }
        for ($i = 1; $i <= $num_pv_prices - $num_req_prices; $i++) {
            $pv_prices[$num_pv_prices - $i]->delete();
        }

        return 1;
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
