<div class="space-y-8" x-data="{ needsRefresh: false }" x-init="
    // Check if we need to refresh after post operations
    if (localStorage.getItem('dashboardNeedsRefresh') === 'true') {
        $wire.$refresh();
        localStorage.removeItem('dashboardNeedsRefresh');
    }
    
    // Listen for post events
    window.addEventListener('post-created', () => { $wire.$refresh(); });
    window.addEventListener('post-updated', () => { $wire.$refresh(); });
    window.addEventListener('post-deleted', () => { $wire.$refresh(); });
">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Tổng quan hoạt động và thống kê hệ thống</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-sm text-gray-500 dark:text-gray-400">{{ now()->format('d/m/Y') }}</span>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
        <!-- Card 1: Danh mục -->
        <div class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 p-6 shadow-lg transition-all duration-300 hover:shadow-2xl hover:scale-105">
            <div class="absolute right-0 top-0 h-32 w-32 translate-x-8 -translate-y-8 transform rounded-full bg-white/10"></div>
            <div class="relative">
                <div class="flex items-center justify-between">
                    <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-white/20 backdrop-blur-sm">
                        <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <p class="text-sm font-medium text-blue-100">Tổng danh mục</p>
                    <p class="mt-2 text-4xl font-bold text-white">{{ $totalCategories }}</p>
                </div>
            </div>
        </div>

        <!-- Card 2: Tổng bài viết -->
        <div class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-600 p-6 shadow-lg transition-all duration-300 hover:shadow-2xl hover:scale-105">
            <div class="absolute right-0 top-0 h-32 w-32 translate-x-8 -translate-y-8 transform rounded-full bg-white/10"></div>
            <div class="relative">
                <div class="flex items-center justify-between">
                    <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-white/20 backdrop-blur-sm">
                        <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <p class="text-sm font-medium text-emerald-100">Tổng bài viết</p>
                    <p class="mt-2 text-4xl font-bold text-white">{{ $totalPosts }}</p>
                </div>
            </div>
        </div>

        <!-- Card 3: Đã đăng -->
        <div class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-purple-500 to-purple-600 p-6 shadow-lg transition-all duration-300 hover:shadow-2xl hover:scale-105">
            <div class="absolute right-0 top-0 h-32 w-32 translate-x-8 -translate-y-8 transform rounded-full bg-white/10"></div>
            <div class="relative">
                <div class="flex items-center justify-between">
                    <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-white/20 backdrop-blur-sm">
                        <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <p class="text-sm font-medium text-purple-100">Đã đăng</p>
                    <p class="mt-2 text-4xl font-bold text-white">{{ $publishedPosts }}</p>
                </div>
            </div>
        </div>

        <!-- Card 4: Bản nháp -->
        <div class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-amber-500 to-amber-600 p-6 shadow-lg transition-all duration-300 hover:shadow-2xl hover:scale-105">
            <div class="absolute right-0 top-0 h-32 w-32 translate-x-8 -translate-y-8 transform rounded-full bg-white/10"></div>
            <div class="relative">
                <div class="flex items-center justify-between">
                    <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-white/20 backdrop-blur-sm">
                        <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <p class="text-sm font-medium text-amber-100">Bản nháp</p>
                    <p class="mt-2 text-4xl font-bold text-white">{{ $draftPosts }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart Section -->
    <div class="rounded-2xl bg-white p-6 shadow-lg dark:bg-neutral-800">
        <!-- Chart Header -->
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Biểu đồ thống kê</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    @if($chartType === 'day')
                        Số lượng bài viết theo ngày (Tháng {{ now()->format('m/Y') }})
                    @elseif($chartType === 'month')
                        Số lượng bài viết theo tháng (Năm {{ now()->year }})
                    @else
                        Tổng số bài viết năm {{ now()->year }}
                    @endif
                </p>
            </div>
            
            <!-- Filter Buttons -->
            <div class="flex gap-2 rounded-lg bg-gray-100 p-1 dark:bg-neutral-700">
                <button 
                    wire:click="$set('chartType', 'day')"
                    class="rounded-md px-4 py-2 text-sm font-medium transition-all {{ $chartType === 'day' ? 'bg-white text-blue-600 shadow-sm dark:bg-neutral-600 dark:text-blue-400' : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white' }}"
                >
                    Ngày
                </button>
                <button 
                    wire:click="$set('chartType', 'month')"
                    class="rounded-md px-4 py-2 text-sm font-medium transition-all {{ $chartType === 'month' ? 'bg-white text-blue-600 shadow-sm dark:bg-neutral-600 dark:text-blue-400' : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white' }}"
                >
                    Tháng
                </button>
                <button 
                    wire:click="$set('chartType', 'year')"
                    class="rounded-md px-4 py-2 text-sm font-medium transition-all {{ $chartType === 'year' ? 'bg-white text-blue-600 shadow-sm dark:bg-neutral-600 dark:text-blue-400' : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white' }}"
                >
                    Năm
                </button>
            </div>
        </div>

        <!-- Chart Canvas -->
        <div class="relative h-96" wire:ignore>
            <canvas id="postsChart"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let postsChart = null;

function initChart() {
    const ctx = document.getElementById('postsChart');
    
    if (!ctx) return;
    
    // Destroy existing chart if it exists
    if (postsChart) {
        postsChart.destroy();
    }
    
    // Create gradient
    const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(99, 102, 241, 0.3)');
    gradient.addColorStop(1, 'rgba(99, 102, 241, 0.01)');
    
    postsChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: @json($chartLabels),
            datasets: [{
                label: 'Số bài viết',
                data: @json($chartValues),
                borderColor: 'rgb(99, 102, 241)',
                backgroundColor: gradient,
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 5,
                pointHoverRadius: 8,
                pointBackgroundColor: 'rgb(99, 102, 241)',
                pointBorderColor: '#fff',
                pointBorderWidth: 3,
                pointHoverBackgroundColor: 'rgb(99, 102, 241)',
                pointHoverBorderColor: '#fff',
                pointHoverBorderWidth: 3,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index',
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        color: document.documentElement.classList.contains('dark') ? '#e5e7eb' : '#374151',
                        font: {
                            family: 'system-ui, -apple-system, sans-serif',
                            size: 13,
                            weight: '500'
                        },
                        padding: 15,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.9)',
                    padding: 16,
                    cornerRadius: 12,
                    titleFont: {
                        size: 14,
                        family: 'system-ui, -apple-system, sans-serif',
                        weight: '600'
                    },
                    bodyFont: {
                        size: 15,
                        family: 'system-ui, -apple-system, sans-serif',
                        weight: '500'
                    },
                    displayColors: true,
                    boxPadding: 6
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        color: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#6b7280',
                        font: {
                            family: 'system-ui, -apple-system, sans-serif',
                            size: 12
                        },
                        padding: 10
                    },
                    grid: {
                        color: document.documentElement.classList.contains('dark') ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.06)',
                        drawBorder: false
                    }
                },
                x: {
                    ticks: {
                        color: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#6b7280',
                        font: {
                            family: 'system-ui, -apple-system, sans-serif',
                            size: 12
                        },
                        maxRotation: 0,
                        minRotation: 0,
                        padding: 10,
                        autoSkip: false,
                        callback: function(value, index, ticks) {
                            // value là giá trị label (ngày)
                            const day = parseInt(value);
                            // Hiển thị: ngày 1, các mốc chia hết cho 5, và ngày cuối
                            if (day === 1 || day % 5 === 0 || index === ticks.length - 1) {
                                return day;
                            }
                            return '';
                        }
                    },
                    grid: {
                        display: false,
                        drawBorder: false
                    }
                }
            },
            animation: {
                duration: 750,
                easing: 'easeInOutQuart'
            }
        }
    });
}

// Initialize chart on page load
document.addEventListener('livewire:navigated', initChart);

// Re-initialize chart after Livewire updates
document.addEventListener('livewire:init', () => {
    Livewire.hook('morph.updated', () => {
        setTimeout(initChart, 100);
    });
    
    // Listen for custom events to refresh chart
    Livewire.on('post-created', () => {
        setTimeout(initChart, 100);
    });
    
    Livewire.on('post-updated', () => {
        setTimeout(initChart, 100);
    });
    
    Livewire.on('post-deleted', () => {
        setTimeout(initChart, 100);
    });
});

// Initial load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initChart);
} else {
    initChart();
}
</script>
