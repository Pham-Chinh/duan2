{{-- Session Timeout Component - Chỉ cho role 'user' --}}
@auth
    @if(auth()->user()->isUser())
        <div x-data="sessionTimeout()" 
             x-init="init()"
             style="display: none;">
            {{-- Hidden component để track timeout --}}
        </div>
    @endif
@endauth

<script>
    function sessionTimeout() {
        return {
            timeout: 30000, // 30 giây
            timer: null,
            warningTime: 5000, // Cảnh báo 5 giây trước khi timeout
            warningShown: false,
            isActive: true,
            lastActivityTime: null, // Thời gian activity cuối cùng
            warningTimer: null, // Timer cho warning
            checkInterval: null, // Interval để check định kỳ

            init() {
                // Component chỉ được render nếu user role = 'user'
                // Nên không cần check lại ở đây
                this.lastActivityTime = Date.now();
                this.startTimer();
                this.setupActivityListeners();
            },

            startTimer() {
                // Clear timer cũ
                if (this.timer) {
                    clearTimeout(this.timer);
                }
                if (this.warningTimer) {
                    clearTimeout(this.warningTimer);
                }

                // Reset warning flag
                this.warningShown = false;

                // Update last activity time
                this.lastActivityTime = Date.now();

                // Set timer mới
                this.timer = setTimeout(() => {
                    this.handleTimeout();
                }, this.timeout);
            },

            handleTimeout() {
                // Hiển thị warning 5 giây trước khi timeout
                if (!this.warningShown) {
                    this.warningShown = true;
                    
                    // Show warning modal/notification
                    this.showWarning();
                    
                    // Đợi thêm 5 giây rồi redirect
                    this.warningTimer = setTimeout(() => {
                        this.redirectToLogin();
                    }, this.warningTime);
                    
                    return;
                }

                // Redirect về login
                this.redirectToLogin();
            },

            checkTimeSinceLastActivity() {
                // Check thời gian từ lần activity cuối cùng
                const timeSinceLastActivity = Date.now() - this.lastActivityTime;
                
                if (timeSinceLastActivity >= this.timeout) {
                    // Đã quá 30 giây → Redirect ngay
                    this.redirectToLogin();
                } else {
                    // Chưa quá 30 giây → Tính thời gian còn lại và set timer
                    const remainingTime = this.timeout - timeSinceLastActivity;
                    
                    // Nếu còn ít hơn 5 giây → Show warning luôn
                    if (remainingTime <= this.warningTime && !this.warningShown) {
                        this.warningShown = true;
                        this.showWarning();
                        this.warningTimer = setTimeout(() => {
                            this.redirectToLogin();
                        }, remainingTime);
                    } else if (remainingTime > this.warningTime) {
                        // Set timer bình thường
                        this.timer = setTimeout(() => {
                            this.handleTimeout();
                        }, remainingTime - this.warningTime);
                    }
                }
            },

            showWarning() {
                // Tạo warning notification
                const warning = document.createElement('div');
                warning.id = 'session-timeout-warning';
                warning.className = 'fixed top-4 right-4 z-50 bg-amber-500 text-white px-6 py-4 rounded-lg shadow-2xl max-w-sm animate-pulse';
                warning.innerHTML = `
                    <div class="flex items-center gap-3">
                        <svg class="h-6 w-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <div>
                            <p class="font-semibold">Phiên làm việc sắp hết hạn!</p>
                            <p class="text-sm mt-1">Bạn sẽ bị đăng xuất sau ${this.warningTime / 1000} giây nữa. Di chuyển chuột để tiếp tục.</p>
                        </div>
                    </div>
                `;
                
                document.body.appendChild(warning);

                // Auto remove sau khi redirect
                setTimeout(() => {
                    if (warning.parentNode) {
                        warning.remove();
                    }
                }, this.warningTime + 1000);
            },

            redirectToLogin() {
                // Xóa warning nếu còn
                const warning = document.getElementById('session-timeout-warning');
                if (warning) {
                    warning.remove();
                }

                // Clear session và redirect về login
                // Route session.timeout sẽ tự động logout, clear session và redirect
                window.location.href = '{{ route("session.timeout") }}';
            },

            setupActivityListeners() {
                // List các event để reset timer
                const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];

                events.forEach(event => {
                    document.addEventListener(event, () => {
                        if (this.isActive) {
                            // Reset timer khi có activity
                            this.startTimer();
                            
                            // Ẩn warning nếu đang hiển thị
                            const warning = document.getElementById('session-timeout-warning');
                            if (warning) {
                                warning.remove();
                                this.warningShown = false;
                            }
                        }
                    }, { passive: true });
                });

                // Khi tab không active: Timer vẫn chạy, không pause
                // Khi tab active lại: Check xem đã quá 30 giây chưa
                document.addEventListener('visibilitychange', () => {
                    if (!document.hidden) {
                        // Tab active lại - check thời gian đã trôi qua
                        if (this.isActive && this.lastActivityTime) {
                            this.checkTimeSinceLastActivity();
                        }
                    }
                });

                // Backup: Check định kỳ ngay cả khi tab không active
                // Dùng để đảm bảo redirect khi quay lại tab
                this.checkInterval = setInterval(() => {
                    if (!document.hidden && this.isActive && this.lastActivityTime) {
                        const timeSinceLastActivity = Date.now() - this.lastActivityTime;
                        if (timeSinceLastActivity >= this.timeout) {
                            this.redirectToLogin();
                        }
                    }
                }, 1000); // Check mỗi giây
            },

            destroy() {
                // Cleanup
                if (this.timer) {
                    clearTimeout(this.timer);
                }
                if (this.warningTimer) {
                    clearTimeout(this.warningTimer);
                }
                if (this.checkInterval) {
                    clearInterval(this.checkInterval);
                }
                this.isActive = false;
            }
        }
    }
</script>

