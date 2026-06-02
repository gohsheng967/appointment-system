@props([
    'columnSpan' => [],
    'columnStart' => [],
    'description' => null,
    'heading' => null,
    'height' => '18rem',
])

<div
    {{
        (new \Illuminate\View\ComponentAttributeBag)
            ->gridColumn($columnSpan, $columnStart)
            ->class(['fi-wi-widget fi-wi-chart'])
    }}
>
    <x-filament::section
        :description="$description"
        :heading="$heading"
    >
        <div
            class="flex flex-col items-center justify-center gap-5 rounded-xl border border-dashed border-gray-200 bg-gray-50/70 p-6 dark:border-white/10 dark:bg-white/5"
            style="height: {{ $height }}"
            aria-live="polite"
        >
            <div class="flex items-center gap-3 text-sm text-gray-600 dark:text-gray-300">
                <x-loading-spinner
                    size="1.25rem"
                    track-color="rgba(156, 163, 175, 0.25)"
                    indicator-color="rgb(59, 130, 246)"
                    class="text-primary-500"
                />
                <span>Loading chart...</span>
            </div>

            <div class="flex w-full max-w-xs items-end justify-center gap-3">
                <div class="h-16 w-8 animate-pulse rounded-t-lg bg-primary-200/70 dark:bg-primary-400/20"></div>
                <div class="h-24 w-8 animate-pulse rounded-t-lg bg-primary-300/70 dark:bg-primary-400/30"></div>
                <div class="h-12 w-8 animate-pulse rounded-t-lg bg-primary-200/70 dark:bg-primary-400/20"></div>
                <div class="h-28 w-8 animate-pulse rounded-t-lg bg-primary-300/70 dark:bg-primary-400/30"></div>
                <div class="h-20 w-8 animate-pulse rounded-t-lg bg-primary-200/70 dark:bg-primary-400/20"></div>
            </div>
        </div>
    </x-filament::section>
</div>
