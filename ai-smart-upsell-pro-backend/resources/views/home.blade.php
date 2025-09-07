@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-semibold mb-6">Upsell Recommendations for {{ $shop->shopify_domain }}</h1>

    @if(session('success'))
    <div class="mb-4 px-4 py-3 text-green-800 bg-green-100 border border-green-200 rounded">
        {{ session('success') }}
    </div>
    @endif

    <!-- ADD new recommendation button -->
    <button id="openAddModal" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded mb-4">Add Recommendation</button>

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-300 rounded shadow">
            <thead>
                <tr class="bg-gray-100">
                    <th class="py-3 px-6 border-b border-gray-300 text-left">Main Product</th>
                    <th class="py-3 px-6 border-b border-gray-300 text-left">Upsell Product</th>
                    <th class="py-3 px-6 border-b border-gray-300 text-left">Source</th>
                    <th class="py-3 px-6 border-b border-gray-300 text-left">Conversions</th>
                    <th class="py-3 px-6 border-b border-gray-300 text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($recommendations as $rec)
                <tr class="hover:bg-gray-50">
                    <td class="py-3 px-6 border-b border-gray-300">
                        {{ $products[$rec->product_id] ?? $rec->product_id }}
                    </td>
                    <td class="py-3 px-6 border-b border-gray-300">
                        {{ $products[$rec->recommended_product_id] ?? $rec->recommended_product_id }}
                    </td>
                    <td class="py-3 px-6 border-b border-gray-300 capitalize">
                        {{ $rec->algo }}
                    </td>
                    <td class="py-3 px-6 border-b border-gray-300 text-green-600">
                        {{ isset($conversions[$rec->id]) ? $conversions[$rec->id]->count() : 0 }}
                    </td>
                    <td class="py-3 px-6 border-b border-gray-300 text-center">
                        <form action="{{ route('recommendations.delete', ['id' => $rec->id]) }}?shop={{ $shop->shopify_domain }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this recommendation?')" class="inline-block mr-2">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline">Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Add Recommendation Modal -->
    <div id="addModal" class="fixed inset-0 bg-black bg-opacity-50 hidden justify-center items-center">
        <div class="bg-white p-6 rounded shadow-lg w-full max-w-md">
            <h2 class="text-xl font-semibold mb-4">Add New Recommendation</h2>

            <form action="{{ route('recommendations.create') }}?shop={{ $shop->shopify_domain }}" method="POST" id="addRecommendationForm">
                @csrf
                <div class="mb-4">
                    <label for="product_id" class="block mb-1 font-medium">Main product</label>
                    <select name="product_id" id="product_id" required class="w-full border border-gray-300 px-3 py-2 rounded">
                        @foreach ($products as $id => $title)
                        <option value="{{ $id }}">{{ $title }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label for="recommended_product_id" class="block mb-1 font-medium">Recommended product</label>
                    <select name="recommended_product_id" id="recommended_product_id" required class="w-full border border-gray-300 px-3 py-2 rounded">
                        @foreach ($products as $id => $title)
                        <option value="{{ $id }}">{{ $title }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">Add Recommendation</button>
                <button type="button" id="closeAddModal" class="ml-4 px-4 py-2 rounded border border-gray-300 hover:bg-gray-100">Cancel</button>
            </form>
        </div>
    </div>
</div>

<script>
    // Show Add Recommendation modal
    document.getElementById('openAddModal').addEventListener('click', () => {
        document.getElementById('addModal').classList.remove('hidden');
        document.getElementById('addModal').classList.add('flex');
    });

    // Hide Add Recommendation modal
    document.getElementById('closeAddModal').addEventListener('click', () => {
        document.getElementById('addModal').classList.remove('flex');
        document.getElementById('addModal').classList.add('hidden');
    });

    // Prevent selecting same product for both Main and Recommended
    document.getElementById('product_id').addEventListener('change', function() {
        const recommendedSelect = document.getElementById('recommended_product_id');
        for (let i = 0; i < recommendedSelect.options.length; i++) {
            recommendedSelect.options[i].disabled = false;
            if (recommendedSelect.options[i].value === this.value) {
                recommendedSelect.options[i].disabled = true;
            }
        }
    });
</script>
@endsection
