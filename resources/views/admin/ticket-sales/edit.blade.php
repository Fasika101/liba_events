<x-app-layout>
    <x-slot name="header">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Edit Sold Ticket</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.ticket-sales.index') }}">Ticket Sales</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.ticket-sales.show', $event) }}">{{ $event->title }}</a></li>
                    <li class="breadcrumb-item active">Edit ticket</li>
                </ol>
            </div>
        </div>
    </x-slot>

    <div class="row">
        <div class="col-lg-8 col-12">
            <div class="card card-outline card-warning">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-edit mr-2"></i> Edit buyer &amp; amount
                    </h3>
                    <code class="small">{{ $ticket->ticket_code }}</code>
                </div>
                <div class="card-body">
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

                    <div class="alert alert-light border small mb-4">
                        <div class="row">
                            <div class="col-md-6 mb-2 mb-md-0">
                                <strong>Event</strong><br>
                                {{ $event->title }}
                            </div>
                            <div class="col-md-6">
                                <strong>Sold by</strong><br>
                                {{ $ticket->agent->name ?? '—' }}
                                <span class="text-muted">·</span>
                                <span class="text-muted">@ethdate($ticket->sold_at)</span>
                            </div>
                        </div>
                        <p class="mb-0 mt-2 text-muted small">
                            Ticket code and which agent sold this ticket cannot be changed here.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('admin.ticket-sales.tickets.update', [$event, $ticket]) }}">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label class="font-weight-bold">Buyer name <span class="text-danger">*</span></label>
                            <input type="text" name="buyer_name" value="{{ old('buyer_name', $ticket->buyer_name) }}"
                                   required class="form-control @error('buyer_name') is-invalid @enderror">
                            @error('buyer_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Phone <span class="text-danger">*</span></label>
                            <input type="text" name="buyer_phone" value="{{ old('buyer_phone', $ticket->buyer_phone) }}"
                                   required placeholder="+251912345678"
                                   class="form-control @error('buyer_phone') is-invalid @enderror">
                            <small class="form-text text-muted">Format: +251 followed by 9 digits.</small>
                            @error('buyer_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Email</label>
                            <input type="email" name="buyer_email" value="{{ old('buyer_email', $ticket->buyer_email) }}"
                                   class="form-control @error('buyer_email') is-invalid @enderror">
                            @error('buyer_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Address</label>
                            <textarea name="buyer_address" rows="2" class="form-control @error('buyer_address') is-invalid @enderror">{{ old('buyer_address', $ticket->buyer_address) }}</textarea>
                            @error('buyer_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Occupation <small class="text-muted font-weight-normal">(optional)</small></label>
                            <input type="text" name="buyer_occupation" value="{{ old('buyer_occupation', $ticket->buyer_occupation) }}"
                                   class="form-control @error('buyer_occupation') is-invalid @enderror" maxlength="150">
                            @error('buyer_occupation') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold d-block mb-2">Yeneshaa Abat <span class="text-danger">*</span></label>
                            @php
                                $ya = (string) old('yeneshaa_abat', $ticket->yeneshaa_abat ? '1' : '0');
                            @endphp
                            <div class="custom-control custom-radio">
                                <input type="radio" name="yeneshaa_abat" id="edit_yeneshaa_yes" value="1"
                                       class="custom-control-input @error('yeneshaa_abat') is-invalid @enderror"
                                       {{ $ya === '1' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="edit_yeneshaa_yes">Yes</label>
                            </div>
                            <div class="custom-control custom-radio">
                                <input type="radio" name="yeneshaa_abat" id="edit_yeneshaa_no" value="0"
                                       class="custom-control-input @error('yeneshaa_abat') is-invalid @enderror"
                                       {{ $ya === '0' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="edit_yeneshaa_no">No</label>
                            </div>
                            @error('yeneshaa_abat') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label class="font-weight-bold">Amount paid <span class="text-danger">*</span></label>
                                <input type="number" name="price_paid" step="0.01" min="0"
                                       value="{{ old('price_paid', $ticket->price_paid) }}"
                                       required class="form-control @error('price_paid') is-invalid @enderror">
                                @error('price_paid') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label class="font-weight-bold">Currency <span class="text-danger">*</span></label>
                                <input type="text" name="currency" maxlength="3"
                                       value="{{ old('currency', $ticket->currency) }}"
                                       required placeholder="USD"
                                       class="form-control text-uppercase @error('currency') is-invalid @enderror">
                                @error('currency') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="d-flex flex-wrap" style="gap:.5rem;">
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save mr-1"></i> Save changes
                            </button>
                            <a href="{{ route('admin.ticket-sales.show', $event) }}" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
