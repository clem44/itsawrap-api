@extends('admin.layouts.app')

@section('title', 'Hero Slider Builder')
@section('header', 'Hero Slider Builder')

@section('content')
    <div id="hero-slider-builder"></div>
@endsection

@push('scripts')
    <script>
        window.addEventListener('load', () => {
            const root = document.getElementById('hero-slider-builder');
            const slider = {{ Illuminate\Support\Js::from($slider) }};
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
