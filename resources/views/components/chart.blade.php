@props([
    "id",
    "type",
    "title",
    "color",
    "sets",
    "categories",
    "toolbar" => false,
    "formatter" => null,
])
<x-simple-card {{ $attributes }}>
    <div class="relative">
        <div class="absolute left-4 top-4">{{ $title }}</div>
    </div>
    <div id="{{ $id }}" class="pt-4"></div>
    <script>
        window.addEventListener('load', function () {
            let options = {
                series: [
                    @foreach ($sets as $set)
                        {
                            name: '{{ $set["name"] }}',
                            data: @json($set["data"]),
                            color: '{{ $set["color"] }}'
                        },
                    @endforeach
                ],
                chart: {
                    height: '100%',
                    maxWidth: '100%',
                    type: '{{ $type }}',
                    fontFamily: 'Inter, sans-serif',
                    dropShadow: {
                        enabled: false
                    },
                    toolbar: {
                        show: @js($toolbar)
                    }
                },
                tooltip: {
                    enabled: true,
                    x: {
                        show: true
                    }
                },
                legend: {
                    show: true
                },
                @if ($type == 'area')
                fill: {
                    type: 'gradient',
                    gradient: {
                        opacityFrom: 0.55,
                        opacityTo: 0,
                        shade: '{{ $color }}',
                        gradientToColors: ['{{ $color }}']
                    }
                },
                @endif
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    width: 2,
                    curve: 'smooth'
                },
                grid: {
                    show: false,
                    strokeDashArray: 4,
                    padding: {
                        left: 2,
                        right: 2,
                        top: 0
                    }
                },
                xaxis: {
                    categories: @json($categories),
                    labels: {
                        show: false
                    },
                    axisBorder: {
                        show: false
                    },
                    axisTicks: {
                        show: false
                    }
                },
                yaxis: {
                    show: false,
                    labels: {
                        @if ($formatter)
                        formatter: {{ $formatter }},
                        @endif
                    }
                }
            };

            if (document.getElementById('{{ $id }}') && typeof ApexCharts !== 'undefined') {
                const chart = new ApexCharts(document.getElementById('{{ $id }}'), options);
                chart.render();
            }
        });
    </script>
</x-simple-card>
