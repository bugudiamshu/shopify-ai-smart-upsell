@extends('layouts.app')

@section('content')
<div class="p-4">
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

    @if (session('success'))
    <div x-data="{ show: true }" x-show="show"
         class="relative flex items-center justify-between gap-4 mt-3 mb-3 p-4 rounded bg-green-100 text-green-800 border border-green-300 shadow">
        <span class="text-sm font-medium">{{ session('success') }}</span>
        <button @click="show = false"
                class="text-xl font-bold leading-none text-green-800 hover:text-green-900 focus:outline-none">
            &times;
        </button>
    </div>
    @endif

    <!-- Better aligned Sync button -->
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-md font-semibold text-white">Connected Shop:
            <span class="text-indigo-400">{{ $shop->shopify_domain }}</span>
        </h2>
        <form method="POST" action="{{ secure_url('shopify/sync-products') }}">
            @csrf
            <input type="hidden" name="shop" value="{{ $shop->shopify_domain }}">
            <button type="submit"
                    class="bg-green-600 hover:bg-green-700 active:bg-green-800 text-white px-4 py-1 font-semibold text-sm sm:text-base rounded transition">
                <span style="font-size: 24px">⟳</span> Sync Products from Store
            </button>
        </form>
    </div>

    <div class="w-full overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 bg-white rounded-lg overflow-hidden shadow">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($products as $product)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ $product->title }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $product->price }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $product->sku }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $product->status }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                        No products found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6 flex justify-center pb-6">
        {{ $products->links() }}
    </div>
</div>
@endsection
