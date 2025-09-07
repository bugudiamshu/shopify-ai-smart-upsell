@extends('layouts.app')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
<style>
    .select2-container .select2-results__option, .select2-search__field {
        color: #000 !important;
    }
</style>
@endpush

@section('content')
<div class="p-4 max-w-8xl mx-auto">

    <div class="w-full inline-flex justify-between">
        <h2 class="text-2xl font-bold mb-6">
            Upsell Recommendations for
            <span class="text-[var(--primary)]">{{ $shop->shopify_domain ?? '-' }}</span>
        </h2>
        {{-- Sync button --}}
        <div class="mb-6 flex justify-between items-center">
            <form action="{{ route('products.sync') }}" method="POST" class="flex gap-4">
                @csrf
                <input type="hidden" name="shop" value="{{ $shop->shopify_domain }}">
                <button type="submit"
                        class="bg-green-600 hover:bg-green-700 active:bg-green-800 text-white font-semibold text-sm px-4 py-2 rounded transition">
                    <span style="font-size: 20px">⟳</span> Sync Products from Store
                </button>
            </form>
        </div>
    </div>

    @if (session('error'))
    <div x-data="{ show: true }" x-show="show"
         class="relative flex items-center justify-between gap-4 mt-3 mb-3 p-4 rounded bg-red-100 text-red-800 border border-red-300 shadow">
        <span class="text-sm font-medium">{{ session('error') }}</span>
        <button @click="show = false"
                class="text-xl font-bold leading-none text-red-800 hover:text-red-900 focus:outline-none">
            &times;
        </button>
    </div>
    @endif

    @if (session('success') || request('success'))
    <div x-data="{ show: true }" x-show="show"
         class="relative flex items-center justify-between gap-4 mt-3 mb-3 p-4 rounded bg-green-100 text-green-800 border border-green-300 shadow">
        <span class="text-sm font-medium">{{ session('success') ?? request('success') }}</span>
        <button @click="show = false"
                class="text-xl font-bold leading-none text-green-800 hover:text-green-900 focus:outline-none">
            &times;
        </button>
    </div>
    @endif

    {{-- Add recommendation form --}}
    <div class="bg-white p-4 rounded shadow mb-8">
        <h2 class="text-lg font-semibold mb-4 text-gray-800">Add a Recommendation</h2>
        <form method="POST" action="{{ route('recommendations.create', ['shop' => $shop->shopify_domain]) }}" class="flex flex-wrap gap-4 items-center">
            @csrf
            <span class="text-gray-600">Product:</span>
            <select name="product_id" required
                    class="select2-product block appearance-none w-full sm:w-auto px-4 py-2 pr-8 rounded border border-gray-300 bg-white text-black shadow-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)] focus:border-[var(--primary)]">
                <option value="" class="text-black">Select product</option>
                @foreach($products as $id => $product)
                <option value="{{ $id }}" class="text-black">{{ $product->title }}</option>
                @endforeach
            </select>
            <span class="mx-1 text-gray-500">→</span>
            <span class="text-gray-600">Upsell:</span>
            <select name="recommended_product_id" required
                    class="select2-product block appearance-none w-full sm:w-auto px-4 py-2 pr-8 rounded border border-gray-300 bg-white text-black shadow-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)] focus:border-[var(--primary)]">
                <option value="" class="text-black">Select upsell</option>
                @foreach($products as $id => $product)
                <option value="{{ $id }}" class="text-black">{{ $product->title }}</option>
                @endforeach
            </select>
            <button type="submit"
                    class="bg-green-600 hover:bg-green-700 active:bg-green-800 text-white px-2 py-1 font-semibold text-sm sm:text-base rounded transition">
                + Add Recommendation
            </button>
        </form>
    </div>

    <div class="bg-white p-3 rounded shadow">
        <div class="w-full inline-flex justify-between">
            <h2 class="text-lg font-semibold mb-4 text-gray-800">Current Recommendations</h2>
            <form method="GET" class="mb-6" x-data="searchForm()" @submit.prevent>
                <input type="hidden" name="shop" :value="shop">

                <div class="relative w-full sm:w-64">
                    <input
                        id="search"
                        type="text"
                        name="search"
                        x-model="search"
                        placeholder="🔍 Search product or upsell..."
                        class="w-full px-3 py-2 pr-8 rounded border border-gray-300 shadow-sm text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 text-black"
                        @input.debounce.300ms="autoSubmit"
                    >
                    <!-- Dismiss button inside input -->
                    <button type="button" x-show="search.length > 0" @click="clear" style="margin-top: -8px; font-size: 24px"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 text-sm">
                        &times;
                    </button>
                </div>
            </form>
        </div>

        @if($recs->count())
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm text-gray-800">
                <thead class="bg-gray-50 text-xs uppercase tracking-wider text-left text-gray-600">
                    <tr>
                        <th class="px-4 py-2">Product</th>
                        <th class="px-4 py-2">Recommended Product</th>
                        <th class="px-4 py-2">Algorithm</th>
                        <th class="px-4 py-2 text-center">Conversions</th>
                        <th class="px-4 py-2">Enable/Disable</th>
                        <th class="px-4 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($recs as $rec)
                    @php
                    $productTitle = $products[$rec->product_id]->title ?? 'Unknown Product';
                    $productImages = $products[$rec->product_id]->images;
                    $productImage = count($productImages) > 0 ? $productImages[0]['src'] : null;
                    $upsellTitle = $products[$rec->recommended_product_id]->title ?? 'Unknown Upsell';
                    $upsellImages = $products[$rec->recommended_product_id]->images;
                    $upsellImage = count($upsellImages) > 0 ? $upsellImages[0]['src'] : null;
                    $conversionCount = (!empty($conversions[$rec->id]) && method_exists($conversions[$rec->id], 'count'))
                    ? $conversions[$rec->id]->count()
                    : 0;
                    @endphp
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-2 font-medium">
                            <div class="inline-flex items-center">
                                <span><img src="{{ $productImage }}" height="30" width="30"/></span>
                                {{ $productTitle }}
                            </div>
                        </td>
                        <td class="px-4 py-2 text-[var(--primary)] font-medium">
                            <div class="inline-flex items-center">
                                <span><img src="{{ $upsellImage }}" height="30" width="30"/></span>
                                {{ $upsellTitle }}
                            </div>
                        </td>
                        <td class="px-4 py-2">
                        <span class="inline-block bg-indigo-100 text-indigo-800 text-xs font-semibold px-2.5 py-0.5 rounded">
                            {{ ucfirst($rec->algo) }}
                        </span>
                        </td>
                        <td class="px-4 py-2 text-center">
                        <span class="inline-block bg-green-100 text-green-800 text-xs font-semibold px-2 py-0.5 rounded-full">
                            {{ $conversionCount }}
                        </span>
                        </td>
                        <td class="px-4 py-2 text-center">
                            <form action="{{ route('recommendations.toggle', $rec->id) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <input type="checkbox" name="enabled" onchange="this.form.submit()" {{ $rec->enabled ? 'checked' : '' }}>
                            </form>
                        </td>
                        <td class="px-4 py-2 text-right">
                            <form action="{{ route('recommendations.delete', ['id' => $rec->id]) }}?shop={{ $shop->shopify_domain }}"
                                  method="POST"
                                  onsubmit="return confirm('Delete this recommendation?');"
                                  class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="text-red-600 hover:text-red-800 font-medium text-sm">
                                    🗑 Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="mt-4">
                {{ $recs->appends(request()->query())->links() }}
            </div>
        </div>
        @else
        <p class="text-gray-500 mt-4">No upsell recommendations set yet.</p>
        @endif
    </div>
</div>
@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    function searchForm() {
        return {
            search: '{{ request('search') }}',
            shop: '{{ $shop->shopify_domain }}',
            autoSubmit() {
                if (this.search.length === 0 || this.search.length >= 3) {
                    this.$root.submit(); // Corrected from $el to $root
                }
            },
            clear() {
                this.search                             = '';
                document.getElementById('search').value = '';
                this.$root.submit()
            }
        };
    }

    document.addEventListener('DOMContentLoaded', function () {
        $('.select2-product').select2({
            placeholder: 'Select product',
            width: 'resolve'
        });

        $('.select2-upsell').select2({
            placeholder: 'Select upsell',
            width: 'resolve'
        });
    });
</script>
@endpush
@endsection
