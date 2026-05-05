@csrf

@php
    use App\Helpers\EthiopianCalendar;

    // Pre-fill Ethiopian values for edit mode
    $ethStart = $event->start_at
        ? EthiopianCalendar::toEthiopian($event->start_at)
        : null;
    $ethEnd = $event->end_at
        ? EthiopianCalendar::toEthiopian($event->end_at)
        : null;

    // Ranges
    $ethYears  = range(2010, 2030);
    $ethMonths = EthiopianCalendar::MONTHS_EN;
@endphp

<div class="form-row">
    <div class="form-group col-md-7">
        <label class="font-weight-bold">Title <span class="text-danger">*</span></label>
        <input type="text" name="title" value="{{ old('title', $event->title) }}"
               required class="form-control @error('title') is-invalid @enderror"
               placeholder="Event title">
        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="form-group col-md-3">
        <label class="font-weight-bold">Price <span class="text-danger">*</span></label>
        <input type="number" name="price" step="0.01" min="0"
               value="{{ old('price', $event->price) }}"
               required class="form-control @error('price') is-invalid @enderror">
        @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="form-group col-md-2">
        <label class="font-weight-bold">Currency <span class="text-danger">*</span></label>
        <input type="text" name="currency"
               value="{{ old('currency', $event->currency ?? 'ETB') }}"
               maxlength="3" required
               class="form-control text-uppercase @error('currency') is-invalid @enderror">
        @error('currency') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

{{-- ── Ethiopian Date Pickers ───────────────────────────────────────────── --}}
<div class="card card-outline card-warning mb-3">
    <div class="card-header py-2 bg-light">
        <h6 class="mb-0 font-weight-bold text-dark">
            <i class="fas fa-calendar-check mr-2 text-warning"></i> Event dates <span class="text-danger">*</span>
        </h6>
        <small class="text-muted d-block mt-1 mb-0 font-weight-normal">
            <strong class="text-dark">End date</strong> is the main date shown on buyer tickets. Set the range so the end day matches the event attendees should use.
        </small>
    </div>
    <div class="card-body py-3">

        {{-- End Date (priority — shown on tickets) --}}
        <label class="font-weight-bold">End date (event day on ticket) <span class="text-danger">*</span></label>
        @error('end_at') <div class="text-danger small mb-1">{{ $message }}</div> @enderror
        <div class="form-row align-items-end mb-1">
            <div class="form-group col-3 col-md-2 mb-2">
                <label class="small text-muted">Day</label>
                <select id="eth_day_end" class="form-control form-control-sm" onchange="ethConvert('end')">
                    <option value="">Day</option>
                    @for ($d = 1; $d <= 30; $d++)
                        <option value="{{ $d }}" @selected(old('eth_day_end', $ethEnd['day'] ?? '') == $d)>{{ $d }}</option>
                    @endfor
                </select>
            </div>
            <div class="form-group col-5 col-md-4 mb-2">
                <label class="small text-muted">Month</label>
                <select id="eth_month_end" class="form-control form-control-sm" onchange="ethConvert('end')">
                    <option value="">Month</option>
                    @foreach ($ethMonths as $num => $name)
                        <option value="{{ $num }}" @selected(old('eth_month_end', $ethEnd['month'] ?? '') == $num)>
                            {{ $num }}. {{ $name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-4 col-md-3 mb-2">
                <label class="small text-muted">Year (E.C.)</label>
                <select id="eth_year_end" class="form-control form-control-sm" onchange="ethConvert('end')">
                    <option value="">Year</option>
                    @foreach ($ethYears as $y)
                        <option value="{{ $y }}" @selected(old('eth_year_end', $ethEnd['year'] ?? '') == $y)>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-3 mb-2 text-muted small d-flex align-items-center" style="margin-top:-4px">
                <i class="fas fa-exchange-alt mr-1 text-primary"></i>
                <span id="greg_end_preview" class="font-italic">
                    {{ $ethEnd ? '= ' . $event->end_at->format('M d, Y') . ' G.C.' : '' }}
                </span>
            </div>
        </div>
        <input type="hidden" name="end_at" id="end_at_hidden"
               value="{{ old('end_at', optional($event->end_at)->format('Y-m-d')) }}">

        <hr class="my-3">

        {{-- Start Date --}}
        <label class="font-weight-bold mt-1">Start date <span class="text-danger">*</span>
            <small class="text-muted font-weight-normal">(when the event period begins)</small>
        </label>
        @error('start_at') <div class="text-danger small mb-1">{{ $message }}</div> @enderror
        <div class="form-row align-items-end mb-1">
            <div class="form-group col-3 col-md-2 mb-2">
                <label class="small text-muted">Day</label>
                <select id="eth_day_start" class="form-control form-control-sm" onchange="ethConvert('start')">
                    <option value="">Day</option>
                    @for ($d = 1; $d <= 30; $d++)
                        <option value="{{ $d }}" @selected(old('eth_day_start', $ethStart['day'] ?? '') == $d)>{{ $d }}</option>
                    @endfor
                </select>
            </div>
            <div class="form-group col-5 col-md-4 mb-2">
                <label class="small text-muted">Month</label>
                <select id="eth_month_start" class="form-control form-control-sm" onchange="ethConvert('start')">
                    <option value="">Month</option>
                    @foreach ($ethMonths as $num => $name)
                        <option value="{{ $num }}" @selected(old('eth_month_start', $ethStart['month'] ?? '') == $num)>
                            {{ $num }}. {{ $name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-4 col-md-3 mb-2">
                <label class="small text-muted">Year (E.C.)</label>
                <select id="eth_year_start" class="form-control form-control-sm" onchange="ethConvert('start')">
                    <option value="">Year</option>
                    @foreach ($ethYears as $y)
                        <option value="{{ $y }}" @selected(old('eth_year_start', $ethStart['year'] ?? '') == $y)>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-3 mb-2 text-muted small d-flex align-items-center" style="margin-top:-4px">
                <i class="fas fa-exchange-alt mr-1 text-primary"></i>
                <span id="greg_start_preview" class="font-italic">
                    {{ $ethStart ? '= ' . $event->start_at->format('M d, Y') . ' G.C.' : '' }}
                </span>
            </div>
        </div>
        {{-- Hidden Gregorian value submitted to server --}}
        <input type="hidden" name="start_at" id="start_at_hidden"
               value="{{ old('start_at', optional($event->start_at)->format('Y-m-d')) }}">

    </div>
</div>

<div class="form-row">
    <div class="form-group col-md-2">
        <label class="font-weight-bold">Capacity <small class="text-muted font-weight-normal">(optional)</small></label>
        <input type="number" name="capacity" min="0"
               value="{{ old('capacity', $event->capacity) }}"
               class="form-control @error('capacity') is-invalid @enderror"
               placeholder="Unlimited">
        @error('capacity') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="form-group col-md-2">
        <label class="font-weight-bold">Status <span class="text-danger">*</span></label>
        <select name="status" class="form-control @error('status') is-invalid @enderror">
            @foreach (['active', 'draft', 'archived'] as $s)
                <option value="{{ $s }}" @selected(old('status', $event->status ?? 'active') === $s)>
                    {{ ucfirst($s) }}
                </option>
            @endforeach
        </select>
        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="form-group">
    <label class="font-weight-bold">Description</label>
    <textarea name="description" rows="4"
              class="form-control @error('description') is-invalid @enderror"
              placeholder="Describe this event…">{{ old('description', $event->description) }}</textarea>
    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="form-group">
    <label class="font-weight-bold">
        <i class="fas fa-image mr-1 text-primary"></i> Event Photo
        <small class="text-muted font-weight-normal">(optional, max 2 MB)</small>
    </label>

    @if ($event->photo_path)
        <div class="mb-2 d-flex align-items-start">
            <img src="{{ asset('storage/' . $event->photo_path) }}"
                 alt="Current event photo"
                 class="img-thumbnail"
                 style="max-height:160px; max-width:260px; object-fit:cover; border-radius:6px;">
            <div class="ml-3 text-muted small mt-1">
                <i class="fas fa-check-circle text-success mr-1"></i> Current photo<br>
                <span class="text-warning">Upload a new image below to replace it.</span>
            </div>
        </div>
    @endif

    <div class="custom-file">
        <input type="file" name="photo" accept="image/*"
               class="custom-file-input @error('photo') is-invalid @enderror"
               id="photoInput"
               onchange="previewPhoto(this)">
        <label class="custom-file-label" for="photoInput">Choose image…</label>
        @error('photo') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div id="photoPreviewWrap" class="mt-2" style="display:none;">
        <img id="photoPreview" src="" alt="Preview"
             class="img-thumbnail"
             style="max-height:160px; max-width:260px; object-fit:cover; border-radius:6px;">
        <div class="text-muted small mt-1"><i class="fas fa-eye mr-1"></i> New photo preview</div>
    </div>
</div>

<hr>
<div class="d-flex justify-content-between align-items-center">
    <a href="{{ route('admin.events.index') }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left mr-1"></i> Cancel
    </a>
    <button type="submit" class="btn btn-primary px-4">
        <i class="fas fa-save mr-1"></i> Save Event
    </button>
</div>

@push('scripts')
<script>
/* ── Ethiopian ↔ Gregorian converter (JS mirror of PHP helper) ── */
var ETH_EPOCH_JDN = 1724221;

function ethToJDN(y, m, d) {
    var year4       = Math.floor((y - 1) / 4);
    var yearInCycle = (y - 1) % 4;
    var jdn = ETH_EPOCH_JDN + year4 * 1461;
    if (yearInCycle >= 1) jdn += 365;
    if (yearInCycle >= 2) jdn += 365;
    if (yearInCycle >= 3) jdn += 365;
    jdn += (m - 1) * 30 + (d - 1);
    return jdn;
}

function jdnToGreg(jdn) {
    var l = jdn + 68569;
    var n = Math.floor(4 * l / 146097);
    l = l - Math.floor((146097 * n + 3) / 4);
    var i = Math.floor(4000 * (l + 1) / 1461001);
    l = l - Math.floor(1461 * i / 4) + 31;
    var j = Math.floor(80 * l / 2447);
    var dd = l - Math.floor(2447 * j / 80);
    l = Math.floor(j / 11);
    var mm = j + 2 - 12 * l;
    var yy = 100 * (n - 49) + i + l;
    return { year: yy, month: mm, day: dd };
}

var GREG_MONTHS = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

function ethConvert(prefix) {
    var d = parseInt(document.getElementById('eth_day_'   + prefix).value);
    var m = parseInt(document.getElementById('eth_month_' + prefix).value);
    var y = parseInt(document.getElementById('eth_year_'  + prefix).value);

    var previewEl = document.getElementById('greg_' + prefix + '_preview');
    var hiddenEl  = document.getElementById(prefix + '_at_hidden');

    if (!d || !m || !y) {
        previewEl.textContent = '';
        hiddenEl.value = '';
        return;
    }

    var jdn  = ethToJDN(y, m, d);
    var greg = jdnToGreg(jdn);

    var gregStr = greg.year + '-' +
        String(greg.month).padStart(2, '0') + '-' +
        String(greg.day).padStart(2, '0');

    hiddenEl.value = gregStr;
    previewEl.textContent = '= ' + GREG_MONTHS[greg.month - 1] + ' ' + greg.day + ', ' + greg.year + ' G.C.';
}

/* ── Photo preview ── */
document.getElementById('photoInput').addEventListener('change', function () {
    this.nextElementSibling.textContent = this.files[0] ? this.files[0].name : 'Choose image…';
});

function previewPhoto(input) {
    var wrap = document.getElementById('photoPreviewWrap');
    var img  = document.getElementById('photoPreview');
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) { img.src = e.target.result; wrap.style.display = 'block'; };
        reader.readAsDataURL(input.files[0]);
    } else {
        wrap.style.display = 'none';
    }
}
</script>
@endpush
