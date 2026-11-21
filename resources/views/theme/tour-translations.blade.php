@php
    // نصوص الجولة بتتحقن هنا عشان الـ JS المحزوم يفضل محايد للغة.
    $fcTourTranslations = [
        'next' => __('fc-onboarding::onboarding.tours.next'),
        'prev' => __('fc-onboarding::onboarding.tours.prev'),
        'done' => __('fc-onboarding::onboarding.tours.done'),
        'progress' => __('fc-onboarding::onboarding.tours.progress'),
    ];
@endphp

{{-- @json بترميز hex — آمن جوه script من غير عرض خام --}}
<script>
    window.fcTourTranslations = @json($fcTourTranslations, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
</script>
