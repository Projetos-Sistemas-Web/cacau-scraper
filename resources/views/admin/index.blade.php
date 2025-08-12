<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 leading-tight">
                    {{ __('Painel Administrativo') }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">Monitore os preços do cacau e logs do sistema</p>
            </div>
            <!-- Estatísticas Rápidas -->
            <div class="flex flex-row md:flex-row mb-8">
                <div class="bg-white overflow-hidden rounded-xl">
                    <div class="p-4">
                        <div class="flex items-center gap-3">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-600">Total de Registros</p>
                                <p class="text-xl font-bold text-gray-900">{{ $prices->total() }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden rounded-xl">
                    <div class="p-4">
                        <div class="flex items-center gap-3">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-600">Último Preço</p>
                                @if($prices->count() > 0)
                                    <p class="text-xl font-bold text-gray-900">R$ {{ number_format($prices->first()->price, 2, ',', '.') }}</p>
                                @else
                                    <p class="text-xl font-bold text-gray-400">--</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden rounded-xl">
                    <div class="p-4">
                        <div class="flex items-center gap-3">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-600">Última Atualização</p>
                                @if($prices->count() > 0)
                                    <p class="text-xl font-bold text-gray-900">{{ $prices->first()->date->format('d/m') }}</p>
                                @else
                                    <p class="text-xl font-bold text-gray-400">--</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status do Sistema -->
            <div class="flex items-center space-x-2 bg-emerald-50 px-4 py-2 rounded-lg border border-emerald-200">
                <div class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
                <span class="text-emerald-700 font-medium text-sm">Sistema Ativo</span>
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="{ showExportModal: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Tabela de Preços do Cacau -->
            <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 mb-8">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between ">
                        <div class="flex items-center space-x-5 gap-4">
                            <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center">
                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">Histórico de Preços do Cacau</h3>
                                <p class="text-sm text-gray-600">Últimos {{ $prices->count() }} registros de {{ $prices->total() }} no total</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3">
                            <button @click="showExportModal = true" class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Exportar CSV
                            </button>
                            <div class="text-xs text-gray-500 bg-gray-50 px-3 py-1 rounded-full">
                                Página {{ $prices->currentPage() }} de {{ $prices->lastPage() }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Data
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Preço (R$)
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Variação
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($prices as $index => $price)
                                @php
                                    $previousPrice = $index < $prices->count() - 1 ? $prices[$index + 1]->price : null;
                                    $variation = $previousPrice ? (($price->price - $previousPrice) / $previousPrice) * 100 : null;
                                @endphp
                                <tr class="hover:bg-gray-50 transition-colors duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-2 h-2 bg-emerald-400 rounded-full mr-3"></div>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ $price->date->format('d/m/Y') }}
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    {{ $price->date->format('l') }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-lg font-bold text-gray-900">
                                            R$ {{ number_format($price->price, 2, ',', '.') }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($variation !== null)
                                            <div class="flex items-center">
                                                @if($variation > 0)
                                                    <svg class="w-4 h-4 text-emerald-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path>
                                                    </svg>
                                                    <span class="text-emerald-600 font-medium text-sm">+{{ number_format($variation, 2) }}%</span>
                                                @elseif($variation < 0)
                                                    <svg class="w-4 h-4 text-red-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"></path>
                                                    </svg>
                                                    <span class="text-red-600 font-medium text-sm">{{ number_format($variation, 2) }}%</span>
                                                @else
                                                    <span class="text-gray-500 font-medium text-sm">0.00%</span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-gray-400 text-sm">--</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                            </svg>
                                            <h3 class="text-lg font-medium text-gray-900 mb-1">Nenhum dado encontrado</h3>
                                            <p class="text-gray-500">Não há registros de preços do cacau disponíveis.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($prices->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                Mostrando <span class="font-medium">{{ $prices->firstItem() }}</span> até
                                <span class="font-medium">{{ $prices->lastItem() }}</span> de
                                <span class="font-medium">{{ $prices->total() }}</span> resultados
                            </div>
                            <div class="flex-1 flex justify-center">
                                {{ $prices->links('pagination::tailwind') }}
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Logs do Sistema -->
            <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100" x-data="{ open: false }">
                <button @click="open = !open" class="w-full px-6 py-4 border-b border-gray-200 text-left hover:bg-gray-50 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-inset">
                    <div class="flex justify-between text-start">
                        <div class="flex gap-4">
                            <div class="w-10 h-10 bg-slate-100 rounded-xl flex items-center">
                                <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">Logs do Sistema</h3>
                                <p class="text-sm text-gray-600">Últimas 50 entradas do log do Laravel</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded-full" x-show="!open">
                                Clique para expandir
                            </span>
                            <svg class="w-5 h-5 text-gray-400 transform transition-transform duration-200"
                                 :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </div>
                </button>

                <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 transform scale-100" x-transition:leave-end="opacity-0 transform scale-95" class="p-6">
                    <div class="bg-slate-900 rounded-lg p-4 max-h-80 overflow-y-auto font-mono text-sm">
                        @forelse($logs as $line)
                            <div class="text-emerald-400 mb-1 hover:bg-slate-800 px-2 py-1 rounded transition-colors duration-150">
                                {{ $line }}
                            </div>
                        @empty
                            <div class="text-slate-400 text-center py-8">
                                <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <p>Nenhum log registrado</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Modal de Exportação CSV -->
            <div x-show="showExportModal"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="showExportModal = false"></div>

                    <div class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-xl">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-gray-900">Exportar dados para CSV</h3>
                            <button @click="showExportModal = false" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <form action="{{ route('admin.export.csv') }}" method="POST" class="space-y-4">
                            @csrf
                            <div>
                                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Data inicial</label>
                                <input type="date" id="start_date" name="start_date" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                            </div>

                            <div>
                                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">Data final</label>
                                <input type="date" id="end_date" name="end_date" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                            </div>

                            <div class="flex justify-end space-x-3 pt-4 gap-4">
                                <button type="button" @click="showExportModal = false"
                                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500">
                                    Cancelar
                                </button>
                                <button type="submit"
                                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500">
                                    Baixar CSV
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
