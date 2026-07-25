@extends('layouts.app')

@section('title', 'Bulk Add Products - Excel Grid')

@section('page-title')
    <i class="fas fa-file-excel text-emerald-600 mr-2"></i>Excel Spreadsheet Bulk Product Addition
@endsection

@section('content')
<div class="w-full">
    <div class="bg-white rounded-2xl shadow-xl p-4 md:p-6 border border-slate-300 space-y-4">
        
        <!-- Header & Navigation -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3 pb-3 border-b border-slate-200">
            <div>
                <h2 class="text-xl font-extrabold text-slate-900 flex items-center gap-2">
                    <i class="fas fa-table text-emerald-600"></i> Excel Grid Product Entry
                </h2>
                <p class="text-xs text-slate-600 mt-1 font-medium">
                    Navigate using <strong>4-Way Arrow Keys (<i class="fas fa-arrow-left text-[10px]"></i> <i class="fas fa-arrow-right text-[10px]"></i> <i class="fas fa-arrow-up text-[10px]"></i> <i class="fas fa-arrow-down text-[10px]"></i>)</strong> or press <kbd class="px-1.5 py-0.5 bg-slate-800 text-white rounded font-mono text-[11px] font-bold">Enter</kbd> to jump straight down to the next row!
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('products.create') }}" class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold rounded-lg text-xs shadow transition flex items-center gap-1.5">
                    <i class="fas fa-plus text-yellow-300"></i> Single Product Form
                </a>
                <a href="{{ route('products.index') }}" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white font-extrabold rounded-lg text-xs shadow transition flex items-center gap-1.5">
                    <i class="fas fa-arrow-left"></i> Products List
                </a>
            </div>
        </div>

        <!-- Validation Error Banner -->
        @if ($errors->any())
            <div class="p-4 bg-red-100 border-l-4 border-red-600 rounded-r-lg text-red-900">
                <div class="flex items-start">
                    <i class="fas fa-exclamation-circle text-red-600 mt-0.5 mr-2.5 text-lg"></i>
                    <div>
                        <h3 class="font-extrabold text-xs uppercase tracking-wide">Validation Errors Found:</h3>
                        <ul class="list-disc list-inside space-y-0.5 text-xs font-semibold mt-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <!-- Spreadsheet Toolbar -->
        <div class="flex flex-col sm:flex-row justify-between items-center gap-3 bg-slate-900 p-3 rounded-xl shadow">
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" onclick="addRows(1)" class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold rounded text-xs shadow flex items-center gap-1">
                    <i class="fas fa-plus"></i> +1 Row
                </button>
                <button type="button" onclick="addRows(5)" class="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white font-extrabold rounded text-xs shadow flex items-center gap-1">
                    <i class="fas fa-plus"></i> +5 Rows
                </button>
                <button type="button" onclick="addRows(10)" class="px-3.5 py-1.5 bg-purple-600 hover:bg-purple-500 text-white font-extrabold rounded text-xs shadow flex items-center gap-1">
                    <i class="fas fa-plus"></i> +10 Rows
                </button>
                <button type="button" onclick="clearEmptyRows()" class="px-3.5 py-1.5 bg-slate-700 hover:bg-slate-600 text-white font-extrabold rounded text-xs shadow flex items-center gap-1 ml-2">
                    <i class="fas fa-eraser text-yellow-300"></i> Clear Empty
                </button>
            </div>

            <div class="flex items-center gap-4">
                <div class="text-xs text-white font-bold hidden sm:block">
                    Total: <span id="summaryTotalItems" class="text-emerald-400 font-extrabold text-sm">0</span> items | 
                    Selling Value: <span id="summaryTotalSelling" class="text-emerald-400 font-extrabold text-sm">UGX 0</span>
                </div>

                <button type="submit" form="excelProductForm" class="px-6 py-2 bg-emerald-500 hover:bg-emerald-600 text-white font-black rounded-lg shadow-lg transition flex items-center gap-2 text-xs">
                    <i class="fas fa-save text-yellow-300 text-sm"></i>
                    <span>Save All Products</span>
                </button>
            </div>
        </div>

        <form action="{{ route('products.bulk-store') }}" method="POST" id="excelProductForm">
            @csrf

            <!-- Excel Grid Table -->
            <div class="overflow-x-auto border-2 border-slate-300 shadow rounded-lg max-h-[70vh] overflow-y-auto">
                <table class="w-full border-collapse border border-slate-300 text-xs bg-white font-sans">
                    <thead class="bg-slate-900 text-white sticky top-0 z-10 select-none">
                        <tr class="divide-x divide-slate-700">
                            <th class="p-2 text-center w-10 font-black bg-slate-950">#</th>
                            <th class="p-2 text-left font-black min-w-[220px]">Product Name <span class="text-red-400">*</span></th>
                            <th class="p-2 text-left font-black min-w-[160px]">Category</th>
                            <th class="p-2 text-left font-black w-32">SKU</th>
                            <th class="p-2 text-left font-black w-36">Barcode</th>
                            <th class="p-2 text-right font-black w-32">Cost Price (UGX) <span class="text-red-400">*</span></th>
                            <th class="p-2 text-right font-black w-32">Selling Price (UGX) <span class="text-red-400">*</span></th>
                            <th class="p-2 text-right font-black w-24">Stock Qty <span class="text-red-400">*</span></th>
                            <th class="p-2 text-left font-black w-24">Unit <span class="text-red-400">*</span></th>
                            <th class="p-2 text-center font-black w-20">VAT 18%</th>
                            <th class="p-2 text-center font-black w-10 bg-slate-950"></th>
                        </tr>
                    </thead>
                    <tbody id="excelGridBody" class="divide-y divide-slate-300">
                        <!-- Spreadsheet rows dynamically rendered -->
                    </tbody>
                </table>
            </div>

            <!-- Footer Toolbar & Submit -->
            <div class="flex flex-col sm:flex-row justify-between items-center gap-3 pt-3 border-t border-slate-200">
                <div class="flex items-center gap-2 text-xs text-slate-700 font-extrabold">
                    <i class="fas fa-keyboard text-emerald-600 text-sm"></i>
                    <span>Use <strong>Enter</strong> to go down, <strong>Left/Right/Up/Down Arrows</strong> to jump between cells!</span>
                </div>

                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <button type="button" onclick="addRows(1)" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 border border-slate-400 font-extrabold rounded-lg text-xs text-slate-900 transition flex items-center gap-1.5">
                        <i class="fas fa-plus text-emerald-700"></i> Add Row
                    </button>
                    <button type="submit" class="px-8 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-black rounded-lg shadow-lg transition flex items-center justify-center gap-2 text-xs flex-1 sm:flex-none">
                        <i class="fas fa-check-circle text-yellow-300 text-sm"></i>
                        <span>Save All Products</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
let rowIndex = 0;
const categoriesList = [
    @foreach($categories as $cat)
        { id: {{ $cat->id }}, name: '{{ addslashes($cat->name) }}' },
    @endforeach
];

function createRowHTML(index) {
    let categoryOptionsHTML = '<option value="">-- Select Category --</option>';
    categoriesList.forEach(cat => {
        categoryOptionsHTML += `<option value="${cat.id}">${cat.name}</option>`;
    });

    return `
        <tr id="row_${index}" class="divide-x divide-slate-300 hover:bg-emerald-50 transition">
            <td class="p-1 text-center font-black text-slate-700 bg-slate-200 select-none row-number">${index + 1}</td>
            
            <!-- col 0: Product Name -->
            <td class="p-0">
                <input type="text" name="products[${index}][name]" required
                       data-row="${index}" data-col="0"
                       placeholder="Product Name"
                       oninput="calculateSummaries()"
                       class="excel-cell w-full px-2.5 py-2 bg-white border-0 focus:bg-yellow-100 focus:ring-2 focus:ring-emerald-600 text-xs font-bold text-slate-900 outline-none">
            </td>

            <!-- col 1: Category -->
            <td class="p-0">
                <select name="products[${index}][category_id]" 
                        data-row="${index}" data-col="1"
                        class="excel-cell w-full px-2 py-2 bg-white border-0 focus:bg-yellow-100 focus:ring-2 focus:ring-emerald-600 text-xs font-bold text-slate-900 outline-none cursor-pointer">
                    ${categoryOptionsHTML}
                </select>
            </td>

            <!-- col 2: SKU -->
            <td class="p-0">
                <input type="text" name="products[${index}][sku]" 
                       data-row="${index}" data-col="2"
                       placeholder="Auto SKU" 
                       class="excel-cell w-full px-2.5 py-2 bg-white border-0 focus:bg-yellow-100 focus:ring-2 focus:ring-emerald-600 text-xs font-mono font-bold text-slate-700 outline-none">
            </td>

            <!-- col 3: Barcode -->
            <td class="p-0">
                <input type="text" name="products[${index}][barcode]" 
                       data-row="${index}" data-col="3"
                       placeholder="Barcode" 
                       class="excel-cell w-full px-2.5 py-2 bg-white border-0 focus:bg-yellow-100 focus:ring-2 focus:ring-emerald-600 text-xs font-mono font-bold text-indigo-900 outline-none">
            </td>

            <!-- col 4: Cost Price -->
            <td class="p-0">
                <input type="number" name="products[${index}][cost_price]" step="any" min="0" required value="0"
                       data-row="${index}" data-col="4"
                       oninput="calculateSummaries()"
                       class="excel-cell w-full px-2.5 py-2 bg-white border-0 focus:bg-yellow-100 focus:ring-2 focus:ring-emerald-600 text-xs text-right font-black text-slate-900 outline-none">
            </td>

            <!-- col 5: Selling Price -->
            <td class="p-0">
                <input type="number" name="products[${index}][selling_price]" step="any" min="0" required value="0"
                       data-row="${index}" data-col="5"
                       oninput="calculateSummaries()"
                       class="excel-cell w-full px-2.5 py-2 bg-white border-0 focus:bg-yellow-100 focus:ring-2 focus:ring-emerald-600 text-xs text-right font-black text-emerald-900 outline-none">
            </td>

            <!-- col 6: Stock Qty -->
            <td class="p-0">
                <input type="number" name="products[${index}][quantity]" step="any" min="0" required value="1"
                       data-row="${index}" data-col="6"
                       oninput="calculateSummaries()"
                       class="excel-cell w-full px-2 py-2 bg-white border-0 focus:bg-yellow-100 focus:ring-2 focus:ring-emerald-600 text-xs text-right font-black text-slate-900 outline-none">
            </td>

            <!-- col 7: Unit -->
            <td class="p-0">
                <select name="products[${index}][unit]" 
                        data-row="${index}" data-col="7"
                        class="excel-cell w-full px-2 py-2 bg-white border-0 focus:bg-yellow-100 focus:ring-2 focus:ring-emerald-600 text-xs font-bold text-slate-900 outline-none cursor-pointer">
                    <option value="pcs">pcs</option>
                    <option value="kg">kg</option>
                    <option value="ltr">ltr</option>
                    <option value="box">box</option>
                    <option value="pack">pack</option>
                    <option value="bottle">bottle</option>
                    <option value="unit">unit</option>
                </select>
            </td>

            <!-- col 8: VAT Toggle -->
            <td class="p-1.5 text-center bg-slate-100">
                <input type="checkbox" name="products[${index}][requires_vat]" value="1" checked 
                       data-row="${index}" data-col="8"
                       class="excel-cell w-4 h-4 text-emerald-600 rounded border-slate-400 focus:ring-emerald-600 cursor-pointer">
            </td>

            <!-- Action -->
            <td class="p-1.5 text-center bg-slate-100">
                <button type="button" onclick="removeRow(${index})" class="text-slate-500 hover:text-red-700 transition">
                    <i class="fas fa-trash-alt text-xs"></i>
                </button>
            </td>
        </tr>
    `;
}

function addRows(count = 1) {
    const tbody = document.getElementById('excelGridBody');
    for (let i = 0; i < count; i++) {
        tbody.insertAdjacentHTML('beforeend', createRowHTML(rowIndex));
        rowIndex++;
    }
    renumberRows();
    calculateSummaries();
}

function removeRow(index) {
    const row = document.getElementById(`row_${index}`);
    if (row) {
        row.remove();
        renumberRows();
        calculateSummaries();
    }
}

function renumberRows() {
    const rows = document.querySelectorAll('#excelGridBody tr');
    rows.forEach((tr, idx) => {
        const numTd = tr.querySelector('.row-number');
        if (numTd) numTd.textContent = idx + 1;
    });
}

function clearEmptyRows() {
    const rows = document.querySelectorAll('#excelGridBody tr');
    rows.forEach(tr => {
        const nameInput = tr.querySelector('input[name*="[name]"]');
        if (nameInput && !nameInput.value.trim()) {
            tr.remove();
        }
    });
    renumberRows();
    calculateSummaries();
}

function calculateSummaries() {
    const rows = document.querySelectorAll('#excelGridBody tr');
    let totalItems = 0;
    let totalSelling = 0;

    rows.forEach(tr => {
        const nameInput = tr.querySelector('input[name*="[name]"]');
        const sellingInput = tr.querySelector('input[name*="[selling_price]"]');
        const qtyInput = tr.querySelector('input[name*="[quantity]"]');

        const name = nameInput ? nameInput.value.trim() : '';
        const selling = sellingInput ? parseFloat(sellingInput.value) || 0 : 0;
        const qty = qtyInput ? parseFloat(qtyInput.value) || 0 : 0;

        if (name !== '') {
            totalItems++;
            totalSelling += (selling * qty);
        }
    });

    document.getElementById('summaryTotalItems').textContent = totalItems.toLocaleString();
    document.getElementById('summaryTotalSelling').textContent = 'UGX ' + totalSelling.toLocaleString();
}

function focusCell(r, c) {
    const el = document.querySelector(`.excel-cell[data-row="${r}"][data-col="${c}"]`);
    if (el) {
        el.focus();
        if (typeof el.select === 'function' && el.tagName === 'INPUT' && el.type !== 'checkbox') {
            el.select();
        }
        return true;
    }
    return false;
}

// 4-Way Arrow Keys & Enter Key Spreadsheet Navigation
document.addEventListener('DOMContentLoaded', function() {
    addRows(8);

    const tbody = document.getElementById('excelGridBody');

    tbody.addEventListener('keydown', function(e) {
        const target = e.target;
        if (!target.classList.contains('excel-cell')) return;

        const row = parseInt(target.dataset.row);
        const col = parseInt(target.dataset.col);

        // Enter key: jump DOWN to same column in next row
        if (e.key === 'Enter') {
            e.preventDefault();
            if (!focusCell(row + 1, col)) {
                addRows(1);
                setTimeout(() => {
                    focusCell(row + 1, col);
                }, 40);
            }
        } else if (e.key === 'ArrowDown') {
            e.preventDefault();
            focusCell(row + 1, col);
        } else if (e.key === 'ArrowUp') {
            if (row > 0) {
                e.preventDefault();
                focusCell(row - 1, col);
            }
        } else if (e.key === 'ArrowRight') {
            if (target.tagName === 'SELECT' || target.type === 'checkbox' || target.selectionStart === undefined || target.selectionStart === target.value.length) {
                if (col < 8) {
                    e.preventDefault();
                    focusCell(row, col + 1);
                }
            }
        } else if (e.key === 'ArrowLeft') {
            if (target.tagName === 'SELECT' || target.type === 'checkbox' || target.selectionStart === undefined || target.selectionStart === 0) {
                if (col > 0) {
                    e.preventDefault();
                    focusCell(row, col - 1);
                }
            }
        }
    });
});
</script>
@endsection
