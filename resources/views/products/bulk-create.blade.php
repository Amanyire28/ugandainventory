@extends('layouts.app')

@section('title', 'Bulk Add Products - Excel Grid')

@section('page-title')
    <i class="fas fa-file-excel text-blue-600 mr-2"></i>Excel Spreadsheet Bulk Product Addition
@endsection

@section('content')
<style>
    .excel-table-header {
        background-color: #dbeafe !important; /* Soft Light Blue */
        color: #1e40af !important; /* Deep Blue Text */
        font-weight: 800 !important;
        text-transform: uppercase !important;
        font-size: 11px !important;
        letter-spacing: 0.5px !important;
        padding: 10px 8px !important;
        border: 1px solid #bfdbfe !important;
    }
    .excel-btn-primary {
        background-color: #2563eb !important; /* Soft Blue Primary */
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
        box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2) !important;
    }
    .excel-btn-primary:hover {
        background-color: #1d4ed8 !important;
    }
    .excel-btn-secondary {
        background-color: #eff6ff !important; /* Softest Light Blue Tint */
        color: #1d4ed8 !important;
        font-weight: 800 !important;
        border: 1px solid #bfdbfe !important;
        padding: 8px 16px !important;
        border-radius: 8px !important;
        cursor: pointer !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 6px !important;
        font-size: 12px !important;
    }
    .excel-btn-secondary:hover {
        background-color: #dbeafe !important;
    }
    .excel-btn-slate {
        background-color: #f8fafc !important; /* Clean Soft Light Slate */
        color: #475569 !important;
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
        background-color: #f1f5f9 !important;
    }
    .excel-cell-input {
        background-color: #ffffff !important;
        color: #1e3a8a !important;
        font-weight: 700 !important;
        font-size: 12px !important;
        border: 1px solid #cbd5e1 !important;
        padding: 6px 8px !important;
        width: 100% !important;
        outline: none !important;
        border-radius: 4px !important;
    }
    .excel-cell-input:focus {
        background-color: #eff6ff !important; /* Light Blue Focus */
        border: 2px solid #2563eb !important;
        color: #1e3a8a !important;
    }
</style>

<div class="w-full">
    <div class="bg-white rounded-2xl shadow-xl p-4 md:p-6 border border-slate-200 space-y-4">
        
        <!-- Header & System Navigation -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3 pb-3 border-b border-slate-200">
            <div>
                <h2 class="text-xl font-extrabold text-blue-950 flex items-center gap-2">
                    <i class="fas fa-table text-blue-600"></i> Spreadsheet Product Entry Grid
                </h2>
                <p class="text-xs text-slate-600 mt-1 font-medium">
                    Navigate using <strong>4-Way Arrow Keys (<i class="fas fa-arrow-left text-[10px]"></i> <i class="fas fa-arrow-right text-[10px]"></i> <i class="fas fa-arrow-up text-[10px]"></i> <i class="fas fa-arrow-down text-[10px]"></i>)</strong> or press <kbd class="px-1.5 py-0.5 bg-blue-50 text-blue-900 border border-blue-200 rounded font-mono text-[11px] font-bold">Enter</kbd> to jump straight down to the next row!
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('products.create') }}" class="excel-btn-secondary" style="text-decoration: none;">
                    <i class="fas fa-plus text-blue-600"></i> Single Product Form
                </a>
                <a href="{{ route('products.index') }}" class="excel-btn-slate" style="text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Products List
                </a>
            </div>
        </div>

        <!-- Validation Error Banner -->
        @if ($errors->any())
            <div class="p-4 bg-red-50 border-l-4 border-red-500 rounded-r-lg text-red-900">
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

        <!-- Light Blue Spreadsheet Toolbar (No dark backgrounds) -->
        <div class="flex flex-col sm:flex-row justify-between items-center gap-3 p-3 rounded-xl border border-blue-200" style="background-color: #eff6ff;">
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
                    <i class="fas fa-eraser text-blue-600"></i> Clear Empty
                </button>
            </div>

            <div class="flex items-center gap-4">
                <div class="text-xs font-bold hidden sm:block" style="color: #1e3a8a;">
                    Total: <span id="summaryTotalItems" style="color: #2563eb; font-weight: 900; font-size: 14px;">0</span> items | 
                    Selling Value: <span id="summaryTotalSelling" style="color: #2563eb; font-weight: 900; font-size: 14px;">UGX 0</span>
                </div>

                <button type="button" id="saveAllBtnTop" onclick="submitBulkForm()" class="excel-btn-primary" style="padding: 9px 24px; font-size: 13px;">
                    <i class="fas fa-save text-yellow-300 text-sm" id="saveIconTop"></i>
                    <span id="saveLabelTop">Save All Products</span>
                </button>
            </div>
        </div>

        <form action="{{ route('products.bulk-store') }}" method="POST" id="excelProductForm">
            @csrf

            <!-- Light Blue Excel Grid Table -->
            <div class="overflow-x-auto shadow rounded-lg max-h-[70vh] overflow-y-auto" style="border: 1.5px solid #bfdbfe;">
                <table class="w-full border-collapse text-xs font-sans" style="background-color: #ffffff;">
                    <thead class="sticky top-0 z-10 select-none">
                        <tr>
                            <th class="excel-table-header text-center w-10">#</th>
                            <th class="excel-table-header text-left min-w-[220px]">Product Name <span style="color: #dc2626;">*</span></th>
                            <th class="excel-table-header text-left min-w-[160px]">Category</th>
                            <th class="excel-table-header text-left w-32">SKU</th>
                            <th class="excel-table-header text-left w-36">Barcode</th>
                            <th class="excel-table-header text-right w-32">Cost Price (UGX) <span style="color: #dc2626;">*</span></th>
                            <th class="excel-table-header text-right w-32">Selling Price (UGX) <span style="color: #dc2626;">*</span></th>
                            <th class="excel-table-header text-right w-24">Stock Qty <span style="color: #dc2626;">*</span></th>
                            <th class="excel-table-header text-left w-24">Unit <span style="color: #dc2626;">*</span></th>
                            <th class="excel-table-header text-center w-20">VAT 18%</th>
                            <th class="excel-table-header text-center w-10"></th>
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
                    <i class="fas fa-keyboard text-blue-600 text-sm"></i>
                    <span>Use <strong>Enter</strong> to go down, <strong>Left/Right/Up/Down Arrows</strong> to jump between cells!</span>
                </div>

                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <button type="button" onclick="addRows(1)" class="excel-btn-secondary">
                        <i class="fas fa-plus text-blue-600"></i> Add Row
                    </button>
                    <button type="button" id="saveAllBtnBottom" onclick="submitBulkForm()" class="excel-btn-primary" style="padding: 9px 28px; font-size: 13px;">
                        <i class="fas fa-check-circle text-yellow-300 text-sm" id="saveIconBottom"></i>
                        <span id="saveLabelBottom">Save All Products</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Spinner Overlay --}}
<div id="bulkSaveOverlay" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.65); z-index:9999; align-items:center; justify-content:center; flex-direction:column; gap:16px;">
    <div style="background:#fff; border-radius:16px; padding:36px 48px; text-align:center; box-shadow:0 20px 60px rgba(0,0,0,0.3);">
        <div style="display:flex; justify-content:center; margin-bottom:16px;">
            <svg width="52" height="52" viewBox="0 0 52 52" style="animation:spin 0.9s linear infinite;">
                <circle cx="26" cy="26" r="22" fill="none" stroke="#dbeafe" stroke-width="5"/>
                <path d="M26 4 a22 22 0 0 1 22 22" fill="none" stroke="#2563eb" stroke-width="5" stroke-linecap="round"/>
            </svg>
        </div>
        <p style="font-size:16px; font-weight:800; color:#1e3a8a; margin:0 0 4px;">Saving Products…</p>
        <p style="font-size:13px; color:#64748b; font-weight:600; margin:0;">Please wait while your products are being added.</p>
    </div>
</div>

{{-- Toast Notification --}}
<div id="bulkToast" style="display:none; position:fixed; bottom:28px; right:28px; z-index:10000; min-width:280px; max-width:380px;">
    <div id="bulkToastInner" style="display:flex; align-items:flex-start; gap:14px; background:#fff; border-radius:14px; padding:18px 20px; box-shadow:0 8px 32px rgba(0,0,0,0.18); border-left:5px solid #16a34a;">
        <div id="bulkToastIcon" style="font-size:26px; line-height:1;">🎉</div>
        <div style="flex:1;">
            <p id="bulkToastTitle" style="font-size:14px; font-weight:800; color:#14532d; margin:0 0 3px;">Products Added!</p>
            <p id="bulkToastMsg" style="font-size:13px; color:#166534; font-weight:600; margin:0;"></p>
        </div>
        <button onclick="closeToast()" style="background:none;border:none;cursor:pointer;font-size:18px;color:#94a3b8;line-height:1;padding:0;">×</button>
    </div>
</div>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
#bulkSaveOverlay { display: none; }
#bulkSaveOverlay.active { display: flex !important; }
#bulkToast { display: none; }
#bulkToast.active { display: block !important; animation: toastSlideIn 0.35s cubic-bezier(.22,1,.36,1); }
@keyframes toastSlideIn { from { opacity:0; transform:translateY(30px); } to { opacity:1; transform:translateY(0); } }
</style>

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
        <tr id="row_${index}" style="border-bottom: 1px solid #cbd5e1;" class="hover:bg-blue-50/50 transition">
            <td style="padding: 4px; text-align: center; font-weight: 900; color: #1e40af; background-color: #eff6ff; border-right: 1px solid #cbd5e1;" class="row-number select-none">${index + 1}</td>
            
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
                       class="excel-cell excel-cell-input" style="font-family: monospace; color: #1d4ed8 !important;">
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
                       class="excel-cell excel-cell-input" style="text-align: right; color: #1d4ed8 !important; font-weight: 900;">
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
                       class="excel-cell" style="width: 18px; height: 18px; cursor: pointer; accent-color: #2563eb;">
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

// ─── AJAX Bulk Submit ────────────────────────────────────────────────────────
function setSavingState(isSaving) {
    const overlay = document.getElementById('bulkSaveOverlay');
    const btns = [document.getElementById('saveAllBtnTop'), document.getElementById('saveAllBtnBottom')];
    if (isSaving) {
        overlay.classList.add('active');
        btns.forEach(btn => { if(btn) btn.disabled = true; });
    } else {
        overlay.classList.remove('active');
        btns.forEach(btn => { if(btn) btn.disabled = false; });
    }
}

function showToast(count, isError = false) {
    const toast = document.getElementById('bulkToast');
    const inner = document.getElementById('bulkToastInner');
    const icon  = document.getElementById('bulkToastIcon');
    const title = document.getElementById('bulkToastTitle');
    const msg   = document.getElementById('bulkToastMsg');

    if (isError) {
        inner.style.borderLeftColor = '#dc2626';
        icon.textContent = '❌';
        title.style.color = '#7f1d1d';
        title.textContent = 'Error!';
        msg.style.color   = '#991b1b';
        msg.textContent   = count; // count holds error message when isError
    } else {
        inner.style.borderLeftColor = '#16a34a';
        icon.textContent = '🎉';
        title.style.color = '#14532d';
        title.textContent = count === 1 ? '1 Product Added!' : `${count} Products Added!`;
        msg.style.color   = '#166534';
        msg.textContent   = count === 1
            ? 'Your product has been saved successfully.'
            : `All ${count} products have been saved successfully.`;
    }

    toast.classList.remove('active');
    void toast.offsetWidth; // reflow to restart animation
    toast.classList.add('active');

    setTimeout(() => closeToast(), 6000);
}

function closeToast() {
    document.getElementById('bulkToast').classList.remove('active');
}

function submitBulkForm() {
    const form = document.getElementById('excelProductForm');

    // Basic validation: must have at least one row with a name
    const filledRows = Array.from(document.querySelectorAll('#excelGridBody input[name*="[name]"]'))
        .filter(inp => inp.value.trim() !== '');

    if (filledRows.length === 0) {
        showToast('Please fill in at least one product name before saving.', true);
        return;
    }

    // Check HTML5 validity
    if (!form.reportValidity()) return;

    const formData   = new FormData(form);
    const csrfToken  = document.querySelector('meta[name="csrf-token"]');

    // Clear any previous row highlights
    document.querySelectorAll('#excelGridBody tr.bulk-row-error').forEach(tr => {
        tr.classList.remove('bulk-row-error');
        tr.style.background = '';
    });

    setSavingState(true);

    fetch(form.action, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken ? csrfToken.content : '',
            'Accept': 'application/json',
        },
        body: formData,
    })
    .then(async res => {
        const text = await res.text();
        let data = {};
        try { data = JSON.parse(text); } catch(e) {
            data = { success: false, message: `Server error (HTTP ${res.status}). Please try again.` };
        }

        setSavingState(false);

        if (res.ok && data.success) {
            showToast(data.count);
            // Clear the grid after a short delay so user sees the toast
            setTimeout(() => {
                document.getElementById('excelGridBody').innerHTML = '';
                rowIndex = 0;
                addRows(8);
                calculateSummaries();
            }, 1500);
        } else if (data.row_errors && data.row_errors.length > 0) {
            // Per-row validation errors — highlight the bad rows and show details
            const rows = document.querySelectorAll('#excelGridBody tr');
            data.row_errors.forEach(re => {
                const tr = rows[re.row - 1];
                if (tr) {
                    tr.style.background = '#fff1f2';
                    tr.classList.add('bulk-row-error');
                    tr.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });

            // Build detailed error message for the toast
            const errorLines = data.row_errors.map(re =>
                `Row ${re.row} (${re.name}): ${re.errors.join(' | ')}`
            );

            showBulkErrorToast(data.message, errorLines);
        } else {
            showToast(data.message || 'An unexpected error occurred.', true);
        }
    })
    .catch(err => {
        setSavingState(false);
        showToast('Connection failed. Please check your internet and try again.', true);
        console.error('Bulk save error:', err);
    });
}

// Detailed multi-line error toast for row errors
function showBulkErrorToast(title, lines) {
    const toast = document.getElementById('bulkToast');
    const inner = document.getElementById('bulkToastInner');
    const icon  = document.getElementById('bulkToastIcon');
    const titleEl = document.getElementById('bulkToastTitle');

    // Replace simple msg <p> with a <ul> for multiple lines
    let listEl = document.getElementById('bulkToastErrorList');
    const existingMsg = document.getElementById('bulkToastMsg');
    if (existingMsg) existingMsg.style.display = 'none';
    if (!listEl) {
        listEl = document.createElement('ul');
        listEl.id = 'bulkToastErrorList';
        listEl.style.cssText = 'font-size:12px;color:#991b1b;font-weight:600;margin:4px 0 0;padding-left:16px;line-height:1.7;max-height:200px;overflow-y:auto;';
        inner.insertBefore(listEl, inner.querySelector('button'));
    }
    listEl.innerHTML = '';
    lines.forEach(line => {
        const li = document.createElement('li');
        li.textContent = line;
        listEl.appendChild(li);
    });

    inner.style.borderLeftColor = '#dc2626';
    icon.textContent = '❌';
    titleEl.style.color = '#7f1d1d';
    titleEl.textContent = title;

    toast.classList.remove('active');
    void toast.offsetWidth;
    toast.classList.add('active');
    // Keep error toast open for 15s (user needs to read it)
    clearTimeout(toast._bulkTimer);
    toast._bulkTimer = setTimeout(() => closeToast(), 15000);
}
</script>
@endsection
