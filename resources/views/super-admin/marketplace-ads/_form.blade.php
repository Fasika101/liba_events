<div class="card-body">
    <div class="form-group">
        <label for="title">Title</label>
        <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror"
               required maxlength="120" value="{{ old('title', $ad->title) }}">
        @error('title')<span class="invalid-feedback">{{ $message }}</span>@enderror
    </div>

    <div class="form-group">
        <label for="body">Short line (optional)</label>
        <textarea name="body" id="body" rows="2" maxlength="500"
                  class="form-control @error('body') is-invalid @enderror"
                  placeholder="One quiet supporting sentence">{{ old('body', $ad->body) }}</textarea>
        @error('body')<span class="invalid-feedback">{{ $message }}</span>@enderror
    </div>

    <div class="form-group">
        <label for="link_url">Link URL (optional)</label>
        <input type="url" name="link_url" id="link_url" class="form-control @error('link_url') is-invalid @enderror"
               maxlength="500" placeholder="https://…" value="{{ old('link_url', $ad->link_url) }}">
        @error('link_url')<span class="invalid-feedback">{{ $message }}</span>@enderror
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="placement">Placement</label>
                <select name="placement" id="placement" class="form-control @error('placement') is-invalid @enderror" required>
                    @foreach($placements as $value => $label)
                        <option value="{{ $value }}" @selected(old('placement', $ad->placement) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('placement')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="sort_order">Sort order</label>
                <input type="number" name="sort_order" id="sort_order" min="0" class="form-control"
                       value="{{ old('sort_order', $ad->sort_order ?? 0) }}">
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="starts_at">Starts at (optional)</label>
                <input type="datetime-local" name="starts_at" id="starts_at" class="form-control"
                       value="{{ old('starts_at', optional($ad->starts_at)->format('Y-m-d\TH:i')) }}">
                <small class="form-text text-muted">Leave blank to show immediately. Ad stays live until Ends at.</small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="ends_at">Ends at (optional)</label>
                <input type="datetime-local" name="ends_at" id="ends_at" class="form-control"
                       value="{{ old('ends_at', optional($ad->ends_at)->format('Y-m-d\TH:i')) }}">
                <small class="form-text text-muted">Leave blank for no expiry. Until then it keeps rotating on Discover.</small>
            </div>
        </div>
    </div>

    <div class="form-group">
        <label for="image">Image (optional)</label>
        <input type="file" name="image" id="image" accept="image/*" class="form-control-file @error('image') is-invalid @enderror">
        <small class="form-text text-muted">Keep it light — wide banner works best. Max 2MB.</small>
        @error('image')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
        @if($ad->imageUrl())
            <div class="mt-2">
                <img src="{{ $ad->imageUrl() }}" alt="" class="rounded border" style="max-width:220px;max-height:100px;object-fit:cover;">
                <div class="custom-control custom-checkbox mt-2">
                    <input type="checkbox" class="custom-control-input" id="remove_image" name="remove_image" value="1">
                    <label class="custom-control-label" for="remove_image">Remove current image</label>
                </div>
            </div>
        @endif
    </div>

    <div class="custom-control custom-checkbox">
        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1"
               @checked(old('is_active', $ad->is_active ?? true))>
        <label class="custom-control-label" for="is_active">Active on site</label>
    </div>
</div>
