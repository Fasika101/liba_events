<div class="card-body">
    <div class="form-group">
        <label for="name">Package name</label>
        <input type="text" name="name" id="name" class="form-control" required maxlength="120"
               value="{{ old('name', $package->name ?? '') }}">
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="sms_count">Number of SMS</label>
                <input type="number" name="sms_count" id="sms_count" class="form-control" min="1" required
                       value="{{ old('sms_count', $package->sms_count ?? 1000) }}">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="price_etb">Total price (ETB)</label>
                <input type="number" name="price_etb" id="price_etb" class="form-control" min="0" step="0.01" required
                       value="{{ old('price_etb', $package->price_etb ?? 1000) }}">
                <small class="text-muted">e.g. 1000 SMS × 1 ETB = 1000 ETB</small>
            </div>
        </div>
    </div>
    <div class="form-group">
        <label for="description">Description</label>
        <textarea name="description" id="description" rows="2" class="form-control">{{ old('description', $package->description ?? '') }}</textarea>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="sort_order">Sort order</label>
                <input type="number" name="sort_order" id="sort_order" class="form-control" min="0"
                       value="{{ old('sort_order', $package->sort_order ?? 0) }}">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <div class="custom-control custom-checkbox mt-4">
                    <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1"
                           @checked(old('is_active', $package->is_active ?? true))>
                    <label class="custom-control-label" for="is_active">Active (visible to organizations)</label>
                </div>
            </div>
        </div>
    </div>
</div>
