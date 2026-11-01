{{-- Renders a Chart.js radar chart; resources/js/app.js hydrates any canvas[data-radar-chart]. --}}
@props(['payload', 'height' => 'h-80'])

<div {{ $attributes->merge(['class' => 'relative w-full ' . $height]) }}>
    <canvas data-radar-chart
            data-payload="{{ json_encode($payload) }}"
            role="img"
            aria-label="Grafik radar skor likuiditas, profitabilitas, efisiensi kas, dan operasional"></canvas>
</div>
