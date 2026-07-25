@extends('layouts.app')

@section('title', 'Bulk Add Products - Excel Grid')

@section('page-title')
    <i class="fas fa-file-excel text-indigo-600 mr-2"></i>Excel Spreadsheet Bulk Product Addition
@endsection

@section('content')
<style>
    .excel-table-header {
        background-color: #3730a3 !important; /* Deep Indigo Blue */
        color: #ffffff !important;
        font-weight: 800 !important;
        text-transform: uppercase !important;
        font-size: 11px !important;
        letter-spacing: 0.5px !important;
        padding: 10px 8px !important;
        border: 1px solid #4338ca !important;
    }
    .excel-btn-primary {
        background-color: #4f46e5 !important; /* Primary System Indigo Blue */
        color: #ffffff !important;
        font-weight: 800 !important;
        border: none !important;
        padding: 8px 18px !important;
        border-radius: 8px !important;
        cursor: pointer !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 6px !important;
        font-size: 12px !important;
        box-shadow: 0 2px 4px rgba(79, 70, 229, 0.2) !important;
    }
    .excel-btn-primary:hover {
        background-color: #4338ca !important;
    }
    .excel-btn-secondary {
        background-color: #e0e7ff !important; /* Soft Indigo Tint */
        color: #3730a3 !important;
        font-weight: 800 !important;
        border: 1px solid #c7d2fe !important;
        padding: 8px 16px !important;
        border-radius: 8px !important;
        cursor: pointer !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 6px !important;
        font-size: 12px !important;
    }
    .excel-btn-secondary:hover {
        background-color: #c7d2fe !important;
    }
    .excel-btn-slate {
        background-color: #f1f5f9 !important; /* Clean Light Slate */
        color: #334155 !important;
        font-weight: 700 !important;
        border: 1px solid #cbd5e1 !important;
        padding: 8px 16px !important;
        border-radius: 8px !important;
        cursor: pointer !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 6px !important;
        font-size: 12px !important;
    }
    .excel-btn-slate:hover {
        background-color: #e2e8f0 !important;
    }
    .excel-cell-input {
        background-color: #ffffff !important;
        color: #1e1b4b !important;
        font-weight: 700 !important;
        font-size: 12px !important;
        border: 1px solid #cbd5e1 !important;
        padding: 6px 8px !important;
        width: 100% !important;
        outline: none !important;
        border-radius: 4px !important;
    }
    .excel-cell-input:focus {
        background-color: #eef2ff !important; /* Soft Indigo Focus */
        border: 2px solid #4f46e5 !important;
        color: #1e1b4b !important;
    }
</style>

<div class="w-full">
    <div class="bg-white rounded-2xl shadow-xl p-4 md:p-6 border border-slate-200 space-y-4">
        
        <!-- Header & System Navigation -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3 pb-3 border-b border-slate-200">
            <div>
                <h2 class="text-xl font-extrabold text-indigo-950 flex items-center gap-2">
                    <i class="fas fa-table text-indigo-600"></i> Spreadsheet Product Entry Grid
                </h2>
                <p class="text-xs text-slate-600 mt-1 font-medium">
                    Navigate using <strong>4-Way Arrow Keys (<i class="fas fa-arrow-left text-[10px]"></i> <i class="fas fa-arrow-right text-[10px]"></i> <i class="fas fa-arrow-up text-[10px]"></i> <i class="fas fa-arrow-down text-[10px]"></i>)</strong> or press <kbd class="px-1.5 py-0.5 bg-indigo-100 text-indigo-900 border border-indigo-200 rounded font-mono text-[11px] font-bold">Enter</kbd> to jump straight down to the next row!
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('products.create') }}" class="excel-btn-secondary" style="text-decoration: none;">
                    <i class="fas fa-plus text-indigo-600"></i> Single Product Form
                </a>
                <a href="{{ route('products.index') }}" class="excel-btn-slate" style="text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Products List
                </a>
            </div>
        </div>

        <!-- Validation Error Banner -->
        @if ($errors->any())
            <div class="p-4 bg-red-50 border-l-4 border-red-600 rounded-r-lg text-red-900">
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
        <div class="flex flex-col sm:flex-row justify-between items-center gap-3 p-3 rounded-xl shadow" style="background-color: #1e1b4b; border: 1px solid #312e81;">
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" onclick="addRows(1)" class="excel-btn-secondary">
                    <i class="fas fa-plus"></i> +1 Row
                </button>
                <button type="button" onclick="addRows(5)" class="excel-btn-secondary">
                    <i class="fas fa-plus"></i> +5 Rows
                </button>
                <button type="button" onclick="addRows(10)" class="excel-btn-secondary">
                    <i class="fas fa-plus"></i> +10 Rows
                </button>
                <button type="button" onclick="clearEmptyRows()" class="excel-btn-slate" style="margin-left: 8px;">
                    <i class="fas fa-eraser text-indigo-600"></i> Clear Empty
                </button>
            </div>

            <div class="flex items-center gap-4">
                <div class="text-xs font-bold hidden sm:block" style="color: #ffffff;">
                    Total: <span id="summaryTotalItems" style="color: #a5b4fc; font-weight: 900; font-size: 14px;">0</span> items | 
                    Selling Value: <span id="summaryTotalSelling" style="color: #a5b4fc; font-weight: 900; font-size: 14px;">UGX 0</span>
                </div>

                <button type="submit" form="excelProductForm" class="excel-btn-primary" style="padding: 10px 24px; font-size: 13px;">
                    <i class="fas fa-save text-yellow-300 text-sm"></i>
                    <span>Save All Products</span>
                </button>
            </div>
        </div>

        <form action="{{ route('products.bulk-store') }}" method="POST" id="excelProductForm">
            @csrf

            <!-- Excel Grid Table -->
            <div class="overflow-x-auto shadow rounded-lg max-h-[70vh] overflow-y-auto" style="border: 2px solid #c7d2fe;">
                <table class="w-full border-collapse text-xs font-sans" style="background-color: #ffffff;">
                    <thead class="sticky top-0 z-10 select-none">
                        <tr>
                            <th class="excel-table-header text-center w-10" style="background-color: #1e1b4b !important;">#</th>
                            <th class="excel-table-header text-left min-w-[220px]">Product Name <span style="color: #f87171;">*</span></th>
                            <th class="excel-table-header text-left min-w-[160px]">Category</th>
                            <th class="excel-table-header text-left w-32">SKU</th>
                            <th class="excel-table-header text-left w-36">Barcode</th>
                            <th class="excel-table-header text-right w-32">Cost Price (UGX) <span style="color: #f87171;">*</span></th>
                            <th class="excel-table-header text-right w-32">Selling Price (UGX) <span style="color: #f87171;">*</span></th>
                            <th class="excel-table-header text-right w-24">Stock Qty <span style="color: #f87171;">*</span></th>
                            <th class="excel-table-header text-left w-24">Unit <span style="color: #f87171;">*</span></th>
                            <th class="excel-table-header text-center w-20">VAT 18%</th>
                            <th class="excel-table-header text-center w-10" style="background-color: #1e1b4b !important;"></th>
                        </tr>
                    </thead>
                    <tbody id="excelGridBody">
                        <!-- Spreadsheet rows dynamically rendered -->
                    </tbody>
                </table>
            </div>

            <!-- Footer Toolbar & Submit -->
            <div class="flex flex-col sm:flex-row justify-between items-center gap-3 pt-3 border-t border-slate-200">
                <div class="flex items-center gap-2 text-xs text-slate-700 font-extrabold">
                    <i class="fas fa-keyboard text-indigo-600 text-sm"></i>
                    <span>Use <strong>Enter</strong> to go down, <strong>Left/Right/Up/Down Arrows</strong> to jump between cells!</span>
                </div>

                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <button type="button" onclick="addRows(1)" class="excel-btn-secondary">
                        <i class="fas fa-plus text-indigo-600"></i> Add Row
                    </button>
                    <button type="submit" class="excel-btn-primary" style="padding: 10px 28px; font-size: 13px;">
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
        <tr id="row_${index}" style="border-bottom: 1px solid #cbd5e1;">
            <td style="padding: 4px; text-align: center; font-weight: 900; color: #3730a3; background-color: #e0e7ff; border-right: 1px solid #cbd5e1;" class="row-number select-none">${index + 1}</td>
            
            <!-- col 0: Product Name -->
            <td style="padding: 2px; border-right: 1px solid #cbd5e1;">
                <input type="text" name="products[${index}][name]" required
                       data-row="${index}" data-col="0"
                       placeholder="Product Name"
                       oninput="calculateSummaries()"
                       class="excel-cell excel-cell-input">
            </td>

            <!-- col 1: Category -->
            <td style="padding: 2px; border-right: 1px solid #cbd5e1;">
                <select name="products[${index}][category_id]" 
                        data-row="${index}" data-col="1"
                        class="excel-cell excel-cell-input">
                    ${categoryOptionsHTML}
                </select>
            </td>

            <!-- col 2: SKU -->
            <td style="padding: 2px; border-right: 1px solid #cbd5e1;">
                <input type="text" name="products[${index}][sku]" 
                       data-row="${index}" data-col="2"
                       placeholder="Auto SKU" 
                       class="excel-cell excel-cell-input" style="font-family: monospace;">
            </td>

            <!-- col 3: Barcode -->
            <td style="padding: 2px; border-right: 1px solid #cbd5e1;">
                <input type="text" name="products[${index}][barcode]" 
                       data-row="${index}" data-col="3"
                       placeholder="Barcode" 
                       class="excel-cell excel-cell-input" style="font-family: monospace; color: #312e81 !important;">
            </td>

            <!-- col 4: Cost Price -->
            <td style="padding: 2px; border-right: 1px solid #cbd5e1;">
                <input type="number" name="products[${index}][cost_price]" step="any" min="0" required value="0"
                       data-row="${index}" data-col="4"
                       oninput="calculateSummaries()"
                       class="excel-cell excel-cell-input" style="text-align: right;">
            </td>

            <!-- col 5: Selling Price -->
            <td style="padding: 2px; border-right: 1px solid #cbd5e1;">
                <input type="number" name="products[${index}][selling_price]" step="any" min="0" required value="0"
                       data-row="${index}" data-col="5"
                       oninput="calculateSummaries()"
                       class="excel-cell excel-cell-input" style="text-align: right; color: #3730a3 !important; font-weight: 900;">
            </td>

            <!-- col 6: Stock Qty -->
            <td style="padding: 2px; border-right: 1px solid #cbd5e1;">
                <input type="number" name="products[${index}][quantity]" step="any" min="0" required value="1"
                       data-row="${index}" data-col="6"
                       oninput="calculateSummaries()"
                       class="excel-cell excel-cell-input" style="text-align: right; font-weight: 900;">
            </td>

            <!-- col 7: Unit -->
            <td style="padding: 2px; border-right: 1px solid #cbd5e1;">
                <select name="products[${index}][unit]" 
                        data-row="${index}" data-col="7"
                        class="excel-cell excel-cell-input">
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
            <td style="padding: 4px; text-align: center; background-color: #f8fafc; border-right: 1px solid #cbd5e1;">
                <input type="checkbox" name="products[${index}][requires_vat]" value="1" checked 
                       data-row="${index}" data-col="8"
                       class="excel-cell" style="width: 18px; height: 18px; cursor: pointer; accent-color: #4f46e5;">
            </td>

            <!-- Action -->
            <td style="padding: 4px; text-align: center; background-color: #f8fafc;">
                <button type="button" onclick="removeRow(${index})" style="background: none; border: none; color: #dc2626; cursor: pointer; font-size: 14px;">
                    <i class="fas fa-trash-alt"></i>
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
