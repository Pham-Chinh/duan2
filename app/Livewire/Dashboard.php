<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Attributes\On;

class Dashboard extends Component
{
    public $chartType = 'day'; // day, month, year
    
    /**
     * Mount component - Reset chartType về 'day' mỗi lần vào trang
     */
    public function mount(): void
    {
        $this->chartType = 'day';
    }
    
    /**
     * Refresh dashboard khi có post mới hoặc cập nhật
     */
    #[On('post-created')]
    #[On('post-updated')]
    #[On('post-deleted')]
    public function refreshDashboard(): void
    {
        // Method này sẽ trigger re-render của component
        // Livewire sẽ tự động gọi lại render() method
    }
    
    public function render()
    {
        // Thống kê tổng số
        $totalCategories = Category::count();
        $totalPosts = Post::count();
        $publishedPosts = Post::where('status', 'published')->count();
        $draftPosts = Post::where('status', 'draft')->count();
        
        // Dữ liệu biểu đồ theo loại
        $chartData = $this->getChartData();
        
        return view('livewire.dashboard', [
            'totalCategories' => $totalCategories,
            'totalPosts' => $totalPosts,
            'publishedPosts' => $publishedPosts,
            'draftPosts' => $draftPosts,
            'chartLabels' => $chartData['labels'],
            'chartValues' => $chartData['values'],
        ]);
    }
    
    private function getChartData()
    {
        if ($this->chartType === 'day') {
            return $this->getDailyData();
        } elseif ($this->chartType === 'month') {
            return $this->getMonthlyData();
        } else {
            return $this->getYearlyData();
        }
    }
    
    private function getDailyData()
    {
        // Lấy dữ liệu các ngày trong tháng hiện tại
        $today = now();
        
        $postsPerDay = Post::select(
            DB::raw('DAY(created_at) as day'),
            DB::raw('COUNT(*) as count')
        )
        ->whereYear('created_at', $today->year)
        ->whereMonth('created_at', $today->month)
        ->groupBy('day')
        ->orderBy('day')
        ->get()
        ->pluck('count', 'day');
        
        $chartData = collect();
        $daysInMonth = $today->daysInMonth;
        
        // Tạo dữ liệu cho tất cả các ngày trong tháng
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $chartData[$day] = $postsPerDay->get($day, 0);
        }
        
        return [
            'labels' => $chartData->keys()->toArray(), // Chỉ hiển thị số ngày: 1, 2, 3...
            'values' => $chartData->values()->toArray(),
        ];
    }
    
    private function getMonthlyData()
    {
        // Lấy dữ liệu các tháng từ đầu năm đến hiện tại
        $currentYear = now()->year;
        $currentMonth = now()->month;
        
        $postsPerMonth = Post::select(
            DB::raw('MONTH(created_at) as month'),
            DB::raw('COUNT(*) as count')
        )
        ->whereYear('created_at', $currentYear)
        ->groupBy('month')
        ->orderBy('month')
        ->get()
        ->pluck('count', 'month');
        
        $chartData = collect();
        $monthNames = [
            1 => 'T1', 2 => 'T2', 3 => 'T3', 4 => 'T4',
            5 => 'T5', 6 => 'T6', 7 => 'T7', 8 => 'T8',
            9 => 'T9', 10 => 'T10', 11 => 'T11', 12 => 'T12'
        ];
        
        // Tạo dữ liệu từ tháng 1 đến tháng hiện tại
        for ($month = 1; $month <= $currentMonth; $month++) {
            $chartData[$monthNames[$month]] = $postsPerMonth->get($month, 0);
        }
        
        return [
            'labels' => $chartData->keys()->toArray(),
            'values' => $chartData->values()->toArray(),
        ];
    }
    
    private function getYearlyData()
    {
        // Lấy năm đầu tiên có bài viết
        $firstPostYear = Post::selectRaw('MIN(YEAR(created_at)) as min_year')->value('min_year');
        $currentYear = now()->year;
        
        // Nếu chưa có bài viết nào, trả về năm hiện tại với giá trị 0
        if (!$firstPostYear) {
            return [
                'labels' => [(string)$currentYear],
                'values' => [0],
            ];
        }
        
        // Lấy số lượng bài viết theo từng năm
        $postsPerYear = Post::select(
            DB::raw('YEAR(created_at) as year'),
            DB::raw('COUNT(*) as count')
        )
        ->groupBy('year')
        ->orderBy('year')
        ->get()
        ->pluck('count', 'year');
        
        $chartData = collect();
        
        // Tạo dữ liệu từ năm đầu tiên đến năm hiện tại
        for ($year = $firstPostYear; $year <= $currentYear; $year++) {
            $chartData[$year] = $postsPerYear->get($year, 0);
        }
        
        return [
            'labels' => $chartData->keys()->map(fn($y) => (string)$y)->toArray(), // "2020", "2021"...
            'values' => $chartData->values()->toArray(),
        ];
    }
}
