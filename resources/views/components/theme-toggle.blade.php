<div
    x-data
    @click="
        if (document.documentElement.classList.contains('dark')) {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('theme', 'light');
        } else {
            document.documentElement.classList.add('dark');
            localStorage.setItem('theme', 'dark');
        }
    "
    class="fixed bottom-4 right-4 z-50 rounded-full shadow-lg backdrop-blur-sm bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-300 cursor-pointer"
    style="width: 48px; height: 48px; display: flex; align-items: center; justify-content: center;"
    title="切换主题"
>
    <i class="fas fa-moon text-gray-700 dark:text-yellow-400 text-xl dark:hidden"></i>
    <i class="fas fa-sun text-yellow-400 dark:text-yellow-400 text-xl hidden dark:inline"></i>
</div>