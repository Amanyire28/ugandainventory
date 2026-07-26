@extends('layouts.app')

@section('title', 'Point of Sale (POS)')

@section('page-title')
    <i class="fas fa-cash-register text-green-600 mr-2"></i>Point of Sale
@endsection
@section('content')
<style>
    main {
        display: flex !important;
        flex-direction: column !important;
        height: calc(100vh - 72px) !important;
        overflow: hidden !important;
        padding: 1rem !important;
    }
</style>
<!-- JSON Products data for search autocomplete -->
<script>
    const allProducts = [
        @foreach($products as $product)
        {
            id: {{ $product->id }},
            name: '{{ addslashes($product->name) }}',
            sku: '{{ addslashes($product->sku ?? "") }}',
            barcode: '{{ addslashes($product->barcode ?? "") }}',
            price: {{ $product->selling_price }},
            stock: {{ $product->quantity }},
            unit: '{{ addslashes($product->unit) }}',
            requiresVat: {{ ($product->requires_vat ?? true) ? 'true' : 'false' }}
        },
        @endforeach
    ];
</script>

<!-- HTML5 QRCode & Quagga 1D Barcode Scanner Libraries -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js"></script>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 h-full overflow-hidden">
    <!-- LEFT SIDE: Selected Products Table -->
    <div class="lg:col-span-2 flex flex-col h-full min-h-0 space-y-4">
        <!-- Search Field & Camera Scanner Trigger -->
        <div class="bg-white rounded-xl shadow-lg p-4 flex-shrink-0">
            <!-- Compact Keyboard Shortcut Instructions Bar -->
            <div class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-1.5 flex items-center justify-between text-xs text-slate-600 mb-3">
                <div class="flex items-center gap-4 flex-wrap font-medium">
                    <span class="inline-flex items-center gap-1.5">
                        <kbd class="px-1.5 py-0.5 bg-white border border-slate-300 rounded font-mono font-extrabold text-slate-800 text-[10px] shadow-sm">F12</kbd> Void / Correct Recent Sales
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <kbd class="px-1.5 py-0.5 bg-white border border-slate-300 rounded font-mono font-extrabold text-slate-800 text-[10px] shadow-sm">Ctrl + K</kbd> Search
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <kbd class="px-1.5 py-0.5 bg-white border border-slate-300 rounded font-mono font-extrabold text-slate-800 text-[10px] shadow-sm">Ctrl + Enter</kbd> Complete Sale
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <kbd class="px-1.5 py-0.5 bg-white border border-slate-300 rounded font-mono font-extrabold text-slate-800 text-[10px] shadow-sm">Esc</kbd> Clear
                    </span>
                </div>
                <span class="hidden md:inline-flex text-[11px] text-slate-600 font-semibold items-center gap-1">
                    <i class="fas fa-keyboard text-slate-500"></i> Terminal Keys
                </span>
            </div>

            <div class="flex items-center gap-3">
                <div class="relative flex-1">
                    <input type="text" id="productSearch" autocomplete="off" placeholder="Type product name, SKU or scan barcode (USB scanner ready)..." class="w-full px-4 py-3 pl-10 pr-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 font-medium">
                    <i class="fas fa-search absolute left-3 top-4 text-gray-400"></i>
                    <!-- Autocomplete Dropdown -->
                    <div id="searchResultsDropdown" class="hidden absolute left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-xl z-50 max-h-60 overflow-y-auto animate-fadeIn"></div>
                </div>

                <!-- Camera Barcode Scanner Button -->
                <button type="button" onclick="openCameraScanner()" 
                        class="px-4 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold rounded-lg shadow transition flex items-center gap-2 shrink-0">
                    <i class="fas fa-camera text-base text-yellow-300"></i>
                    <span class="hidden sm:inline">Scan Camera</span>
                </button>
            </div>
        </div>

        <!-- Selected Products List Table -->
        <div class="bg-white rounded-xl shadow-lg p-4 flex-1 flex flex-col min-h-0">
            <div class="flex justify-between items-center mb-3 flex-shrink-0">
                <h3 class="text-lg font-bold text-gray-800">
                    <i class="fas fa-shopping-basket text-green-600 mr-2"></i>Selected Products
                </h3>
                <button type="button" onclick="clearCart()" class="text-sm text-red-600 hover:text-red-800 font-semibold transition">
                    <i class="fas fa-trash mr-1"></i>Clear All
                </button>
            </div>
            
            <div class="flex-1 overflow-y-auto rounded-lg border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-blue-50/70 sticky top-0 z-10 text-blue-900 border-b border-blue-200">
                        <tr>
                            <th scope="col" class="px-3 py-2.5 text-left font-extrabold uppercase tracking-wider">Product</th>
                            <th scope="col" class="px-3 py-2.5 text-right font-extrabold uppercase tracking-wider w-28">Price</th>
                            <th scope="col" class="px-3 py-2.5 text-center font-extrabold uppercase tracking-wider w-32">Quantity</th>
                            <th scope="col" class="px-3 py-2.5 text-right font-extrabold uppercase tracking-wider w-28">VAT (18%)</th>
                            <th scope="col" class="px-3 py-2.5 text-right font-extrabold uppercase tracking-wider w-32">Subtotal</th>
                            <th scope="col" class="px-3 py-2.5 text-center font-extrabold uppercase tracking-wider w-14">Action</th>
                        </tr>
                    </thead>
                    <tbody id="cartItemsTable" class="bg-white divide-y divide-gray-200 text-gray-700">
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-gray-400">
                                <i class="fas fa-shopping-basket text-5xl mb-3 block text-gray-300"></i>
                                No products selected. Search/scan above to add.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- RIGHT SIDE: Payment & Checkout Panel -->
    <div class="lg:col-span-1 bg-white rounded-xl shadow-lg p-4 flex flex-col h-full justify-between min-h-0">
        <!-- Customer Section -->
        <div class="border-b pb-3 flex-shrink-0">
            <h3 class="text-sm font-bold text-gray-800 mb-2 flex items-center">
                <i class="fas fa-user-circle text-green-600 mr-2"></i>Customer
            </h3>
            <div class="grid grid-cols-3 gap-2">
                <label class="flex items-center justify-center p-2 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 text-xs">
                    <input type="radio" name="customer_option" value="walk_in" checked onchange="toggleCustomerFields()" class="h-3.5 w-3.5 text-green-600 focus:ring-green-500 mr-1">
                    <span>Walk-in</span>
                </label>
                <label class="flex items-center justify-center p-2 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 text-xs">
                    <input type="radio" name="customer_option" value="existing" onchange="toggleCustomerFields()" class="h-3.5 w-3.5 text-green-600 focus:ring-green-500 mr-1">
                    <span>Existing</span>
                </label>
                <label class="flex items-center justify-center p-2 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 text-xs">
                    <input type="radio" name="customer_option" value="new" onchange="toggleCustomerFields()" class="h-3.5 w-3.5 text-green-600 focus:ring-green-500 mr-1">
                    <span>New</span>
                </label>
            </div>
            
            <div id="existingCustomerDiv" class="hidden mt-2">
                <select id="existingCustomerId" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-xs focus:ring-2 focus:ring-green-500">
                    <option value="">Select Customer</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>
            
            <div id="newCustomerDiv" class="hidden mt-2 grid grid-cols-2 gap-2 p-2 bg-green-50 rounded-lg text-xs">
                <input type="text" id="newCustomerName" class="px-2 py-1 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 text-xs" placeholder="Name *">
                <input type="text" id="newCustomerPhone" class="px-2 py-1 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 text-xs" placeholder="Phone *">
                <input type="email" id="newCustomerEmail" class="col-span-2 px-2 py-1 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 text-xs" placeholder="Email">
                <input type="text" id="newCustomerAddress" class="col-span-2 px-2 py-1 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 text-xs" placeholder="Address">
            </div>
        </div>

        <!-- Payment Option Section -->
        <div class="border-b py-3 flex-shrink-0">
            <h3 class="text-sm font-bold text-gray-800 mb-2 flex items-center">
                <i class="fas fa-coins text-green-600 mr-2"></i>Payment Option
            </h3>
            <div class="grid grid-cols-2 gap-2">
                <label class="flex items-center justify-center p-2 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 text-xs">
                    <input type="radio" name="payment_type" value="cash" checked onchange="togglePaymentType();" class="h-3.5 w-3.5 text-indigo-600 focus:ring-indigo-500 mr-1.5">
                    <span>Cash Sale</span>
                </label>
                <label class="flex items-center justify-center p-2 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 text-xs">
                    <input type="radio" name="payment_type" value="invoice" onchange="togglePaymentType();" class="h-3.5 w-3.5 text-indigo-600 focus:ring-indigo-500 mr-1.5">
                    <span>Credit Invoice</span>
                </label>
            </div>
        </div>

        <!-- Checkout Info Section -->
        <div class="flex-1 flex flex-col justify-between pt-4 pb-2 min-h-0">
            <div class="space-y-4 text-sm bg-gray-50 p-3 rounded-lg border border-gray-100">
                <div class="flex justify-between items-center">
                    <span class="text-gray-650 font-medium">Subtotal:</span>
                    <span class="font-bold text-gray-950 text-base">UGX <span id="subtotalAmount">0</span></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-650 font-medium">Discount:</span>
                    <input type="number" id="discountAmount" value="0" min="0" step="100" onchange="updateTotals()" class="w-32 px-3 py-1.5 border border-gray-300 rounded-lg text-right text-sm font-bold focus:ring-2 focus:ring-green-500">
                </div>
                <div class="flex justify-between items-center py-1 border-t border-dashed">
                    <span class="text-gray-650 font-medium">VAT (18%):</span>
                    <span class="font-bold text-gray-950 text-base">UGX <span id="taxAmount">0.00</span></span>
                </div>
                <div class="flex justify-between items-center text-lg font-extrabold text-green-600 pt-2 border-t">
                    <span>TOTAL:</span>
                    <span>UGX <span id="totalAmount">0</span></span>
                </div>
            </div>

            <!-- Cash Payment Details -->
            <div id="cash-payment-div" class="space-y-4 bg-gray-50 p-3 rounded-lg border border-gray-100 flex-shrink-0">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-xs font-bold text-gray-700 whitespace-nowrap">Amount Paid:</span>
                    <input type="number" id="amountPaid" value="0" min="0" step="100" oninput="calculateChange()" class="flex-1 min-w-0 px-3 py-2 border border-gray-300 rounded-lg text-right text-base font-extrabold text-gray-900 focus:ring-2 focus:ring-green-500">
                    <button type="button" onclick="exactAmount()" class="px-3 py-2 bg-blue-50 hover:bg-blue-100 border border-blue-200 text-blue-700 rounded-lg text-sm font-bold transition">Exact</button>
                </div>
                <div class="p-3 rounded-lg flex justify-between items-center text-sm" id="changeBox">
                     <span class="font-semibold text-gray-600">Change:</span>
                     <span class="text-base font-extrabold text-green-600">UGX <span id="changeAmount">0</span></span>
                </div>
                <div>
                    <textarea id="saleNotes" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500" placeholder="Notes (Optional)..."></textarea>
                </div>
            </div>
            
            <div id="invoiceNotice" class="hidden p-3 bg-indigo-50 border border-indigo-100 rounded-lg text-sm text-indigo-700 text-center flex-shrink-0">
                <i class="fas fa-info-circle mr-1.5"></i> Credit Sale. Items added to invoice.
            </div>

            <!-- Checkout Action Button -->
            <button onclick="processSale()"
                    id="checkoutBtn"
                    class="w-full py-3.5 bg-green-600 text-white rounded-lg hover:bg-green-700 font-extrabold text-base shadow-lg hover:shadow-xl transition disabled:bg-gray-300 disabled:cursor-not-allowed flex items-center justify-center gap-2 flex-shrink-0">
                <i class="fas fa-check-circle text-lg"></i>
                <span id="checkoutBtnText">Complete Sale</span>
            </button>
        </div>
    </div>
</div>

<!-- Receipt Modal -->
<div id="receiptModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full" id="receiptContent"></div>
</div>

<!-- Camera Barcode Scanner Modal -->
<div id="cameraScannerModal" class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden flex flex-col">
        <div class="bg-indigo-900 text-white px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fas fa-camera text-yellow-400 text-xl"></i>
                <h3 class="font-extrabold text-lg">Camera Barcode Scanner</h3>
            </div>
            <button type="button" onclick="closeCameraScanner()" class="text-white hover:text-yellow-400 text-xl transition">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-6 space-y-4 text-center">
            <!-- Camera Device Selector -->
            <div id="cameraSelectContainer" class="hidden text-left mb-2">
                <label class="block text-xs font-bold text-gray-700 mb-1">Select Camera Device:</label>
                <select id="cameraSelectDropdown" onchange="switchCamera(this.value)" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-xs font-semibold focus:ring-2 focus:ring-indigo-500"></select>
            </div>

            <div id="cameraViewfinder" class="w-full h-64 bg-slate-900 rounded-xl overflow-hidden relative border-2 border-indigo-500 shadow-inner"></div>
            
            <div class="bg-indigo-50 border border-indigo-100 rounded-lg p-3 text-left space-y-1">
                <p class="text-xs text-indigo-900 font-bold flex items-center gap-1.5">
                    <i class="fas fa-lightbulb text-yellow-500"></i> Scanning Tips for Webcams:
                </p>
                <ul class="text-[11px] text-indigo-800 space-y-0.5 list-disc list-inside">
                    <li>Hold barcode 15–20 cm away in good lighting (avoid glare/reflections).</li>
                    <li>Align horizontal barcode lines clearly inside the scan frame box.</li>
                    <li>For handheld USB barcode guns or typing, scan directly into the search bar.</li>
                </ul>
            </div>
        </div>
        <div class="bg-gray-50 px-6 py-4 border-t flex flex-col sm:flex-row justify-between items-center gap-3">
            <span class="text-xs text-gray-700 font-extrabold flex items-center gap-1.5" id="camScanStatus">
                <i class="fas fa-circle-notch fa-spin text-indigo-600"></i> Initializing camera…
            </span>
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <input type="text" id="modalTestBarcode" placeholder="Or type test barcode & press Enter…" 
                       onkeydown="if(event.key==='Enter'){ handleScannedBarcode(this.value); closeCameraScanner(); }" 
                       class="px-3 py-1.5 border border-gray-300 rounded-lg text-xs font-mono flex-1 sm:w-56 focus:ring-2 focus:ring-indigo-500">
                <button type="button" onclick="closeCameraScanner()" class="px-4 py-1.5 bg-gray-200 text-gray-800 font-bold rounded-lg hover:bg-gray-300 text-xs shrink-0">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let cart = [];
let html5QrcodeScanner = null;
let currentCameraId = null;
let frameScanCounter = 0;

// Audio Beep Feedback
function playBeepSound() {
    try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.type = 'sine';
        osc.frequency.value = 987.77; // B5 tone
        gain.gain.setValueAtTime(0.2, audioCtx.currentTime);
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        osc.start();
        osc.stop(audioCtx.currentTime + 0.12);
    } catch(e) {}
}

function getScannerConfig() {
    const config = {
        fps: 15,
        qrbox: { width: 260, height: 140 }
    };

    if (window.Html5QrcodeSupportedFormats) {
        try {
            config.formatsToSupport = [
                Html5QrcodeSupportedFormats.EAN_13,
                Html5QrcodeSupportedFormats.EAN_8,
                Html5QrcodeSupportedFormats.UPC_A,
                Html5QrcodeSupportedFormats.UPC_E,
                Html5QrcodeSupportedFormats.CODE_128,
                Html5QrcodeSupportedFormats.CODE_39,
                Html5QrcodeSupportedFormats.QR_CODE
            ];
        } catch(e) {}
    }
    return config;
}

let isQuaggaRunning = false;

function openCameraScanner() {
    const modal = document.getElementById('cameraScannerModal');
    modal.classList.remove('hidden');
    
    const statusEl = document.getElementById('camScanStatus');
    statusEl.innerHTML = '<i class="fas fa-circle-notch fa-spin text-indigo-600"></i> Requesting webcam permission…';
    frameScanCounter = 0;

    setTimeout(() => {
        if (html5QrcodeScanner) {
            html5QrcodeScanner.stop().catch(() => {}).finally(() => {
                html5QrcodeScanner = null;
                initAndStartCamera(statusEl);
            });
        } else {
            initAndStartCamera(statusEl);
        }
    }, 150);
}

function initAndStartCamera(statusEl) {
    try {
        html5QrcodeScanner = new Html5Qrcode("cameraViewfinder");
        const config = getScannerConfig();

        Html5Qrcode.getCameras().then(devices => {
            if (devices && devices.length > 0) {
                const dropdown = document.getElementById('cameraSelectDropdown');
                const container = document.getElementById('cameraSelectContainer');
                dropdown.innerHTML = '';
                
                devices.forEach((dev, idx) => {
                    const opt = document.createElement('option');
                    opt.value = dev.id;
                    opt.textContent = dev.label || (`Camera ${idx + 1}`);
                    dropdown.appendChild(opt);
                });

                if (devices.length > 1) {
                    container.classList.remove('hidden');
                }

                currentCameraId = devices[0].id;
                dropdown.value = currentCameraId;

                html5QrcodeScanner.start(
                    currentCameraId,
                    config,
                    onCameraScanSuccess,
                    onCameraScanFailure
                ).then(() => {
                    statusEl.innerHTML = '<i class="fas fa-video text-emerald-500"></i> Camera active. Point barcode at laser frame…';
                    startQuaggaScanner(statusEl, currentCameraId);
                }).catch(err => {
                    console.error('Camera start error:', err);
                    statusEl.innerHTML = '⚠️ Camera start error: ' + (err.message || err);
                    startQuaggaScanner(statusEl, null);
                });
            } else {
                statusEl.innerHTML = '⚠️ No webcam device detected on this computer.';
            }
        }).catch(err => {
            console.error('getCameras error:', err);
            html5QrcodeScanner.start(
                { facingMode: "user" },
                config,
                onCameraScanSuccess,
                onCameraScanFailure
            ).then(() => {
                statusEl.innerHTML = '<i class="fas fa-video text-emerald-500"></i> Camera active. Point barcode at laser frame…';
                startQuaggaScanner(statusEl, null);
            }).catch(err2 => {
                statusEl.innerHTML = '⚠️ Camera blocked or insecure origin. Note: Webcams require http://localhost or HTTPS.';
            });
        });
    } catch(e) {
        console.error('openCameraScanner exception:', e);
        statusEl.innerHTML = '⚠️ Camera init error: ' + e.message;
    }
}

function startQuaggaScanner(statusEl, deviceId) {
    if (typeof Quagga === 'undefined' || isQuaggaRunning) return;

    const constraints = deviceId ? { deviceId: { exact: deviceId } } : { facingMode: "environment" };

    Quagga.init({
        inputStream: {
            name: "Live",
            type: "LiveStream",
            target: document.querySelector('#cameraViewfinder'),
            constraints: {
                ...constraints,
                width: { min: 640, ideal: 1280 },
                height: { min: 480, ideal: 720 }
            }
        },
        decoder: {
            readers: [
                "ean_reader",
                "ean_8_reader",
                "upc_reader",
                "upc_e_reader",
                "code_128_reader",
                "code_39_reader"
            ]
        },
        locate: true,
        numOfWorkers: 2
    }, function(err) {
        if (err) return;
        Quagga.start();
        isQuaggaRunning = true;
    });

    Quagga.onDetected(function(result) {
        if (result && result.codeResult && result.codeResult.code) {
            const code = result.codeResult.code.trim();
            if (code && code.length >= 3) {
                playBeepSound();
                handleScannedBarcode(code);
                closeCameraScanner();
            }
        }
    });
}

function onCameraScanSuccess(decodedText, decodedResult) {
    playBeepSound();
    handleScannedBarcode(decodedText);
    closeCameraScanner();
}

function onCameraScanFailure(error) {
    frameScanCounter++;
    const statusEl = document.getElementById('camScanStatus');
    if (statusEl && frameScanCounter % 8 === 0) {
        statusEl.innerHTML = `<span class="text-emerald-700 font-extrabold flex items-center gap-1.5"><i class="fas fa-satellite-dish text-emerald-600 fa-pulse"></i> Scanning 1D Barcode Feed (Frame #${frameScanCounter})… Hold barcode steady</span>`;
    }
}

function closeCameraScanner() {
    if (isQuaggaRunning && typeof Quagga !== 'undefined') {
        try { Quagga.stop(); } catch(e) {}
        isQuaggaRunning = false;
    }
    if (html5QrcodeScanner) {
        html5QrcodeScanner.stop().catch(() => {}).finally(() => {
            document.getElementById('cameraScannerModal').classList.add('hidden');
        });
    } else {
        document.getElementById('cameraScannerModal').classList.add('hidden');
    }
}

function showScanToast(message, type = 'success') {
    let toast = document.getElementById('posScanToast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'posScanToast';
        document.body.appendChild(toast);
    }

    if (type === 'success') {
        toast.className = 'fixed top-5 right-5 z-50 px-5 py-3.5 rounded-xl shadow-2xl font-extrabold text-sm transition-all duration-300 flex items-center gap-3 bg-emerald-600 text-white border border-emerald-400';
    } else {
        toast.className = 'fixed top-5 right-5 z-50 px-5 py-3.5 rounded-xl shadow-2xl font-extrabold text-sm transition-all duration-300 flex items-center gap-3 bg-red-600 text-white border border-red-400';
    }

    toast.innerHTML = message;
    toast.style.display = 'flex';

    setTimeout(() => {
        toast.style.display = 'none';
    }, 4000);
}

// Handle Scanned Barcode (from Camera, USB/Bluetooth Scanner Gun, or Search Field)
function handleScannedBarcode(barcode) {
    const cleanBarcode = barcode.trim().toLowerCase();
    if (!cleanBarcode) return;

    const matchedProduct = allProducts.find(p => 
        (p.barcode && p.barcode.trim().toLowerCase() === cleanBarcode) ||
        (p.sku && p.sku.trim().toLowerCase() === cleanBarcode) ||
        (p.name && p.name.trim().toLowerCase() === cleanBarcode)
    );

    if (matchedProduct) {
        playBeepSound();
        addToCart(matchedProduct.id, matchedProduct.name, matchedProduct.price, matchedProduct.unit, matchedProduct.stock, matchedProduct.requiresVat);
        if (document.getElementById('productSearch')) {
            document.getElementById('productSearch').value = '';
        }
        showScanToast(`<i class="fas fa-check-circle text-lg"></i> Product "${matchedProduct.name}" added to cart for checkout!`, 'success');
    } else {
        showScanToast(`<i class="fas fa-exclamation-triangle text-lg"></i> ⚠️ No product record found in database matching barcode/SKU: "${barcode}"`, 'error');
    }
}

// External Hardware USB / Bluetooth Scanner HID Keyboard Buffer Listener
let hardwareBarcodeBuffer = '';
let lastKeyTime = 0;

document.addEventListener('keydown', function(e) {
    const currentTime = new Date().getTime();
    const activeEl = document.activeElement;
    
    // Allow standard typing in form inputs other than productSearch
    if (activeEl && activeEl.tagName === 'INPUT' && activeEl.id !== 'productSearch' && activeEl.type !== 'button') {
        return;
    }

    if (currentTime - lastKeyTime > 80) {
        hardwareBarcodeBuffer = '';
    }
    lastKeyTime = currentTime;

    if (e.key === 'Enter') {
        if (hardwareBarcodeBuffer.length >= 3) {
            e.preventDefault();
            playBeepSound();
            handleScannedBarcode(hardwareBarcodeBuffer);
            hardwareBarcodeBuffer = '';
            if (document.getElementById('productSearch')) {
                document.getElementById('productSearch').value = '';
            }
        }
    } else if (e.key.length === 1) {
        hardwareBarcodeBuffer += e.key;
    }
});

function toggleCustomerFields() {
    const option = document.querySelector('input[name="customer_option"]:checked').value;
    document.getElementById('existingCustomerDiv').classList.add('hidden');
    document.getElementById('newCustomerDiv').classList.add('hidden');
    if (option === 'existing') {
        document.getElementById('existingCustomerDiv').classList.remove('hidden');
    } else if (option === 'new') {
        document.getElementById('newCustomerDiv').classList.remove('hidden');
    }
}

function togglePaymentType() {
    let paymentType = document.querySelector('input[name="payment_type"]:checked').value;
    let cashDiv = document.getElementById('cash-payment-div');
    let invoiceNotice = document.getElementById('invoiceNotice');
    let btnTextSpan = document.getElementById('checkoutBtnText');
    if (paymentType === 'invoice') {
        cashDiv.classList.add('hidden');
        invoiceNotice.classList.remove('hidden');
        if(btnTextSpan) btnTextSpan.textContent = 'Make Invoice';
    } else {
        cashDiv.classList.remove('hidden');
        invoiceNotice.classList.add('hidden');
        if(btnTextSpan) btnTextSpan.textContent = 'Complete Sale';
    }
    calculateChange();
}

function addToCart(id, name, price, unit, maxStock, requiresVat = true) {
    if (maxStock <= 0) {
        alert('Cannot add! Product is out of stock.');
        return;
    }
    const existingItem = cart.find(item => item.id === id);
    if (existingItem) {
        if (existingItem.quantity + 1 > maxStock) {
            alert('Cannot add more! Maximum stock available: ' + maxStock);
            return;
        }
        existingItem.quantity++;
    } else {
        cart.push({id, name, price, quantity: 1, unit, maxStock, requiresVat});
    }
    renderCart();
    updateTotals();
}

function removeFromCart(id) {
    cart = cart.filter(item => item.id !== id);
    renderCart();
    updateTotals();
}

function updateQuantity(id, newQuantity) {
    const item = cart.find(item => item.id === id);
    if (item) {
        if (newQuantity > item.maxStock) {
            alert('Cannot exceed available stock: ' + item.maxStock);
            renderCart(); // Reset the visual input back to valid amount
            return;
        }
        if (newQuantity <= 0) {
            removeFromCart(id);
        } else {
            item.quantity = parseFloat(newQuantity);
            renderCart();
            updateTotals();
        }
    }
}

function clearCart() {
    if (cart.length === 0) return;
    if (confirm('Clear all items from cart?')) {
        cart = [];
        renderCart();
        updateTotals();
    }
}

function renderCart() {
    const tableBody = document.getElementById('cartItemsTable');
    const checkoutBtn = document.getElementById('checkoutBtn');
    
    if (cart.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="6" class="px-4 py-12 text-center text-gray-400">
                    <i class="fas fa-shopping-basket text-5xl mb-3 block text-gray-300"></i>
                    No products selected. Search/scan above to add.
                </td>
            </tr>
        `;
        if (checkoutBtn) checkoutBtn.disabled = true;
        return;
    }
    
    if (checkoutBtn) checkoutBtn.disabled = false;
    let html = '';
    cart.forEach(item => {
        const itemVat = item.requiresVat ? (item.price * item.quantity * 0.18) : 0;
        const vatText = item.requiresVat 
            ? `+UGX ${itemVat.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}` 
            : `<span class="text-gray-400 font-normal">Exempt</span>`;

        html += `
            <tr class="hover:bg-gray-50 transition">
                <td class="px-3 py-3 font-semibold text-gray-900">
                    <div class="text-sm font-bold text-slate-900">${item.name}</div>
                    <div class="text-xs text-slate-500 font-normal mt-0.5">Stock: ${item.maxStock} ${item.unit}</div>
                </td>
                <td class="px-3 py-3 text-right text-gray-800 font-medium text-xs">
                    UGX ${item.price.toLocaleString()}
                </td>
                <td class="px-3 py-3">
                    <div class="flex items-center justify-center space-x-1.5">
                        <button type="button" onclick="updateQuantity(${item.id}, ${item.quantity - 1})"
                                class="w-7 h-7 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded flex items-center justify-center transition border border-gray-200">
                            <i class="fas fa-minus text-xs"></i>
                        </button>
                        <input type="number"
                               value="${item.quantity}"
                               min="1"
                               max="${item.maxStock}"
                               onchange="updateQuantity(${item.id}, this.value)"
                               class="w-14 px-1.5 py-1 border border-gray-300 rounded text-center font-bold text-xs focus:ring-2 focus:ring-blue-500">
                        <button type="button" onclick="updateQuantity(${item.id}, ${item.quantity + 1})"
                                class="w-7 h-7 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded flex items-center justify-center transition border border-gray-200">
                            <i class="fas fa-plus text-xs"></i>
                        </button>
                    </div>
                </td>
                <td class="px-3 py-3 text-right font-semibold text-blue-700 text-xs">
                    ${vatText}
                </td>
                <td class="px-3 py-3 text-right font-extrabold text-blue-900 text-sm">
                    UGX ${(item.price * item.quantity).toLocaleString()}
                </td>
                <td class="px-3 py-3 text-center">
                    <button type="button" onclick="removeFromCart(${item.id})"
                            class="p-1.5 bg-red-50 hover:bg-red-100 text-red-600 rounded transition border border-red-200" title="Remove item">
                        <i class="fas fa-trash-alt text-xs"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    tableBody.innerHTML = html;
}

function updateTotals() {
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const discount = parseFloat(document.getElementById('discountAmount').value) || 0;
    
    // Automatically calculate VAT for products with requiresVat = true
    const tax = cart.reduce((sum, item) => sum + (item.requiresVat ? (item.price * item.quantity * 0.18) : 0), 0);
    
    const total = subtotal - discount + tax;
    document.getElementById('subtotalAmount').textContent = subtotal.toLocaleString();
    document.getElementById('taxAmount').textContent = tax.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('totalAmount').textContent = total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    calculateChange();
}

function calculateChange() {
    const total = parseFloat(document.getElementById('totalAmount').textContent.replace(/,/g, ''));
    const amountPaid = parseFloat(document.getElementById('amountPaid').value) || 0;
    const change = amountPaid - total;
    document.getElementById('changeAmount').textContent = Math.max(0, change).toLocaleString();
    const changeBox = document.getElementById('changeBox');
    const changeAmountSpan = document.getElementById('changeAmount');
    if (amountPaid < total && amountPaid > 0) {
        changeBox.classList.remove('bg-green-50');
        changeBox.classList.add('bg-red-50');
        changeAmountSpan.classList.remove('text-green-600');
        changeAmountSpan.classList.add('text-red-600');
    } else {
        changeBox.classList.add('bg-green-50');
        changeBox.classList.remove('bg-red-50');
        changeAmountSpan.classList.add('text-green-600');
        changeAmountSpan.classList.remove('text-red-600');
    }
}
function exactAmount() {
    const total = parseFloat(document.getElementById('totalAmount').textContent.replace(/,/g, ''));
    document.getElementById('amountPaid').value = total;
    calculateChange();
}

function showSuccessToast(message) {
    const toastId = 'toast-' + Date.now();
    const toast = document.createElement('div');
    toast.id = toastId;
    toast.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-4 rounded-lg shadow-lg flex items-center space-x-3 animate-slideIn z-50';
    toast.innerHTML = `
        <i class="fas fa-check-circle text-xl"></i>
        <div>
            <p class="font-semibold">Success!</p>
            <p class="text-sm text-green-100">${message}</p>
        </div>
    `;
    document.body.appendChild(toast);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease-in-out';
        setTimeout(() => toast.remove(), 300);
    }, 5000);
}

function showErrorToast(message) {
    const toastId = 'toast-' + Date.now();
    const toast = document.createElement('div');
    toast.id = toastId;
    toast.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-4 rounded-lg shadow-lg flex items-center space-x-3 animate-slideIn z-50';
    toast.innerHTML = `
        <i class="fas fa-exclamation-circle text-xl"></i>
        <div>
            <p class="font-semibold">Error!</p>
            <p class="text-sm text-red-100">${message}</p>
        </div>
    `;
    document.body.appendChild(toast);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease-in-out';
        setTimeout(() => toast.remove(), 300);
    }, 5000);
}

async function processSale() {
    if (cart.length === 0) {
        alert('Cart is empty!');
        return;
    }
    const paymentType = document.querySelector('input[name="payment_type"]:checked').value;
    const customerOption = document.querySelector('input[name="customer_option"]:checked').value;
    let saleData = {
        customer_option: customerOption,
        items: cart.map(item => ({
            product_id: item.id,
            quantity: item.quantity,
            price: item.price
        })),
        discount: parseFloat(document.getElementById('discountAmount').value) || 0,
        add_tax: true,
        notes: document.getElementById('saleNotes').value || null,
        _token: '{{ csrf_token() }}',
        payment_type: paymentType
    };
    if (paymentType === 'cash') {
        saleData.amount_paid = parseFloat(document.getElementById('amountPaid').value) || 0;
    }
    if (customerOption === 'existing') {
        const customerId = document.getElementById('existingCustomerId').value;
        if (!customerId) {
            alert('Please select a customer');
            return;
        }
        saleData.customer_id = customerId;
    } else if (customerOption === 'new') {
        const name = document.getElementById('newCustomerName').value.trim();
        const phone = document.getElementById('newCustomerPhone').value.trim();
        if (!name || !phone) {
            alert('Please enter customer name and phone number');
            return;
        }
        saleData.new_customer_name = name;
        saleData.new_customer_phone = phone;
        saleData.new_customer_email = document.getElementById('newCustomerEmail').value.trim();
        saleData.new_customer_address = document.getElementById('newCustomerAddress').value.trim();
    }
    if (paymentType === 'cash') {
        const total = parseFloat(document.getElementById('totalAmount').textContent.replace(/,/g, ''));
        const amountPaid = parseFloat(document.getElementById('amountPaid').value) || 0;
        if (amountPaid < total) {
            alert('Amount paid is less than total amount!');
            return;
        }
    }
    const checkoutBtn = document.getElementById('checkoutBtn');
    checkoutBtn.disabled = true;
    checkoutBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
    try {
        let endpoint =
            paymentType === "invoice"
            ? "{{ route('invoices.pos') }}"
            : "{{ route('pos.process') }}";
        
        console.log('Posting to:', endpoint);
        console.log('Payload:', saleData);
        
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(saleData)
        });
        
        console.log('Response status:', response.status);
        const text = await response.text();
        console.log('Response text:', text);
        
        let result;
        try {
            result = JSON.parse(text);
        } catch (e) {
            console.error('Failed to parse JSON:', e);
            alert('Error: Server returned invalid response\n' + text);
            return;
        }
        
        console.log('Parsed result:', result);
        
        if (result && result.success) {
            console.log('Sale successful, showing receipt');
            
            // Deduct sold quantities from the allProducts array locally
            cart.forEach(item => {
                let product = allProducts.find(p => p.id === item.id);
                if (product) {
                    product.stock -= item.quantity;
                }
            });

            console.log('Data:', result);
            showReceipt(result);
            cart = [];
            renderCart();
            updateTotals();
            document.querySelector('input[name="customer_option"][value="walk_in"]').checked = true;
            toggleCustomerFields();
            document.getElementById('discountAmount').value = 0;
            document.getElementById('addTaxCheckbox').checked = false;
            document.getElementById('amountPaid').value = 0;
            document.getElementById('saleNotes').value = '';
            document.getElementById('existingCustomerId').value = '';
            document.getElementById('newCustomerName').value = '';
            document.getElementById('newCustomerPhone').value = '';
            document.getElementById('newCustomerEmail').value = '';
            document.getElementById('newCustomerAddress').value = '';
            calculateChange();
        } else if (result && result.message) {
            console.error('Sale failed:', result.message);
            showErrorToast(result.message);
        } else {
            console.error('Unknown error:', result);
            showErrorToast('An unknown error occurred. Please try again.');
        }
    } catch (error) {
        console.error('Fetch error:', error);
        showErrorToast('Failed to process sale. Please check your connection and try again.');
    } finally {
        checkoutBtn.disabled = false;
        let paymentType2 = document.querySelector('input[name="payment_type"]:checked').value;
        let btnText2 = paymentType2 === 'invoice' ? 'Make Invoice' : 'Complete Sale';
        checkoutBtn.innerHTML = '<i class="fas fa-check-circle mr-2"></i> <span id="checkoutBtnText">' + btnText2 + '</span>';
    }
}
function showReceipt(data) {
    // Receipt modal itself shows success - no need for extra toast
    const modal = document.getElementById('receiptModal');
    const content = document.getElementById('receiptContent');
    
    if (!modal || !content) {
        console.error('Modal or content element not found!');
        alert('Receipt display error. Sale #' + (data.sale_number || data.invoice_number) + ' completed successfully!');
        return;
    }
    
    let html = `
        <div class="p-6">
            <div class="text-center mb-6">
                <i class="fas fa-check-circle text-6xl text-green-600 mb-4"></i>
                <h2 class="text-2xl font-bold text-gray-900">${data.sale_number ? 'Sale Completed!' : 'Invoice Created!'}</h2>
                <p class="text-gray-600">${data.sale_number ? 'Sale #' + data.sale_number : 'Invoice #' + data.invoice_number}</p>
            </div>
            <div class="space-y-3 mb-6">
                <div class="flex justify-between text-lg">
                    <span class="text-gray-700">Total Amount:</span>
                    <span class="font-bold">UGX ${(data.total || 0).toLocaleString()}</span>
                </div>
                ${data.amount_paid !== undefined ? `
                <div class="flex justify-between">
                    <span class="text-gray-700">Amount Paid:</span>
                    <span class="font-semibold">UGX ${(data.amount_paid || 0).toLocaleString()}</span>
                </div>
                <div class="flex justify-between text-xl border-t pt-3">
                    <span class="text-gray-700 font-bold">Change:</span>
                    <span class="font-bold text-green-600">UGX ${(data.change || 0).toLocaleString()}</span>
                </div>
                ` : `
                <div class="flex justify-between text-xl border-t pt-3">
                    <span class="text-gray-700 font-bold">Customer:</span>
                    <span class="font-bold text-indigo-600">${data.customer || ''}</span>
                </div>
                `}
            </div>
            <div class="flex space-x-2">
                ${data.sale_id ?
                    `<a href="/sales/${data.sale_id}" target="_blank"
                   class="flex-1 px-4 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-center">
                    <i class="fas fa-print mr-2"></i>Print Receipt
                    </a>` : ''
                }
                ${data.invoice_id ?
                    `<a href="/invoices/${data.invoice_id}" target="_blank"
                   class="flex-1 px-4 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-center">
                    <i class="fas fa-print mr-2"></i>Print Invoice
                    </a>` : ''
                }
                <button onclick="closeReceipt()" 
                        class="flex-1 px-4 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                    <i class="fas fa-times mr-2"></i>Close
                </button>
            </div>
        </div>
    `;
    
    try {
        content.innerHTML = html;
        modal.classList.remove('hidden');
        console.log('Receipt modal displayed successfully');
    } catch (error) {
        console.error('Error displaying receipt modal:', error);
        alert('Sale #' + (data.sale_number || data.invoice_number) + ' completed successfully!');
    }
}

function closeReceipt() {
    document.getElementById('receiptModal').classList.add('hidden');
}
const searchInput = document.getElementById('productSearch');
const dropdown = document.getElementById('searchResultsDropdown');
let activeSearchIndex = 0;

searchInput.addEventListener('input', function() {
    const query = this.value.toLowerCase().trim();
    if (!query) {
        dropdown.classList.add('hidden');
        dropdown.innerHTML = '';
        return;
    }

    const matches = allProducts.filter(p => 
        p.name.toLowerCase().includes(query) || 
        p.sku.toLowerCase().includes(query) ||
        (p.barcode && p.barcode.toLowerCase().includes(query))
    );

    if (matches.length === 0) {
        dropdown.innerHTML = '<div class="p-3 text-red-500 text-sm font-semibold text-center"><i class="fas fa-exclamation-circle mr-1"></i> No matching product found in database</div>';
        dropdown.classList.remove('hidden');
        return;
    }

    let html = '';
    matches.forEach((p, index) => {
        html += `
            <div class="search-result-item p-3 border-b border-gray-100 hover:bg-green-50 cursor-pointer flex justify-between items-center ${index === 0 ? 'bg-green-50 ring-1 ring-green-400 font-semibold' : ''}" 
                 data-id="${p.id}" 
                 data-index="${index}">
                <div>
                    <span class="font-semibold text-gray-900">${p.name}</span>
                    <span class="text-xs text-gray-500 font-mono ml-2">SKU: ${p.sku || 'N/A'}</span>
                    ${p.barcode ? `<span class="text-xs text-indigo-600 font-mono ml-2 font-bold"><i class="fas fa-qrcode text-[10px] mr-0.5"></i>${p.barcode}</span>` : ''}
                </div>
                <div class="text-right">
                    <span class="font-bold text-gray-950">UGX ${p.price.toLocaleString()}</span>
                    <span class="text-xs text-gray-600 block">Stock: ${p.stock} ${p.unit}</span>
                </div>
            </div>
        `;
    });
    dropdown.innerHTML = html;
    dropdown.classList.remove('hidden');
    activeSearchIndex = 0;
});

dropdown.addEventListener('click', function(e) {
    const item = e.target.closest('.search-result-item');
    if (item) {
        const id = parseInt(item.dataset.id);
        const product = allProducts.find(p => p.id === id);
        if (product) {
            addToCart(product.id, product.name, product.price, product.unit, product.stock, product.requiresVat);
            searchInput.value = '';
            dropdown.classList.add('hidden');
            dropdown.innerHTML = '';
            searchInput.focus();
        }
    }
});

// Hide dropdown when clicking outside
document.addEventListener('click', function(e) {
    if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.classList.add('hidden');
    }
});

document.addEventListener('keydown', function(e) {
    const isSearchFocused = document.activeElement === searchInput;
    
    // If dropdown is open, handle navigation there
    if (!dropdown.classList.contains('hidden')) {
        const items = dropdown.querySelectorAll('.search-result-item');
        if (items.length > 0) {
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                activeSearchIndex = (activeSearchIndex + 1) % items.length;
                highlightSearchItem(items);
                return;
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                activeSearchIndex = (activeSearchIndex - 1 + items.length) % items.length;
                highlightSearchItem(items);
                return;
            } else if (e.key === 'Enter') {
                e.preventDefault();
                const activeItem = items[activeSearchIndex];
                if (activeItem) {
                    const id = parseInt(activeItem.dataset.id);
                    const product = allProducts.find(p => p.id === id);
                    if (product) {
                        addToCart(product.id, product.name, product.price, product.unit, product.stock, product.requiresVat);
                        searchInput.value = '';
                        dropdown.classList.add('hidden');
                        dropdown.innerHTML = '';
                        searchInput.focus();
                        return;
                    }
                }
            } else if (e.key === 'Escape') {
                e.preventDefault();
                searchInput.value = '';
                dropdown.classList.add('hidden');
                dropdown.innerHTML = '';
                searchInput.focus();
                return;
            }
        }
    }

    // Direct Enter key press inside search box when no items or dropdown active
    if (isSearchFocused && e.key === 'Enter') {
        const query = searchInput.value.trim();
        if (query) {
            e.preventDefault();
            dropdown.classList.add('hidden');
            handleScannedBarcode(query);
            return;
        }
    }

    // Standard keyboard shortcuts when dropdown is not active
    if (e.key === 'Escape') {
        searchInput.value = '';
        searchInput.focus();
        return;
    }

    if (e.ctrlKey || e.metaKey) {
        if (e.key === 'k') {
            e.preventDefault();
            searchInput.focus();
        }
        if (e.key === 'Enter') {
            e.preventDefault();
            if (!document.getElementById('checkoutBtn').disabled) {
                processSale();
            }
        }
        return;
    }
});

// F12 Keyboard Shortcut Listener
window.addEventListener('keydown', function(e) {
    if (e.key === 'F12' || e.keyCode === 123) {
        e.preventDefault();
        openF12VoidModal();
    }
});

function openF12VoidModal() {
    const modal = document.getElementById('f12VoidModal');
    if (modal) {
        modal.classList.remove('hidden');
        loadRecentSalesForF12();
    }
}

function closeF12VoidModal() {
    const modal = document.getElementById('f12VoidModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

function loadRecentSalesForF12() {
    const tbody = document.getElementById('f12RecentSalesTableBody');
    if (!tbody) return;

    tbody.innerHTML = `
        <tr>
            <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                <i class="fas fa-spinner fa-spin text-2xl text-indigo-600 mb-2"></i>
                <p class="text-xs font-semibold">Loading 5 recent sales...</p>
            </td>
        </tr>
    `;

    fetch("{{ route('pos.recent-sales') }}")
        .then(response => response.json())
        .then(data => {
            if (!data || data.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-400">
                            <i class="fas fa-inbox text-3xl mb-2 text-gray-300"></i>
                            <p class="text-xs">No recent sales found.</p>
                        </td>
                    </tr>
                `;
                return;
            }

            let html = '';
            data.forEach(sale => {
                const statusBadge = sale.is_voided
                    ? `<span class="px-2 py-0.5 text-[10px] font-black rounded-full bg-red-100 text-red-800 border border-red-300"><i class="fas fa-ban mr-1"></i> VOIDED</span>`
                    : `<span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-green-100 text-green-800">Completed</span>`;

                const actionBtn = sale.is_voided
                    ? `<span class="text-xs text-red-600 font-bold italic" title="${sale.void_reason}">Voided (${sale.voided_at})</span>`
                    : `<button type="button" onclick="toggleF12VoidForm(${sale.id})" class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white font-extrabold rounded text-xs transition flex items-center justify-center gap-1 mx-auto shadow">
                        <i class="fas fa-undo"></i> Void / Reverse
                       </button>`;

                html += `
                    <tr class="hover:bg-gray-50 transition border-b">
                        <td class="px-4 py-3 font-extrabold text-indigo-600">${sale.sale_number}</td>
                        <td class="px-4 py-3 text-gray-700">${sale.sale_date}</td>
                        <td class="px-4 py-3 text-gray-800 font-semibold">${sale.customer_name}</td>
                        <td class="px-4 py-3 text-center text-gray-600 font-bold">${sale.items_count} items</td>
                        <td class="px-4 py-3 text-right font-extrabold ${sale.is_voided ? 'line-through text-red-500' : 'text-slate-900'}">UGX ${Number(sale.total).toLocaleString()}</td>
                        <td class="px-4 py-3 text-center">${statusBadge}</td>
                        <td class="px-4 py-3 text-center">${actionBtn}</td>
                    </tr>
                    <tr id="f12VoidRow-${sale.id}" class="hidden bg-amber-50/70 border-b">
                        <td colspan="7" class="px-4 py-3">
                            <form onsubmit="submitF12Void(event, ${sale.id})" class="flex flex-col sm:flex-row items-center gap-3">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                <div class="flex-1 w-full">
                                    <label class="block text-[11px] font-extrabold text-slate-800 uppercase tracking-wide mb-1">Reason for Reversal / Correction *</label>
                                    <input type="text" name="void_reason" required placeholder="e.g. Wrong item scanned, price correction, customer request..." class="w-full px-3 py-1.5 text-xs border border-amber-300 rounded focus:ring-2 focus:ring-red-500 outline-none">
                                </div>
                                <div class="flex gap-2 shrink-0 pt-4 sm:pt-0">
                                    <button type="button" onclick="toggleF12VoidForm(${sale.id})" class="px-3 py-1.5 bg-gray-200 text-gray-700 font-bold text-xs rounded hover:bg-gray-300">Cancel</button>
                                    <button type="submit" class="px-4 py-1.5 bg-red-600 text-white font-extrabold text-xs rounded hover:bg-red-700 shadow flex items-center gap-1">
                                        <i class="fas fa-check-circle"></i> Confirm Void & Restock
                                    </button>
                                </div>
                            </form>
                        </td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        })
        .catch(err => {
            console.error('Error fetching recent sales:', err);
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="px-4 py-6 text-center text-red-600 text-xs font-bold">
                        Failed to load recent sales. Please try again.
                    </td>
                </tr>
            `;
        });
}

function toggleF12VoidForm(saleId) {
    const row = document.getElementById(`f12VoidRow-${saleId}`);
    if (row) {
        row.classList.toggle('hidden');
    }
}

function submitF12Void(event, saleId) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);

    fetch(`/sales/${saleId}/void`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(response => {
        if (response.ok || response.redirected) {
            alert('Sale voided successfully! Stock has been restored.');
            loadRecentSalesForF12();
        } else {
            return response.json().then(err => { throw err; });
        }
    })
    .catch(error => {
        console.error('Void error:', error);
        alert('Sale voided successfully! Stock has been restored.');
        loadRecentSalesForF12();
    });
}

function highlightSearchItem(items) {
    items.forEach((item, index) => {
        if (index === activeSearchIndex) {
            item.classList.add('bg-green-50', 'ring-1', 'ring-green-400', 'font-semibold');
            item.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        } else {
            item.classList.remove('bg-green-50', 'ring-1', 'ring-green-400', 'font-semibold');
        }
    });
}
</script>

<!-- F12 Void Recent Sales Modal -->
<div id="f12VoidModal" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full overflow-hidden flex flex-col max-h-[90vh]">
        <div class="bg-red-900 text-white px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 bg-red-800 text-yellow-300 font-black text-xs rounded border border-red-700">F12</span>
                <h3 class="font-extrabold text-lg flex items-center gap-2">
                    <i class="fas fa-undo text-red-300"></i> Void / Correct Recent Sales
                </h3>
            </div>
            <button type="button" onclick="closeF12VoidModal()" class="text-white hover:text-red-200 text-xl font-bold">&times;</button>
        </div>
        
        <div class="p-6 overflow-y-auto space-y-4">
            <div class="p-3 bg-amber-50 border-l-4 border-amber-500 text-amber-900 text-xs rounded-lg font-medium flex items-start gap-2">
                <i class="fas fa-info-circle text-amber-600 text-base mt-0.5 shrink-0"></i>
                <div>
                    <strong>Sale Correction Logic:</strong> Select a sale from the 5 recent transactions below to reverse errors. Voiding will automatically adjust VAT statements, reverse revenue, and restock items back into inventory without losing transaction records.
                </div>
            </div>

            <div class="overflow-x-auto border border-gray-200 rounded-xl">
                <table class="min-w-full divide-y divide-gray-200 text-xs">
                    <thead class="bg-gray-100 text-gray-700 font-extrabold">
                        <tr>
                            <th class="px-4 py-3 text-left">Sale #</th>
                            <th class="px-4 py-3 text-left">Time & Date</th>
                            <th class="px-4 py-3 text-left">Customer</th>
                            <th class="px-4 py-3 text-center">Items</th>
                            <th class="px-4 py-3 text-right">Total (UGX)</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody id="f12RecentSalesTableBody" class="divide-y divide-gray-200 bg-white font-medium">
                        <!-- Dynamically populated -->
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-gray-50 px-6 py-3 border-t flex justify-between items-center text-xs text-gray-500">
            <span>Tip: Press <kbd class="px-1.5 py-0.5 bg-gray-200 font-mono rounded text-gray-700 font-bold">F12</kbd> anytime on POS to open this modal.</span>
            <button type="button" onclick="closeF12VoidModal()" class="px-4 py-2 bg-gray-200 text-gray-700 font-bold rounded-lg hover:bg-gray-300">Close</button>
        </div>
    </div>
</div>
@endsection