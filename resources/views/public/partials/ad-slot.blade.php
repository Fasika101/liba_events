@php
    $items = \App\Models\MarketplaceAd::activeGrouped()->get($placement, collect());
@endphp

@if($items->isNotEmpty())
    <div class="mp-ads mp-ads-{{ $placement }}" aria-label="{{ __('public.sponsored') }}">
        @foreach($items as $ad)
            @php
                $tag = filled($ad->link_url) ? 'a' : 'div';
                $href = filled($ad->link_url) ? ' href="'.e($ad->link_url).'" target="_blank" rel="noopener sponsored"' : '';
                $image = $ad->imageUrl();
            @endphp
            <{{ $tag }} class="mp-ad"{!! $href !!}>
                @if($image)
                    <span class="mp-ad-media" style="background-image:url('{{ $image }}')"></span>
                @endif
                <span class="mp-ad-body">
                    <span class="mp-ad-label">{{ __('public.sponsored') }}</span>
                    <span class="mp-ad-title">{{ $ad->title }}</span>
                    @if($ad->body)
                        <span class="mp-ad-text">{{ $ad->body }}</span>
                    @endif
                </span>
            </{{ $tag }}>
        @endforeach
    </div>
@endif
