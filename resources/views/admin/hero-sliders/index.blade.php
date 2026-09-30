@extends('admin.layouts.app')

@section('title', 'Hero Slider Builder')
@section('header', 'Hero Slider Builder')

@section('content')
    <div id="hero-slider-builder"></div>

    <script id="hero-slider-data" type="application/json">{!! json_encode($slider, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endsection

@push('scripts')
    <script>
        window.addEventListener('load', () => {
            const root = document.getElementById('hero-slider-builder');
            const slider = JSON.parse(document.getElementById('hero-slider-data').textContent);
            const updateUrl = @json(route('admin.hero-sliders.update', $slider['id']));
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            window.HeroSliderBuilder?.mount(root, {
                slider,
                openMediaPicker({ selectedMediaId, onSelect }) {
                    window.MediaLibraryPicker.open({
                        selectedMediaId,
                        accept: ['image'],
                        multiple: false,
                        onSelect,
                    });
                },
                async save(payload) {
                    const response = await fetch(updateUrl, {
                        method: 'PUT',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify(payload),
                    });
                    const body = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        const error = new Error(body.message || 'The slider could not be saved.');
                        error.errors = body.errors;
                        throw error;
                    }

                    return body;
                },
            });
        });
    </script>
@endpush
