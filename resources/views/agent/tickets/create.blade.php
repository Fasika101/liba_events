<x-app-layout>
    <x-slot name="header">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Sell a Ticket</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('agent.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('agent.tickets.index') }}">My Tickets</a></li>
                    <li class="breadcrumb-item active">Sell</li>
                </ol>
            </div>
        </div>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-7 col-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-ticket-alt mr-2"></i> New Ticket Sale
                    </h3>
                </div>
                <div class="card-body">
                    @if ($events->isEmpty())
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-calendar-times fa-3x mb-3 d-block"></i>
                            <strong>No active events available right now.</strong><br>
                            Please ask the admin to create an event.
                        </div>
                    @else
                        @if ($errors->any())
                            <div class="alert alert-danger alert-dismissible">
                                <button type="button" class="close" data-dismiss="alert">&times;</button>
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @php
                            $eventData = $events->mapWithKeys(fn($e) => [
                                $e->id => [
                                    'title'       => $e->title,
                                    'photo'       => $e->photo_path ? asset('storage/'.$e->photo_path) : null,
                                    'date'        => ($e->end_at ?? $e->start_at)
                                        ? \App\Helpers\EthiopianCalendar::format($e->end_at ?? $e->start_at)
                                        : null,
                                    'date_starts' => ($e->end_at && $e->start_at && $e->end_at->ne($e->start_at))
                                        ? \App\Helpers\EthiopianCalendar::format($e->start_at)
                                        : null,
                                    'price'       => (string) $e->price,
                                    'price_label' => $e->price . ' ' . $e->currency,
                                    'currency'    => $e->currency,
                                    'full'        => $e->capacity > 0 && $e->tickets_count >= $e->capacity,
                                    'capacity'    => $e->capacity,
                                    'sold'        => $e->tickets_count,
                                ]
                            ]);
                        @endphp

                        <form method="POST" action="{{ route('agent.tickets.store') }}" id="ticketForm">
                            @csrf

                            {{-- Step indicator --}}
                            <div class="d-flex align-items-center mb-4">
                                <div class="d-flex align-items-center justify-content-center rounded-circle bg-primary text-white mr-2"
                                     style="width:28px;height:28px;font-size:.85rem;font-weight:700;">1</div>
                                <span class="font-weight-bold mr-3">Fill Details</span>
                                <div class="flex-grow-1 border-top mx-2"></div>
                                <div class="d-flex align-items-center justify-content-center rounded-circle bg-secondary text-white mr-2"
                                     style="width:28px;height:28px;font-size:.85rem;font-weight:700;">2</div>
                                <span class="text-muted">Review &amp; Confirm</span>
                            </div>

                            <div class="form-group">
                                <label class="font-weight-bold">
                                    <i class="fas fa-calendar-alt mr-1 text-primary"></i> Select Event <span class="text-danger">*</span>
                                </label>
                                <select name="event_id" id="eventSelect"
                                        class="form-control form-control-lg @error('event_id') is-invalid @enderror"
                                        required onchange="updateEventPreview(this)">
                                    <option value="">— Choose an event —</option>
                                    @foreach ($events as $event)
                                        @php
                                            $eFull = $event->capacity > 0 && $event->tickets_count >= $event->capacity;
                                        @endphp
                                        <option value="{{ $event->id }}"
                                                @selected(old('event_id', request('event_id')) == $event->id)
                                                @disabled($eFull)>
                                            {{ $event->title }} — {{ $event->price }} {{ $event->currency }}{{ $eFull ? ' [FULL]' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('event_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Full-capacity alert (toggled by JS — no d-flex so inline display:none is respected) --}}
                            <div id="fullCapacityBanner" class="alert alert-danger mb-3" style="display:none;">
                                <i class="fas fa-ban mr-2"></i>
                                <strong>This event is at full capacity.</strong>
                                No more tickets can be sold for this event. Please select a different event.
                            </div>

                            {{-- Event mini-preview --}}
                            <div id="eventPreview" class="mb-3" style="display:none;">
                                <div class="card border-primary mb-0">
                                    <div class="row no-gutters">
                                        <div class="col-auto" id="previewImgWrap" style="display:none;">
                                            <img id="previewImg" src="" alt=""
                                                 style="width:100px;height:100px;object-fit:cover;border-radius:4px 0 0 4px;">
                                        </div>
                                        <div class="col d-flex align-items-center px-3 py-2">
                                            <div>
                                                <div class="font-weight-bold" id="previewTitle"></div>
                                                <div class="small mt-1">
                                                    <i class="fas fa-calendar mr-1 text-primary"></i>
                                                    <span id="previewDate" class="font-weight-bold text-dark"></span>
                                                </div>
                                                <div id="previewStartsRow" class="text-muted smaller mt-1" style="display:none;font-size:.8rem;"></div>
                                                <div class="mt-1">
                                                    <span class="badge badge-success" id="previewPrice" style="font-size:.9rem;"></span>
                                                    <span class="badge badge-secondary ml-1" id="previewCapacity" style="display:none;font-size:.85rem;"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="font-weight-bold">
                                    <i class="fas fa-user mr-1 text-primary"></i> Buyer Full Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="buyer_name" id="buyerName"
                                       value="{{ old('buyer_name') }}"
                                       required class="form-control @error('buyer_name') is-invalid @enderror"
                                       placeholder="Full name of the buyer">
                                @error('buyer_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="font-weight-bold">
                                        <i class="fas fa-envelope mr-1 text-primary"></i> Email
                                        <small class="text-muted font-weight-normal">(optional)</small>
                                    </label>
                                    <input type="email" name="buyer_email" id="buyerEmail"
                                           value="{{ old('buyer_email') }}"
                                           class="form-control @error('buyer_email') is-invalid @enderror"
                                           placeholder="email@example.com">
                                    @error('buyer_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="font-weight-bold">
                                        <i class="fas fa-phone mr-1 text-primary"></i> Phone <span class="text-danger">*</span>
                                    </label>

                                    {{-- Visible: flag + prefix + 9-digit input --}}
                                    <div class="input-group @error('buyer_phone') is-invalid @enderror">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text px-2" style="font-size:1.1rem;letter-spacing:.03em;">
                                                🇪🇹 <span class="ml-1 font-weight-bold text-dark" style="font-size:.9rem;">+251</span>
                                            </span>
                                        </div>
                                        <input type="tel" id="phoneDigits"
                                               class="form-control @error('buyer_phone') is-invalid @enderror"
                                               placeholder="9X XXX XXXX"
                                               maxlength="11"
                                               autocomplete="off"
                                               inputmode="numeric"
                                               value="{{ old('buyer_phone') ? ltrim(str_replace(['+251',' '], '', old('buyer_phone')), '') : '' }}">
                                        <div class="input-group-append">
                                            <span class="input-group-text px-2" id="phoneCounter"
                                                  style="font-size:.8rem;min-width:40px;justify-content:center;color:#6c757d;">0/9</span>
                                        </div>
                                    </div>

                                    {{-- Hidden field actually submitted --}}
                                    <input type="hidden" name="buyer_phone" id="buyerPhone"
                                           value="{{ old('buyer_phone') }}">

                                    {{-- Inline feedback --}}
                                    <div id="phoneFeedback" class="invalid-feedback" style="display:none;"></div>
                                    @error('buyer_phone')
                                        <div class="text-danger small mt-1">
                                            <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                                        </div>
                                    @enderror
                                    <small class="form-text text-muted"><span class="text-danger">Required.</span> Ethiopian mobile number — 9 digits after +251</small>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="font-weight-bold">
                                    <i class="fas fa-map-marker-alt mr-1 text-primary"></i> Address
                                    <small class="text-muted font-weight-normal">(optional)</small>
                                </label>
                                <input type="text" name="buyer_address" id="buyerAddress"
                                       value="{{ old('buyer_address') }}"
                                       class="form-control @error('buyer_address') is-invalid @enderror"
                                       placeholder="City, neighbourhood or any location detail">
                                @error('buyer_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-group">
                                <label class="font-weight-bold">
                                    <i class="fas fa-briefcase mr-1 text-primary"></i> Occupation
                                    <small class="text-muted font-weight-normal">(optional)</small>
                                </label>
                                <input type="text" name="buyer_occupation" id="buyerOccupation"
                                       value="{{ old('buyer_occupation') }}"
                                       class="form-control @error('buyer_occupation') is-invalid @enderror"
                                       placeholder="e.g. Engineer, Teacher, Student">
                                @error('buyer_occupation') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-group" id="yeneshaaAbatGroup">
                                <label class="font-weight-bold d-block mb-2">
                                    Yeneshaa Abat <span class="text-danger">*</span>
                                </label>
                                <div class="custom-control custom-radio">
                                    <input type="radio"
                                           name="yeneshaa_abat"
                                           id="yeneshaa_abat_yes"
                                           value="1"
                                           class="custom-control-input @error('yeneshaa_abat') is-invalid @enderror"
                                           {{ old('yeneshaa_abat') === '1' ? 'checked' : '' }}>
                                    <label class="custom-control-label font-weight-normal" for="yeneshaa_abat_yes">Yes</label>
                                </div>
                                <div class="custom-control custom-radio">
                                    <input type="radio"
                                           name="yeneshaa_abat"
                                           id="yeneshaa_abat_no"
                                           value="0"
                                           class="custom-control-input @error('yeneshaa_abat') is-invalid @enderror"
                                           {{ old('yeneshaa_abat') === '0' ? 'checked' : '' }}>
                                    <label class="custom-control-label font-weight-normal" for="yeneshaa_abat_no">No</label>
                                </div>
                                @error('yeneshaa_abat')
                                    <div class="text-danger small mt-1 d-block">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted d-block">You must choose one option.</small>
                            </div>

                            <div class="form-group">
                                <label class="font-weight-bold">
                                    <i class="fas fa-money-bill-wave mr-1 text-primary"></i> Ticket Price
                                </label>
                                <input type="text" id="pricePaid"
                                       class="form-control font-weight-bold text-success"
                                       placeholder="Select an event to see price"
                                       disabled readonly
                                       style="background:#f8f9fa;cursor:not-allowed;">
                                <small class="form-text text-muted">Price is set by the admin and cannot be changed.</small>
                            </div>

                            <hr>
                            <div class="d-flex justify-content-between align-items-center">
                                <a href="{{ route('agent.tickets.index') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left mr-1"></i> Cancel
                                </a>
                                <button type="button" id="reviewBtn" class="btn btn-warning btn-lg font-weight-bold">
                                    <i class="fas fa-eye mr-1"></i> Review &amp; Confirm
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @unless($events->isEmpty())

    {{-- Confirmation Modal --}}
    <div class="modal fade" id="confirmModal" tabindex="-1" role="dialog" data-backdrop="static">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title font-weight-bold">
                        <i class="fas fa-eye mr-2"></i> Review Before Confirming
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body p-0">

                    {{-- Event banner --}}
                    <div id="cm-imgWrap" style="display:none;">
                        <img id="cm-img" src="" alt=""
                             style="width:100%;max-height:200px;object-fit:cover;">
                    </div>

                    <div class="p-4">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="callout callout-info mb-0">
                                    <h6 class="text-uppercase text-muted mb-1" style="font-size:.7rem;letter-spacing:.07em;">Event</h6>
                                    <div class="font-weight-bold" id="cm-title" style="font-size:1.05rem;"></div>
                                    <div class="text-muted small mt-1">
                                        <i class="fas fa-calendar mr-1"></i>
                                        <span id="cm-date" class="font-weight-bold text-dark"></span>
                                    </div>
                                    <div id="cm-starts-row" class="text-muted mt-1" style="display:none;font-size:.75rem;"></div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="callout callout-success mb-0">
                                    <h6 class="text-uppercase text-muted mb-1" style="font-size:.7rem;letter-spacing:.07em;">Price to be Charged</h6>
                                    <div class="font-weight-bold text-success" id="cm-price" style="font-size:1.4rem;"></div>
                                </div>
                            </div>
                        </div>

                        <div class="card bg-light border mb-3">
                            <div class="card-body py-3">
                                <h6 class="text-uppercase text-muted mb-3" style="font-size:.7rem;letter-spacing:.07em;">
                                    <i class="fas fa-user mr-1"></i> Buyer Information
                                </h6>
                                <div class="row">
                                    <div class="col-sm-4 mb-2">
                                        <div class="text-muted small">Full Name</div>
                                        <div class="font-weight-bold" id="cm-name"></div>
                                    </div>
                                    <div class="col-sm-4 mb-2">
                                        <div class="text-muted small">Email</div>
                                        <div id="cm-email" class="text-break"></div>
                                    </div>
                                    <div class="col-sm-4 mb-2">
                                        <div class="text-muted small">Phone</div>
                                        <div id="cm-phone"></div>
                                    </div>
                                    <div class="col-12 mt-1" id="cm-address-wrap" style="display:none;">
                                        <div class="text-muted small">Address</div>
                                        <div id="cm-address"></div>
                                    </div>
                                    <div class="col-12 mt-1" id="cm-occupation-wrap" style="display:none;">
                                        <div class="text-muted small">Occupation</div>
                                        <div id="cm-occupation"></div>
                                    </div>
                                    <div class="col-12 mt-2 pt-2 border-top">
                                        <div class="text-muted small">Yeneshaa Abat</div>
                                        <div class="font-weight-bold" id="cm-yeneshaa"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            <strong>Please double-check all information.</strong>
                            Once confirmed, this sale will be recorded and a receipt will be generated.
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">
                        <i class="fas fa-edit mr-1"></i> Go Back &amp; Edit
                    </button>
                    <button type="button" id="confirmBtn" class="btn btn-success btn-lg font-weight-bold">
                        <i class="fas fa-check-circle mr-1"></i> Confirm &amp; Record Sale
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        var eventData = @json($eventData);

        /* ── Event mini-preview on select change ── */
        function updateEventPreview(sel) {
            var id      = sel.value;
            var preview = document.getElementById('eventPreview');
            var fullBanner = document.getElementById('fullCapacityBanner');
            var reviewBtn  = document.getElementById('reviewBtn');

            if (!id || !eventData[id]) {
                preview.style.display = 'none';
                if (fullBanner) fullBanner.style.display = 'none';
                reviewBtn.disabled = false;
                reviewBtn.title = '';
                return;
            }
            var d = eventData[id];

            // Only block when capacity is a positive number AND tickets meet/exceed it
            if (d.full === true) {
                if (fullBanner) fullBanner.style.display = 'block';
                reviewBtn.disabled = true;
                reviewBtn.title = 'This event is at full capacity';
            } else {
                if (fullBanner) fullBanner.style.display = 'none';
                reviewBtn.disabled = false;
                reviewBtn.title = '';
            }

            document.getElementById('previewTitle').textContent = d.title;
            document.getElementById('previewDate').textContent = d.date || '—';
            var ps = document.getElementById('previewStartsRow');
            if (d.date_starts) {
                ps.textContent = 'Starts: ' + d.date_starts;
                ps.style.display = '';
            } else {
                ps.textContent = '';
                ps.style.display = 'none';
            }
            document.getElementById('previewPrice').textContent = d.price_label;
            // Capacity badge
            var capBadge = document.getElementById('previewCapacity');
            if (d.capacity) {
                var left = Math.max(0, d.capacity - d.sold);
                capBadge.textContent = left + ' spot' + (left === 1 ? '' : 's') + ' left';
                capBadge.className = 'badge ml-1 ' + (left === 0 ? 'badge-danger' : 'badge-secondary');
                capBadge.style.display = '';
            } else {
                capBadge.style.display = 'none';
            }
            // Show fixed price in disabled field
            var priceInput = document.getElementById('pricePaid');
            priceInput.value = d.price + ' ' + d.currency;

            var imgWrap = document.getElementById('previewImgWrap');
            var img     = document.getElementById('previewImg');
            if (d.photo) { img.src = d.photo; imgWrap.style.display = ''; }
            else          { imgWrap.style.display = 'none'; }
            preview.style.display = '';
        }

        /* ── Auto-trigger on load if pre-selected ── */
        window.addEventListener('DOMContentLoaded', function () {
            var sel = document.getElementById('eventSelect');
            if (sel && sel.value) updateEventPreview(sel);
        });

        /* ── Review button: validate then open modal ── */
        document.getElementById('reviewBtn').addEventListener('click', function () {
            var eventSel  = document.getElementById('eventSelect');
            var buyerName = document.getElementById('buyerName');

            // Basic client-side required check
            if (!eventSel.value) {
                eventSel.focus();
                eventSel.classList.add('is-invalid');
                return;
            }

            // Block full events
            var d = eventData[eventSel.value];
            if (d && d.full === true) {
                eventSel.classList.add('is-invalid');
                return;
            }
            eventSel.classList.remove('is-invalid');

            if (!buyerName.value.trim()) {
                buyerName.focus();
                buyerName.classList.add('is-invalid');
                return;
            }
            buyerName.classList.remove('is-invalid');

            // Phone validation (9 digits required if entered)
            if (window.validatePhone && !window.validatePhone()) return;

            var yeneshaaYes = document.getElementById('yeneshaa_abat_yes');
            var yeneshaaNo = document.getElementById('yeneshaa_abat_no');
            var yeneshaaGroup = document.getElementById('yeneshaaAbatGroup');
            if (!yeneshaaYes.checked && !yeneshaaNo.checked) {
                yeneshaaYes.classList.add('is-invalid');
                yeneshaaNo.classList.add('is-invalid');
                yeneshaaGroup.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
            yeneshaaYes.classList.remove('is-invalid');
            yeneshaaNo.classList.remove('is-invalid');

            var eventId = eventSel.value;
            var d       = eventData[eventId];

            // Populate modal
            document.getElementById('cm-title').textContent = d.title;
            document.getElementById('cm-date').textContent = d.date || '—';
            var cmStarts = document.getElementById('cm-starts-row');
            if (d.date_starts) {
                cmStarts.textContent = 'Starts: ' + d.date_starts;
                cmStarts.style.display = '';
            } else {
                cmStarts.textContent = '';
                cmStarts.style.display = 'none';
            }
            document.getElementById('cm-price').textContent = d.price + ' ' + d.currency;
            document.getElementById('cm-name').textContent  = buyerName.value.trim();
            document.getElementById('cm-email').textContent = document.getElementById('buyerEmail').value || '—';
            document.getElementById('cm-phone').textContent = window.getPhoneDisplay ? window.getPhoneDisplay() : (document.getElementById('buyerPhone').value || '—');

            // Address (optional)
            var addressVal = document.getElementById('buyerAddress').value.trim();
            var addressWrap = document.getElementById('cm-address-wrap');
            if (addressVal) {
                document.getElementById('cm-address').textContent = addressVal;
                addressWrap.style.display = '';
            } else {
                addressWrap.style.display = 'none';
            }

            var occVal = document.getElementById('buyerOccupation').value.trim();
            var occWrap = document.getElementById('cm-occupation-wrap');
            if (occVal) {
                document.getElementById('cm-occupation').textContent = occVal;
                occWrap.style.display = '';
            } else {
                occWrap.style.display = 'none';
            }

            document.getElementById('cm-yeneshaa').textContent = yeneshaaYes.checked ? 'Yes' : 'No';

            var cmImg  = document.getElementById('cm-img');
            var cmWrap = document.getElementById('cm-imgWrap');
            if (d.photo) { cmImg.src = d.photo; cmWrap.style.display = ''; }
            else          { cmWrap.style.display = 'none'; }

            $('#confirmModal').modal('show');
        });

        /* ── Phone field: +251 prefix + 9-digit Ethiopian number ── */
        (function () {
            var digits  = document.getElementById('phoneDigits');
            var hidden  = document.getElementById('buyerPhone');
            var counter = document.getElementById('phoneCounter');
            var feedback = document.getElementById('phoneFeedback');

            function digitsOnly(v) {
                return v.replace(/\D/g, '').slice(0, 9);
            }

            function formatDisplay(raw) {
                // Format "912345678" → "91 234 5678"
                var d = raw.replace(/\D/g, '').slice(0, 9);
                if (d.length <= 2) return d;
                if (d.length <= 5) return d.slice(0,2) + ' ' + d.slice(2);
                return d.slice(0,2) + ' ' + d.slice(2,5) + ' ' + d.slice(5);
            }

            function rawDigits() {
                return digits.value.replace(/\D/g, '');
            }

            function updateCounter(raw) {
                var n = raw.length;
                counter.textContent = n + '/9';
                if (n === 0) {
                    counter.style.color = '#6c757d';
                } else if (n === 9) {
                    counter.style.color = '#28a745';
                } else {
                    counter.style.color = '#dc3545';
                }
            }

            function showPhoneError(msg) {
                digits.classList.add('is-invalid');
                feedback.textContent = msg;
                feedback.style.display = 'block';
            }

            function clearPhoneError() {
                digits.classList.remove('is-invalid');
                feedback.style.display = 'none';
            }

            function syncHidden() {
                var raw = rawDigits();
                hidden.value = raw.length === 9 ? '+251' + raw : (raw.length === 0 ? '' : '+251' + raw);
            }

            digits.addEventListener('input', function () {
                var raw = digitsOnly(digits.value);
                digits.value = formatDisplay(raw);
                updateCounter(raw);
                syncHidden();
                if (raw.length === 0 || raw.length === 9) clearPhoneError();
            });

            digits.addEventListener('blur', function () {
                var raw = rawDigits();
                if (raw.length === 0) {
                    showPhoneError('Phone number is required.');
                } else if (raw.length !== 9) {
                    showPhoneError('Enter exactly 9 digits after +251 (e.g. 91 234 5678). You entered ' + raw.length + '.');
                } else {
                    clearPhoneError();
                }
            });

            // Pre-fill formatting on page load (in case of old() value)
            if (digits.value) {
                var raw = digitsOnly(digits.value);
                digits.value = formatDisplay(raw);
                updateCounter(raw);
                syncHidden();
            }

            // Expose validation function for review button
            window.validatePhone = function () {
                var raw = rawDigits();
                if (raw.length === 0) {
                    showPhoneError('Phone number is required.');
                    digits.focus();
                    return false;
                }
                if (raw.length !== 9) {
                    showPhoneError('Phone number must be exactly 9 digits after +251. You entered ' + raw.length + '.');
                    digits.focus();
                    return false;
                }
                clearPhoneError();
                return true;
            };

            // Format display phone for modal
            window.getPhoneDisplay = function () {
                var raw = rawDigits();
                return raw.length === 9 ? '+251 ' + formatDisplay(raw) : (raw.length === 0 ? '—' : '+251 ' + formatDisplay(raw) + ' ⚠');
            };
        })();

        /* ── Confirm button: assemble full phone then submit ── */
        document.getElementById('confirmBtn').addEventListener('click', function () {
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Recording…';
            // Ensure hidden phone is set before submit
            var raw = document.getElementById('phoneDigits').value.replace(/\D/g,'');
            document.getElementById('buyerPhone').value = raw.length === 9 ? '+251' + raw : '';
            document.getElementById('ticketForm').submit();
        });

        ['yeneshaa_abat_yes', 'yeneshaa_abat_no'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.addEventListener('change', function () {
                document.getElementById('yeneshaa_abat_yes').classList.remove('is-invalid');
                document.getElementById('yeneshaa_abat_no').classList.remove('is-invalid');
            });
        });
    </script>
    @endpush

    @endunless
</x-app-layout>
