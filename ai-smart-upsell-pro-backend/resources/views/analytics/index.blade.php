@extends('layouts.app')

@section('content')
<h1>Analytics for {{ $shop->shopify_domain }}</h1>

<table border="1" cellpadding="8" cellspacing="0" style="border-collapse: collapse; width: 100%;">
    <thead>
        <tr>
            <th>Product</th>
            <th>Upsell Recommendation</th>
            <th>Algorithm</th>
            <th>Conversions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($recommendations as $rec)
        <tr>
            <td>{{ optional($rec->shop->products->firstWhere('shopify_product_id', $rec->product_id))->title ?? $rec->product_id }}</td>
            <td>{{ optional($rec->shop->products->firstWhere('shopify_product_id', $rec->recommended_product_id))->title ?? $rec->recommended_product_id }}</td>
            <td>{{ $rec->algo }}</td>
            <td>{{ $rec->conversions_count }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

{{ $recommendations->links() }}

@endsection
