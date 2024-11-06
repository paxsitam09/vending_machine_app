<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Product Info') }}
        </h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    <div class="container mx-auto px-4">
                        <h1 class="text-2xl font-bold mb-6">Product Information</h1>
                        <table class="min-w-full bg-white border border-gray-300">
                            <tbody class="divide-y divide-gray-200">
                                <tr>
                                    <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">ID</th>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $product->id }}</td>
                                </tr>
                                <tr>
                                    <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">Name</th>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $product->name }}</td>
                                </tr>
                                <tr>
                                    <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">Price</th>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ rtrim(rtrim(number_format($product->price, 3), '0'), '.') }} USD</td>
                                </tr>
                                <tr>
                                    <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">Quantity</th>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $product->quantity }}</td>
                                </tr>
                                <tr>
                                    <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">Total</th>
                                    <td class="px-6 py-4 text-sm text-gray-900" id="totalCost">{{ rtrim(rtrim(number_format($product->price, 3), '0'), '.') }}</td>
                                </tr>
                                <tr>
                                    <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">Actions</th>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        @if(Auth::user()->role === 'admin')
                                            <a href="{{ route('products.edit', $product->id) }}" class="inline-flex items-center px-4 py-2 bg-yellow-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">Edit</a>
                                            <form action="{{ route('products.destroy', $product->id) }}" method="POST" class="inline-block ml-2">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">Delete</button>
                                            </form>
                                        @endif
                                        @if(Auth::user()->role === 'user')
                                            <form action="{{ route('products.transaction', $product->id) }}" method="POST" class="inline-block ml-2">
                                                @csrf
                                                <input type="number" name="quantity" value="1" min="1" max="{{ $product->quantity }}" required class="w-16 px-2 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" onchange="calculateTotal()" id="quantityInput">
                                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">Purchase</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>

                            </tbody>
                        </table>
                    </div>

                    <script>
                        function calculateTotal() {
                            const quantity = document.getElementById('quantityInput').value;
                            const price = {{ $product->price }};
                            const total = quantity * price;
                            document.getElementById('totalCost').innerText = parseFloat(total.toFixed(3)) + ' USD';
                        }
                    </script>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
