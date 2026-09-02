<x-app-layout>
    <x-slot name="header">
        <div class="row mb-2">
            <div class="col-sm-6"><h1 class="m-0">Bulk SMS</h1></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                    <li class="breadcrumb-item active">Bulk SMS</li>
                </ol>
            </div>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button>{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button>{{ session('error') }}</div>
    @endif

    @if (! $company->canSendSms())
        <div class="alert alert-warning">
            <i class="fas fa-key mr-1"></i>
            SMS is not enabled for your organization yet. The platform administrator must attach an API key before you can send messages.
            You can still request a custom sender ID add-on below.
        </div>
    @else
        <div class="alert alert-light border mb-3">
            <strong>{{ number_format($company->sms_credits) }}</strong> SMS credits remaining ·
            Sender ID: <strong>{{ $company->effectiveSenderId() ?? 'Platform default' }}</strong>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-7">
            @if ($company->canSendSms())
                <div class="card card-outline card-primary mb-4">
                    <div class="card-header"><h3 class="card-title mb-0">Compose bulk message</h3></div>
                    <form method="POST" action="{{ route('admin.bulk-sms.send') }}" id="bulk-sms-form">
                        @csrf
                        <div class="card-body">
                            <div class="form-group">
                                <label>Title (optional)</label>
                                <input type="text" name="title" class="form-control" value="{{ old('title') }}" maxlength="120">
                            </div>
                            <div class="form-group">
                                <label>Message</label>
                                <textarea name="message" rows="4" class="form-control" maxlength="480" required>{{ old('message') }}</textarea>
                                <small class="text-muted">Placeholders: {buyer_name}, {organization}, {event}, {sender_id}</small>
                            </div>

                            <div class="form-group">
                                <label>Send to event ticket buyers</label>
                                <select name="event_id" id="event-select" class="form-control">
                                    <option value="" data-count="0">— Do not use event list —</option>
                                    @foreach ($events as $event)
                                        <option value="{{ $event->id }}" data-count="{{ $eventRecipientCounts[$event->id] ?? 0 }}"
                                                @selected(old('event_id') == $event->id)>
                                            {{ $event->title }} ({{ $event->start_at?->format('M j, Y') }}) — {{ number_format($eventRecipientCounts[$event->id] ?? 0) }} buyers
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">All customers who bought a ticket for the selected event will be included.</small>
                                <div id="event-count-hint" class="alert alert-info py-2 px-3 mt-2 mb-0 small d-none"></div>
                            </div>

                            <div class="form-group">
                                <label>CRM customers <span class="text-muted font-weight-normal">({{ number_format($crmTotalCount) }} in CRM)</span></label>

                                <input type="hidden" name="crm_select_all" id="crm-select-all-input" value="0">
                                <div id="crm-hidden-inputs"></div>

                                <div class="crm-picker position-relative" id="crm-picker">
                                    <button type="button" class="form-control text-left d-flex align-items-center justify-content-between crm-picker-toggle" id="crm-picker-toggle" aria-haspopup="listbox" aria-expanded="false">
                                        <span id="crm-picker-label" class="text-muted">Click to select customers…</span>
                                        <i class="fas fa-chevron-down text-muted"></i>
                                    </button>
                                    <div class="crm-picker-menu border rounded bg-white shadow-sm d-none" id="crm-picker-menu">
                                        <div class="p-2 border-bottom bg-light">
                                            <input type="text" id="crm-list-filter" class="form-control form-control-sm" placeholder="Search by name or phone…" autocomplete="off">
                                            <div class="d-flex justify-content-between mt-2">
                                                <button type="button" class="btn btn-link btn-sm p-0" id="crm-select-all-btn">Select all</button>
                                                <button type="button" class="btn btn-link btn-sm p-0 text-danger" id="crm-clear-btn">Clear selection</button>
                                            </div>
                                        </div>
                                        <div id="crm-checkbox-list" class="crm-picker-options p-2">
                                            <p class="text-muted small text-center py-3 mb-0">Loading customers…</p>
                                        </div>
                                    </div>
                                </div>
                                <small class="text-muted">Click the field above to open the list. Check customers to include them, or use Select all.</small>
                            </div>

                            <div class="form-group mb-0">
                                <label>Manual phone numbers</label>
                                <textarea name="manual_phones" id="manual-phones" rows="4" class="form-control" placeholder="+251911234567&#10;+251922345678">{{ old('manual_phones') }}</textarea>
                                <small class="text-muted">One number per line. Format: +251 followed by 9 digits.</small>
                            </div>

                            <div id="recipient-preview" class="alert alert-secondary mt-3 mb-0 d-none">
                                <strong><span id="preview-total">0</span> text messages</strong> will be sent
                                <span id="preview-breakdown" class="d-block small text-muted mt-1"></span>
                                <span id="preview-credits-warning" class="d-block small text-danger mt-1 d-none"></span>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary" id="send-bulk-btn" @disabled($company->sms_credits < 1)>
                                <i class="fas fa-paper-plane mr-1"></i> <span id="send-btn-label">Send bulk SMS</span>
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            <div class="card card-outline card-warning">
                <div class="card-header"><h3 class="card-title mb-0">Custom sender ID add-on</h3></div>
                <div class="card-body">
                    @if ($company->sms_sender_id)
                        <p class="mb-0">Your custom sender ID: <strong>{{ $company->sms_sender_id }}</strong></p>
                    @elseif ($senderIdRequest)
                        @if ($senderIdRequest->status === 'pending')
                            <p class="text-muted mb-0">Request for <strong>{{ $senderIdRequest->requested_sender_id }}</strong> is pending review.</p>
                        @elseif ($senderIdRequest->status === 'awaiting_payment')
                            <p><strong>Pay {{ number_format($senderIdRequest->price_etb, 2) }} ETB</strong> to register sender ID <code>{{ $senderIdRequest->requested_sender_id }}</code></p>
                            <div class="bg-light border rounded p-3 mb-3 small">{!! nl2br(e($senderIdRequest->payment_instructions)) !!}</div>
                            <form method="POST" action="{{ route('admin.bulk-sms.sender-id.payment') }}" enctype="multipart/form-data">
                                @csrf
                                <div class="form-group">
                                    <label>Upload payment screenshot</label>
                                    <input type="file" name="payment_screenshot" class="form-control-file" accept="image/*" required>
                                </div>
                                <button type="submit" class="btn btn-warning btn-sm">Submit payment proof</button>
                            </form>
                        @elseif ($senderIdRequest->status === 'payment_submitted')
                            <p class="text-muted mb-0">Payment submitted — waiting for verification.</p>
                        @endif
                    @else
                        <p class="text-muted">Register your own 11-character sender name (paid add-on). Without it, messages use the platform default sender ID.</p>
                        <form method="POST" action="{{ route('admin.bulk-sms.sender-id.request') }}" class="form-inline">
                            @csrf
                            <input type="text" name="requested_sender_id" class="form-control form-control-sm mr-2 mb-2" maxlength="11"
                                   pattern="[A-Za-z0-9]{1,11}" placeholder="MYBRAND11" required style="max-width: 140px;">
                            <button type="submit" class="btn btn-sm btn-warning mb-2">Request sender ID</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card card-outline card-secondary">
                <div class="card-header"><h3 class="card-title mb-0">Recent bulk sends</h3></div>
                <ul class="list-group list-group-flush">
                    @forelse ($recentLogs as $log)
                        <li class="list-group-item">
                            <a href="{{ route('admin.bulk-sms.show', $log) }}">{{ $log->title ?: 'Bulk SMS' }}</a>
                            <div class="small text-muted">
                                {{ $log->success_count }}/{{ $log->recipient_count }} sent · {{ $log->created_at->format('M j, g:i A') }}
                            </div>
                        </li>
                    @empty
                        <li class="list-group-item text-muted text-center">No bulk SMS sent yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    @push('styles')
    <style>
        .crm-picker-toggle { cursor: pointer; background: #fff; }
        .crm-picker-toggle:focus { border-color: #80bdff; box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25); outline: 0; }
        .crm-picker-menu {
            position: absolute;
            z-index: 1050;
            left: 0;
            right: 0;
            top: calc(100% + 2px);
            max-height: 320px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .crm-picker-options {
            overflow-y: auto;
            max-height: 260px;
        }
        .crm-picker-menu .custom-control-label {
            cursor: pointer;
            width: 100%;
        }
    </style>
    @endpush

    @push('scripts')
    <script>
    (function () {
        var selected = {};
        var listCustomers = [];
        var crmSelectAll = false;
        var listUrl = @json(route('admin.bulk-sms.customers.list'));
        var previewUrl = @json(route('admin.bulk-sms.preview-count'));
        var creditsAvailable = @json($company->sms_credits);
        var crmTotalCount = @json($crmTotalCount);
        var form = document.getElementById('bulk-sms-form');
        if (!form) return;

        var picker = document.getElementById('crm-picker');
        var pickerToggle = document.getElementById('crm-picker-toggle');
        var pickerMenu = document.getElementById('crm-picker-menu');
        var pickerLabel = document.getElementById('crm-picker-label');
        var hiddenInputs = document.getElementById('crm-hidden-inputs');
        var previewBox = document.getElementById('recipient-preview');
        var previewTotal = document.getElementById('preview-total');
        var previewBreakdown = document.getElementById('preview-breakdown');
        var previewCreditsWarning = document.getElementById('preview-credits-warning');
        var sendBtn = document.getElementById('send-bulk-btn');
        var sendBtnLabel = document.getElementById('send-btn-label');
        var eventHint = document.getElementById('event-count-hint');
        var previewTimer = null;
        var filterTimer = null;
        var customersLoaded = false;

        function crmPhones() {
            return crmSelectAll ? [] : Object.keys(selected);
        }

        function openPicker() {
            pickerMenu.classList.remove('d-none');
            pickerToggle.setAttribute('aria-expanded', 'true');
            if (!customersLoaded) {
                loadCustomerList();
            }
            document.getElementById('crm-list-filter').focus();
        }

        function closePicker() {
            pickerMenu.classList.add('d-none');
            pickerToggle.setAttribute('aria-expanded', 'false');
        }

        function updatePickerLabel() {
            if (crmSelectAll) {
                pickerLabel.textContent = 'All ' + crmTotalCount.toLocaleString() + ' CRM customers selected';
                pickerLabel.classList.remove('text-muted');
                return;
            }
            var count = Object.keys(selected).length;
            if (count === 0) {
                pickerLabel.textContent = 'Click to select customers…';
                pickerLabel.classList.add('text-muted');
            } else if (count === 1) {
                var one = selected[Object.keys(selected)[0]];
                pickerLabel.textContent = (one.name || one.phone) + ' selected';
                pickerLabel.classList.remove('text-muted');
            } else {
                pickerLabel.textContent = count.toLocaleString() + ' customers selected';
                pickerLabel.classList.remove('text-muted');
            }
        }

        function syncHiddenInputs() {
            hiddenInputs.innerHTML = '';
            if (crmSelectAll) {
                return;
            }
            Object.keys(selected).forEach(function (phone) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'crm_phones[]';
                input.value = phone;
                hiddenInputs.appendChild(input);
            });
        }

        function setCrmSelectAll(on) {
            crmSelectAll = on;
            document.getElementById('crm-select-all-input').value = on ? '1' : '0';
            if (on) {
                selected = {};
                syncCheckboxListChecks();
            }
            updatePickerLabel();
            syncHiddenInputs();
            schedulePreview();
        }

        function updateEventHint() {
            var sel = document.getElementById('event-select');
            var opt = sel.options[sel.selectedIndex];
            var count = parseInt(opt.getAttribute('data-count') || '0', 10);
            if (sel.value && count >= 0) {
                eventHint.classList.remove('d-none');
                eventHint.innerHTML = '<i class="fas fa-info-circle mr-1"></i> This event has <strong>' + count + '</strong> ticket buyer(s) with a phone number.';
            } else {
                eventHint.classList.add('d-none');
            }
        }

        function schedulePreview() {
            clearTimeout(previewTimer);
            previewTimer = setTimeout(refreshPreview, 300);
        }

        function refreshPreview() {
            var body = new FormData();
            body.append('_token', @json(csrf_token()));
            var eventId = document.getElementById('event-select').value;
            if (eventId) body.append('event_id', eventId);
            body.append('manual_phones', document.getElementById('manual-phones').value || '');
            if (crmSelectAll) {
                body.append('crm_select_all', '1');
            } else {
                crmPhones().forEach(function (phone) {
                    body.append('crm_phones[]', phone);
                });
            }

            fetch(previewUrl, { method: 'POST', body: body, headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    var total = data.total || 0;
                    if (total === 0) {
                        previewBox.classList.add('d-none');
                        sendBtnLabel.textContent = 'Send bulk SMS';
                        return;
                    }
                    previewBox.classList.remove('d-none');
                    previewTotal.textContent = total.toLocaleString();
                    var parts = [];
                    if (data.event_count > 0) parts.push(data.event_count + ' from event');
                    if (data.manual_count > 0) parts.push(data.manual_count + ' manual');
                    if (data.crm_select_all) {
                        parts.push('all CRM (' + (data.crm_count || crmTotalCount) + ')');
                    } else if (data.crm_count > 0) {
                        parts.push(data.crm_count + ' from CRM');
                    }
                    previewBreakdown.textContent = parts.length ? ('Includes: ' + parts.join(', ') + ' (duplicates merged)') : '';
                    if (!data.has_enough_credits) {
                        previewCreditsWarning.classList.remove('d-none');
                        previewCreditsWarning.textContent = 'You only have ' + creditsAvailable + ' credits. Purchase more before sending.';
                        sendBtn.disabled = true;
                    } else {
                        previewCreditsWarning.classList.add('d-none');
                        sendBtn.disabled = creditsAvailable < 1;
                    }
                    sendBtnLabel.textContent = 'Send ' + total.toLocaleString() + ' SMS';
                });
        }

        function toggleCustomer(row, checked) {
            if (crmSelectAll) {
                setCrmSelectAll(false);
            }
            if (checked) {
                selected[row.phone] = row;
            } else {
                delete selected[row.phone];
            }
            updatePickerLabel();
            syncHiddenInputs();
            schedulePreview();
        }

        function renderCustomerList(customers) {
            listCustomers = customers;
            var checkboxList = document.getElementById('crm-checkbox-list');
            checkboxList.innerHTML = '';
            if (!customers.length) {
                checkboxList.innerHTML = '<p class="text-muted small text-center py-3 mb-0">No customers found.</p>';
                return;
            }
            customers.forEach(function (row, index) {
                var label = row.name + ' — ' + row.phone + (row.event ? ' (' + row.event + ')' : '');
                var id = 'crm-cb-' + index + '-' + row.phone.replace(/\D/g, '');
                var div = document.createElement('div');
                div.className = 'custom-control custom-checkbox mb-1';
                div.innerHTML = '<input type="checkbox" class="custom-control-input crm-pick" id="' + id + '" data-phone="' + row.phone + '">' +
                    '<label class="custom-control-label small" for="' + id + '">' + label + '</label>';
                checkboxList.appendChild(div);
            });
            checkboxList.querySelectorAll('.crm-pick').forEach(function (cb) {
                cb.checked = crmSelectAll || !!selected[cb.getAttribute('data-phone')];
                cb.addEventListener('change', function () {
                    var row = listCustomers.find(function (c) { return c.phone === cb.getAttribute('data-phone'); });
                    if (row) toggleCustomer(row, cb.checked);
                });
            });
        }

        function syncCheckboxListChecks() {
            document.querySelectorAll('.crm-pick').forEach(function (cb) {
                cb.checked = crmSelectAll || !!selected[cb.getAttribute('data-phone')];
            });
        }

        function loadCustomerList() {
            var q = document.getElementById('crm-list-filter').value.trim();
            var checkboxList = document.getElementById('crm-checkbox-list');
            checkboxList.innerHTML = '<p class="text-muted small text-center py-3 mb-0">Loading…</p>';
            fetch(listUrl + (q ? ('?q=' + encodeURIComponent(q)) : ''), { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    customersLoaded = true;
                    renderCustomerList(data.customers || []);
                });
        }

        pickerToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            if (pickerMenu.classList.contains('d-none')) {
                openPicker();
            } else {
                closePicker();
            }
        });

        pickerMenu.addEventListener('click', function (e) {
            e.stopPropagation();
        });

        document.addEventListener('click', function () {
            closePicker();
        });

        document.getElementById('crm-select-all-btn').addEventListener('click', function (e) {
            e.preventDefault();
            setCrmSelectAll(true);
            closePicker();
        });

        document.getElementById('crm-clear-btn').addEventListener('click', function (e) {
            e.preventDefault();
            setCrmSelectAll(false);
            selected = {};
            syncCheckboxListChecks();
            updatePickerLabel();
            syncHiddenInputs();
            schedulePreview();
        });

        document.getElementById('crm-list-filter').addEventListener('input', function () {
            clearTimeout(filterTimer);
            filterTimer = setTimeout(loadCustomerList, 250);
        });

        document.getElementById('crm-list-filter').addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closePicker();
            }
        });

        document.getElementById('event-select').addEventListener('change', function () {
            updateEventHint();
            schedulePreview();
        });
        document.getElementById('manual-phones').addEventListener('input', schedulePreview);

        updatePickerLabel();
        updateEventHint();
        schedulePreview();
    })();
    </script>
    @endpush
</x-app-layout>
