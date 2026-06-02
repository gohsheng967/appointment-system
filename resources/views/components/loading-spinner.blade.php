@props([
    'size' => '1rem',
    'trackColor' => 'rgba(156, 163, 175, 0.25)',
    'indicatorColor' => 'currentColor',
])

<span {{ $attributes->class('inline-flex shrink-0 items-center justify-center') }} aria-hidden="true">
    <span
        class="animate-spin rounded-full border-2 border-solid"
        style="
            width: {{ $size }};
            height: {{ $size }};
            border-color: {{ $trackColor }};
            border-top-color: {{ $indicatorColor }};
        "
    ></span>
</span>
