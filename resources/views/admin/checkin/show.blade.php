<x-app-layout>
    <x-slot name="header">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Check-In — {{ $event->title }}</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route($indexRoute) }}">Check-In</a></li>
                    <li class="breadcrumb-item active">{{ $event->title }}</li>
                </ol>
            </div>
        </div>
    </x-slot>

    <style>
        /* ── Result panel (manual / fallback) ── */
        #result-panel {
            display: none;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            transition: all .25s ease;
        }
        #result-panel.valid      { background: #d1fae5; border: 2px solid #10b981; }
        #result-panel.already    { background: #fef9c3; border: 2px solid #f59e0b; }
        #result-panel.invalid    { background: #fee2e2; border: 2px solid #ef4444; }
        #result-panel.wrong      { background: #fce7f3; border: 2px solid #db2777; }

        /* ── Scanner box + on-camera overlay ── */
        #qr-scanner-wrap {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            background: #000;
            min-height: 220px;
        }
        #qr-reader { border-radius: 12px; overflow: hidden; background: #000; }
        #qr-reader video { border-radius: 12px; }
        #scan-overlay {
            display: none;
            position: absolute;
            inset: 0;
            z-index: 5;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            text-align: center;
            backdrop-filter: blur(2px);
        }
        #scan-overlay.is-visible { display: flex; }
        #scan-overlay.valid   { background: rgba(16, 185, 129, 0.88); color: #fff; }
        #scan-overlay.already { background: rgba(245, 158, 11, 0.9); color: #111; }
        #scan-overlay.invalid,
        #scan-overlay.wrong   { background: rgba(239, 68, 68, 0.9); color: #fff; }
        #scan-overlay .ov-icon { font-size: 2.6rem; line-height: 1; margin-bottom: .4rem; }
        #scan-overlay .ov-status { font-size: 1.25rem; font-weight: 800; letter-spacing: .02em; }
        #scan-overlay .ov-buyer { font-size: 1rem; font-weight: 600; margin-top: .35rem; }
        #scan-overlay .ov-detail { font-size: .85rem; margin-top: .25rem; opacity: .92; }

        /* ── Counter cards ── */
        .stat-card { border-radius: 12px; padding: 1rem 1.2rem; text-align: center; }
        .stat-card .stat-num { font-size: 2rem; font-weight: 800; line-height: 1; }
        .stat-card .stat-lbl { font-size: .75rem; text-transform: uppercase;
                               letter-spacing: .06em; color: #64748b; margin-top: .2rem; }
    </style>

    {{-- ── Stats row ──────────────────────────────────────────── --}}
    <div class="row mb-4">
        <div class="col-4">
            <div class="stat-card bg-white border">
                <div class="stat-num text-info" id="stat-total">{{ $totalTickets }}</div>
                <div class="stat-lbl">Total Tickets</div>
            </div>
        </div>
        <div class="col-4">
            <div class="stat-card bg-white border">
                <div class="stat-num text-success" id="stat-checked">{{ $checkedInCount }}</div>
                <div class="stat-lbl">Checked In</div>
            </div>
        </div>
        <div class="col-4">
            <div class="stat-card bg-white border">
                <div class="stat-num text-warning" id="stat-remaining">{{ $totalTickets - $checkedInCount }}</div>
                <div class="stat-lbl">Remaining</div>
            </div>
        </div>
    </div>

    <div class="row">

        {{-- ── Left: Scanner + manual input ─────────────────── --}}
        <div class="col-lg-5 mb-4">

            {{-- Camera scanner (confirmation overlays on the live camera) --}}
            <div class="card card-outline card-info mb-3">
                <div class="card-header">
                    <h3 class="card-title mb-0"><i class="fas fa-camera mr-2"></i> Scan QR Code</h3>
                </div>
                <div class="card-body p-2">
                    <div class="d-flex flex-wrap align-items-center mb-2 px-1" style="gap:.5rem;">
                        <button type="button" id="startScanBtn" class="btn btn-info btn-block-mobile">
                            <i class="fas fa-play mr-1"></i> Start Camera
                        </button>
                        <button type="button" id="stopScanBtn" class="btn btn-secondary btn-block-mobile" style="display:none;">
                            <i class="fas fa-stop mr-1"></i> Stop
                        </button>
                    </div>
                    <div id="qr-scanner-wrap">
                        <div id="qr-reader" style="width:100%;min-height:80px;"></div>
                        <div id="scan-overlay" aria-live="assertive"></div>
                    </div>
                    <p class="text-muted text-center mt-2 mb-0" style="font-size:.8rem;" id="scanHint">
                        Point the camera at the QR code — confirmation appears on screen instantly.
                    </p>
                </div>
            </div>

            {{-- Result panel (manual entry) --}}
            <div id="result-panel" class="mb-3"></div>

            {{-- Manual entry --}}
            <div class="card card-outline card-primary mb-3">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-keyboard mr-2"></i> Enter Ticket Code</h3>
                </div>
                <div class="card-body">
                    <form id="manualForm" autocomplete="off">
                        <div class="input-group">
                            <input type="text"
                                   id="manualCode"
                                   class="form-control form-control-lg text-uppercase"
                                   placeholder="e.g. ADD-00001"
                                   autofocus
                                   style="letter-spacing:.08em;font-family:monospace;font-size:1.1rem;">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-check mr-1"></i> Verify
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ── Right: All checked-in tickets ──────────────────── --}}
        <div class="col-lg-7 mb-4">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-user-check mr-2"></i>
                        Checked-In Tickets
                        <span class="badge badge-success ml-1" id="checkedBadge">{{ $checkedInCount }}</span>
                    </h3>
                    <div class="card-tools">
                        <input type="text" id="searchChecked"
                               class="form-control form-control-sm"
                               placeholder="Search name / code…"
                               style="width:180px;">
                    </div>
                </div>
                <div class="card-body p-0" style="max-height:520px;overflow-y:auto;">
                    <table class="table table-sm table-hover mb-0" id="checkedTable">
                        <thead class="thead-light sticky-top">
                            <tr>
                                <th style="width:36px;">#</th>
                                <th>Buyer</th>
                                <th>Phone</th>
                                <th>Code</th>
                                <th>Checked In</th>
                            </tr>
                        </thead>
                        <tbody id="checkedTbody">
                            @forelse($allCheckIns as $i => $t)
                                <tr>
                                    <td class="text-muted">{{ $i + 1 }}</td>
                                    <td class="font-weight-bold">{{ $t->buyer_name }}</td>
                                    <td class="text-muted small">{{ $t->buyer_phone ?? '—' }}</td>
                                    <td><code>{{ $t->ticket_code }}</code></td>
                                    <td>
                                        <span class="badge badge-success">
                                            {{ $t->checked_in_at->format('H:i') }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr id="emptyRow">
                                    <td colspan="5" class="text-center text-muted py-4">
                                        No check-ins yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    @push('scripts')
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

    <script>
    (function () {
        const verifyUrl  = @json($verifyUrl);
        const csrfToken  = document.querySelector('meta[name="csrf-token"]').content;

        let html5QrCode  = null;
        let lastScanned  = '';
        let lastTime     = 0;
        let isProcessing = false;
        let rowCounter   = {{ $checkedInCount }};

        // ── Verify ────────────────────────────────────────────
        function verifyCode(code) {
            code = code.trim().toUpperCase();
            if (!code || isProcessing) return;

            const now = Date.now();
            if (code === lastScanned && (now - lastTime) < 3000) return;
            lastScanned  = code;
            lastTime     = now;
            isProcessing = true;

            fetch(verifyUrl, {
                method:  'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept':       'application/json',
                },
                body: JSON.stringify({ code }),
            })
            .then(r => r.json())
            .then(data => {
                showResult(data);
                if (data.status === 'valid') {
                    prependTableRow(data);
                    updateStats();
                }
            })
            .catch(() => showResult({ status: 'invalid', message: 'Network error. Try again.' }))
            .finally(() => { isProcessing = false; });
        }

        // ── Result UI (on-camera overlay + panel) ─────────────
        function resultCopy(data) {
            switch (data.status) {
                case 'valid':
                    return {
                        cls: 'valid',
                        icon: '✅',
                        statusText: 'ENTRY GRANTED',
                        buyer: data.buyer_name + (data.buyer_phone && data.buyer_phone !== '—' ? '  ·  ' + data.buyer_phone : ''),
                        detail: data.ticket_code,
                    };
                case 'already_used':
                    return {
                        cls: 'already',
                        icon: '⚠️',
                        statusText: 'ALREADY CHECKED IN',
                        buyer: data.buyer_name + (data.buyer_phone && data.buyer_phone !== '—' ? '  ·  ' + data.buyer_phone : ''),
                        detail: 'First checked in at ' + data.checked_in_at,
                    };
                case 'wrong_event':
                    return {
                        cls: 'wrong',
                        icon: '🚫',
                        statusText: 'WRONG EVENT',
                        buyer: '',
                        detail: data.message,
                    };
                default:
                    return {
                        cls: 'invalid',
                        icon: '❌',
                        statusText: 'INVALID TICKET',
                        buyer: '',
                        detail: data.message || 'Ticket code not found.',
                    };
            }
        }

        function showResult(data) {
            const copy = resultCopy(data);
            const panel = document.getElementById('result-panel');
            const overlay = document.getElementById('scan-overlay');
            const cameraRunning = !!(html5QrCode && document.getElementById('stopScanBtn').style.display !== 'none');

            const bodyHtml =
                '<div class="ov-icon">' + copy.icon + '</div>' +
                '<div class="ov-status">' + escHtml(copy.statusText) + '</div>' +
                (copy.buyer ? '<div class="ov-buyer">' + escHtml(copy.buyer) + '</div>' : '') +
                (copy.detail ? '<div class="ov-detail">' + escHtml(copy.detail) + '</div>' : '');

            // Always show confirmation on the camera when it is open.
            if (cameraRunning && overlay) {
                overlay.className = copy.cls + ' is-visible';
                overlay.innerHTML = bodyHtml;
                clearTimeout(overlay._hideTimer);
                overlay._hideTimer = setTimeout(function () {
                    overlay.classList.remove('is-visible');
                }, data.status === 'valid' ? 2200 : 2800);
            }

            panel.className = copy.cls;
            panel.innerHTML =
                '<div style="font-size:3rem;line-height:1;margin-bottom:.5rem;">' + copy.icon + '</div>' +
                '<div style="font-size:1.4rem;font-weight:800;margin-bottom:.25rem;">' + escHtml(copy.statusText) + '</div>' +
                (copy.buyer ? '<div style="font-size:1rem;font-weight:600;">' + escHtml(copy.buyer) + '</div>' : '') +
                (copy.detail ? '<div style="font-size:.88rem;margin-top:.25rem;color:#475569;">' + escHtml(copy.detail) + '</div>' : '');
            panel.style.display = cameraRunning ? 'none' : 'block';

            if (data.status === 'valid' && !cameraRunning) {
                setTimeout(() => { panel.style.display = 'none'; }, 5000);
            }

            try {
                if (navigator.vibrate) {
                    navigator.vibrate(data.status === 'valid' ? [40, 30, 40] : [80]);
                }
            } catch (e) {}

            document.getElementById('manualCode').value = '';
        }

        // ── Prepend a new row to the checked-in table ─────────
        function prependTableRow(data) {
            const tbody   = document.getElementById('checkedTbody');
            const emptyRow = document.getElementById('emptyRow');
            if (emptyRow) emptyRow.remove();

            rowCounter++;
            const now  = new Date();
            const time = now.toTimeString().slice(0, 5);

            const tr = document.createElement('tr');
            tr.style.background = '#d1fae5';
            tr.innerHTML =
                '<td class="text-muted">' + rowCounter + '</td>' +
                '<td class="font-weight-bold">' + escHtml(data.buyer_name) + '</td>' +
                '<td class="text-muted small">' + escHtml(data.buyer_phone || '—') + '</td>' +
                '<td><code>' + escHtml(data.ticket_code) + '</code></td>' +
                '<td><span class="badge badge-success">' + time + '</span></td>';

            tbody.insertBefore(tr, tbody.firstChild);

            // Fade the highlight out after 3 seconds
            setTimeout(() => { tr.style.background = ''; }, 3000);
        }

        // ── Update stat counters ──────────────────────────────
        function updateStats() {
            const checkedEl   = document.getElementById('stat-checked');
            const remainingEl = document.getElementById('stat-remaining');
            const badge       = document.getElementById('checkedBadge');
            const newChecked  = parseInt(checkedEl.textContent) + 1;
            const total       = parseInt(document.getElementById('stat-total').textContent);
            checkedEl.textContent   = newChecked;
            remainingEl.textContent = Math.max(0, total - newChecked);
            if (badge) badge.textContent = newChecked;
        }

        // ── Manual form ───────────────────────────────────────
        document.getElementById('manualForm').addEventListener('submit', function (e) {
            e.preventDefault();
            verifyCode(document.getElementById('manualCode').value);
        });

        // ── Camera scanner ────────────────────────────────────
        document.getElementById('startScanBtn').addEventListener('click', function () {
            this.style.display = 'none';
            document.getElementById('stopScanBtn').style.display = '';
            html5QrCode = new Html5Qrcode('qr-reader');
            html5QrCode.start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: { width: 250, height: 250 } },
                (decodedText) => { verifyCode(decodedText); }
            ).catch(() => {
                document.getElementById('scanHint').textContent =
                    'Camera access denied. Use manual entry.';
                document.getElementById('startScanBtn').style.display = '';
                document.getElementById('stopScanBtn').style.display = 'none';
            });
        });

        document.getElementById('stopScanBtn').addEventListener('click', function () {
            if (html5QrCode) {
                html5QrCode.stop().then(() => {
                    document.getElementById('qr-reader').innerHTML = '';
                });
            }
            this.style.display = 'none';
            document.getElementById('startScanBtn').style.display = '';
        });

        // ── Live search on the checked-in table ───────────────
        document.getElementById('searchChecked').addEventListener('input', function () {
            const q    = this.value.toLowerCase();
            const rows = document.querySelectorAll('#checkedTbody tr');
            rows.forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });

        // ── Utility ───────────────────────────────────────────
        function escHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }
    })();
    </script>
    @endpush
</x-app-layout>
