<x-simple-card>
    <div class="flex justify-between">
        <div>Resource Usage</div>
    </div>
    <div id="load-chart"></div>
    <script>
        window.addEventListener('load', function () {
            let options = {
                series: [
                    {
                        name: 'CPU Load',
                        data: @json($data["metrics"]->pluck("load")->toArray()),
                        color: '#ff9900'
                    },
                    {
                        name: 'Memory Usage',
                        data: @json($data["metrics"]->pluck("memory_used")->toArray()),
                        color: '#3366cc'
                    },
                    {
                        name: 'Disk Usage',
                        data: @json($data["metrics"]->pluck("disk_used")->toArray()),
                        color: '#109618'
                    }
                ],
                chart: {
                    height: '400px',
                    maxWidth: '100%',
                    type: 'line',
                    fontFamily: 'Inter, sans-serif',
                    dropShadow: {
                        enabled: false
                    },
                    toolbar: {
                        show: false
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
                // fill: {
                //     type: 'gradient',
                //     gradient: {
                //         opacityFrom: 0.55,
                //         opacityTo: 0,
                //         shade: '#1C64F2',
                //         gradientToColors: ['#1C64F2']
                //     }
                // },
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
                    categories: @json($data["metrics"]->pluck("date")->toArray()),
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
                        formatter: function (value) {
                            return parseInt(value);
                        }
                    }
                }
            };

            if (document.getElementById('load-chart') && typeof ApexCharts !== 'undefined') {
                const chart = new ApexCharts(document.getElementById('load-chart'), options);
                chart.render();
            }
        });
    </script>
</x-simple-card>
