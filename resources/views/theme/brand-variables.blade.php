{{-- متغيّرات لون العلامة — بتتحقن في <head> وبتتكاش بمفتاح فيه اللون نفسه --}}
<style>
    :root {
        @foreach ($scale as $shade => $hex)
        --fc-brand-{{ $shade }}: {{ $hex }};
        @endforeach
    }
</style>
