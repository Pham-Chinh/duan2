{{-- Toast Notification Component --}}
<div 
    x-data="{ 
        show: false, 
        message: '', 
        type: 'success',
        init() {
            window.addEventListener('toast', (event) => {
                this.message = event.detail.message;
                this.type = event.detail.type || 'success';
                this.show = true;
                setTimeout(() => { this.show = false }, 3000);
            });
        }
    }"
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 transform translate-y-2"
    x-transition:enter-end="opacity-100 transform translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 transform translate-y-0"
    x-transition:leave-end="opacity-0 transform translate-y-2"
    class="fixed bottom-4 right-4 z-50 max-w-sm"
    style="display: none;"
>
    <div 
        :class="{
            'bg-green-50 border-green-500 dark:bg-green-900/30 dark:border-green-500': type === 'success',
            'bg-red-50 border-red-500 dark:bg-red-900/30 dark:border-red-500': type === 'error',
            'bg-yellow-50 border-yellow-500 dark:bg-yellow-900/30 dark:border-yellow-500': type === 'warning',
            'bg-blue-50 border-blue-500 dark:bg-blue-900/30 dark:border-blue-500': type === 'info'
        }"
        class="border-l-4 p-4 rounded-lg shadow-lg"
    >
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <svg 
                    x-show="type === 'success'" 
                    class="h-5 w-5 text-green-600 dark:text-green-400" 
                    fill="none" 
                    stroke="currentColor" 
                    viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <svg 
                    x-show="type === 'error'" 
                    class="h-5 w-5 text-red-600 dark:text-red-400" 
                    fill="none" 
                    stroke="currentColor" 
                    viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <svg 
                    x-show="type === 'warning'" 
                    class="h-5 w-5 text-yellow-600 dark:text-yellow-400" 
                    fill="none" 
                    stroke="currentColor" 
                    viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <svg 
                    x-show="type === 'info'" 
                    class="h-5 w-5 text-blue-600 dark:text-blue-400" 
                    fill="none" 
                    stroke="currentColor" 
                    viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="ml-3">
                <p 
                    :class="{
                        'text-green-800 dark:text-green-200': type === 'success',
                        'text-red-800 dark:text-red-200': type === 'error',
                        'text-yellow-800 dark:text-yellow-200': type === 'warning',
                        'text-blue-800 dark:text-blue-200': type === 'info'
                    }"
                    class="text-sm font-medium"
                    x-text="message"
                ></p>
            </div>
            <div class="ml-auto pl-3">
                <button 
                    @click="show = false" 
                    :class="{
                        'text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-200': type === 'success',
                        'text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-200': type === 'error',
                        'text-yellow-600 hover:text-yellow-800 dark:text-yellow-400 dark:hover:text-yellow-200': type === 'warning',
                        'text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200': type === 'info'
                    }"
                    class="inline-flex rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2"
                >
                    <span class="sr-only">Dismiss</span>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>

