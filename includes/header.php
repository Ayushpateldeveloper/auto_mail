<?php

?>
<nav class="bg-indigo-600 text-white px-6 py-4 shadow-lg">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-6">
            <a href="profile.php" class="text-2xl font-bold hover:text-indigo-200 transition-colors">Auto Mail System</a>
            <div class="flex items-center space-x-4">
                <a href="./department.php" class="flex items-center hover:bg-indigo-700 transition-colors rounded-lg px-4 py-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5z" />
                    </svg>
                    Departments
                </a>
                <a href="./group.php" class="flex items-center hover:bg-indigo-700 transition-colors rounded-lg px-4 py-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5z" />
                    </svg>
                    Groups
                </a>
                <a href="./mail_forwarding.php" class="flex items-center hover:bg-indigo-700 transition-colors rounded-lg px-4 py-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                    </svg>
                    Mail Forwarding
                </a>
                <a href="./manage_user_keywords.php" class="flex items-center hover:bg-indigo-700 transition-colors rounded-lg px-4 py-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                    </svg>
                    User Management
                </a>
                <a href="./manage_users.php" class="flex items-center hover:bg-indigo-700 transition-colors rounded-lg px-4 py-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10 2a1 1 0 011 1v1h3a1 1 0 011 1v1h-1V4h-3V2h-1z" />
                        <path d="M3 4a1 1 0 011-1h1v1H4a1 1 0 00-1 1v1H2V4z" />
                        <path d="M10 12a1 1 0 011 1v1h3a1 1 0 011 1v1h-1v-1h-3v-1h-1z" />
                    </svg>
                    Manage Users
                </a>
            </div>
        </div>
        <div class="flex items-center space-x-4">
            <span class="text-sm">Welcome, <?php echo htmlspecialchars($_SESSION['user']['name'] ?? 'Admin'); ?></span>
            <div class="flex items-center space-x-2">
                <button class="bg-indigo-500 px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors" onclick="window.location.href='settings.php'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
                    </svg>
                </button>
                <button class="bg-red-500 px-4 py-2 rounded-lg hover:bg-red-700 transition-colors" onclick="logout()">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 001 1h12a1 1 0 001-1V7.414l-5-5H3zM2 4a2 2 0 012-2h6.586A2 2 0 0112 2.586L17.414 8A2 2 0 0118 9.586V16a2 2 0 01-2 2H4a2 2 0 01-2-2V4z" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</nav>
<script>
function logout() {
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = 'logout.php';
    }
}
</script>