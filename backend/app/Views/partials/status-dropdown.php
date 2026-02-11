<div class="relative">
    <!-- Status Button -->
    <button id="status-dropdown-button" class="flex items-center space-x-2 focus:outline-none">
        <div class="status-indicator w-3 h-3 rounded-full bg-green-500"></div>
        <span class="status-text text-sm">Online</span>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>
    
    <!-- Status Dropdown -->
    <div id="status-dropdown" class="absolute left-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10 hidden">
        <a href="#" class="status-option flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" data-status="online">
            <div class="w-3 h-3 rounded-full bg-green-500 mr-3"></div>
            <span class="status-label">Online</span>
        </a>
        <a href="#" class="status-option flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" data-status="away">
            <div class="w-3 h-3 rounded-full bg-yellow-500 mr-3"></div>
            <span class="status-label">Away</span>
        </a>
        <a href="#" class="status-option flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" data-status="busy">
            <div class="w-3 h-3 rounded-full bg-red-500 mr-3"></div>
            <span class="status-label">Busy</span>
        </a>
        <a href="#" class="status-option flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" data-status="offline">
            <div class="w-3 h-3 rounded-full bg-gray-500 mr-3"></div>
            <span class="status-label">Offline</span>
        </a>
    </div>
</div>
