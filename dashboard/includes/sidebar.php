<!-- start sidebar section -->
<div :class="{'dark text-white-dark' : $store.app.semidark}">
    <nav x-data="sidebar" class="sidebar fixed bottom-0 top-0 z-50 h-full min-h-screen w-[260px] shadow-[5px_0_25px_0_rgba(94,92,154,0.1)] transition-all duration-300">
        <div class="h-full bg-white dark:bg-[#0e1726]">
            <div class="flex items-center justify-between px-4 py-3">
                <a href="index.php" class="main-logo flex shrink-0 items-center">
                    <!---<img class="ml-[5px] w-8 flex-none" src="assets/images/logo.svg" alt="image" />-->
                    <span class="align-middle text-xl font-semibold ltr:ml-1.5 rtl:mr-1.5 dark:text-white-light lg:inline">Specimen Conservation</span>
                </a>
                <a href="javascript:;" class="collapse-icon flex h-8 w-8 items-center rounded-full transition duration-300 hover:bg-gray-500/10 rtl:rotate-180 dark:text-white-light dark:hover:bg-dark-light/10" @click="$store.app.toggleSidebar()">
                    <svg class="m-auto h-5 w-5" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M13 19L7 12L13 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        <path opacity="0.5" d="M16.9998 19L10.9998 12L16.9998 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </a>
            </div>
            <ul class="perfect-scrollbar relative h-[calc(100vh-80px)] space-y-0.5 overflow-y-auto overflow-x-hidden p-4 py-0 font-semibold" x-data="{ activeDropdown: 'dashboard' }">
                <h2 class="-mx-4 mb-1 flex items-center bg-white-light/30 px-7 py-3 font-extrabold uppercase dark:bg-dark dark:bg-opacity-[0.08]">
                    <svg class="hidden h-5 w-4 flex-none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>Dashboard</span>
                </h2>
                <li class="nav-item">
                    <ul>
                        <?php if (!isset($_SESSION['loggedin'])): ?>
                            <li class="nav-item">
                                <a href="https://localhost/Specimen-Conservation/dashboard/submit-report.php" class="group">
                                    <div class="flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7l4-4m0 0l4 4m-4-4v18" />
                                        </svg>
                                        <span class="text-black ltr:pl-3 rtl:pr-3 dark:text-[#506690] dark:group-hover:text-white-dark">Submit Report</span>
                                    </div>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
                            <li class="nav-item">
                                <a href="https://localhost/Specimen-Conservation/dashboard/admin.php" class="group">
                                    <div class="flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" class="shrink-0 group-hover:!text-primary">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M20.5 2C21.3284 2 22 2.67157 22 3.5V20.5C22 21.3284 21.3284 22 20.5 22H3.5C2.67157 22 2 21.3284 2 20.5V3.5C2 2.67157 2.67157 2 3.5 2H20.5ZM14 4H16V6H14V4ZM6 4H8V6H6V4ZM6 8H8V10H6V8ZM10 8H12V10H10V8ZM6 12H8V14H6V12ZM10 12H12V14H10V12ZM6 16H8V18H6V16ZM10 16H12V18H10V16ZM14 8H16V10H14V8ZM14 12H16V14H14V12ZM14 16H16V18H14V16ZM18 4H20V6H18V4ZM18 8H20V10H18V8ZM18 12H20V14H18V12ZM18 16H20V18H18V16Z" fill="currentColor" />
                                        </svg>
                                        <span class="text-black ltr:pl-3 rtl:pr-3 dark:text-[#506690] dark:group-hover:text-white-dark">Admin Dashboard</span>
                                    </div>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if (!isset($_SESSION['loggedin'])): ?>
                            <li class="nav-item">
                                <a href="https://localhost/Specimen-Conservation/dashboard/dashboard.php" class="group">
                                    <div class="flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" class="shrink-0 group-hover:!text-primary">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="m13.498.795.149-.149a1.207 1.207 0 1 1 1.707 1.708l-.149.148a1.5 1.5 0 0 1-.059 2.059L4.854 14.854a.5.5 0 0 1-.233.131l-4 1a.5.5 0 0 1-.606-.606l1-4a.5.5 0 0 1 .131-.232l9.642-9.642a.5.5 0 0 0-.642.056L6.854 4.854a.5.5 0 1 1-.708-.708L9.44.854A1.5 1.5 0 0 1 11.5.796a1.5 1.5 0 0 1 1.998-.001m-.644.766a.5.5 0 0 0-.707 0L1.95 11.756l-.764 3.057 3.057-.764L14.44 3.854a.5.5 0 0 0 0-.708z" fill="currentColor" />
                                        </svg>
                                        <span class="text-black ltr:pl-3 rtl:pr-3 dark:text-[#506690] dark:group-hover:text-white-dark">Dashboard</span>
                                    </div>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
                            <!-- Show Sign Out if logged in -->
                            <li class="nav-item">
                                <a href="https://localhost/Specimen-Conservation/dashboard/logout.php" class="group">
                                    <div class="flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" class="shrink-0 group-hover:!text-primary">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M11 2C10.4477 2 10 2.44772 10 3V21C10 21.5523 10.4477 22 11 22C11.5523 22 12 21.5523 12 21V3C12 2.44772 11.5523 2 11 2ZM5 7.29289L6.70711 6.29289L10 9.58579V7H14V9.58579L17.2929 6.29289L19 7.29289L14.5 11.7929V12.7071L19 17.2071L17.2929 18.2071L14 14.9142V17H10V14.9142L6.70711 18.2071L5 17.2071L9.5 12.7071V11.7929L5 7.29289Z" fill="currentColor" />
                                        </svg>
                                        <span class="text-black ltr:pl-3 rtl:pr-3 dark:text-[#506690] dark:group-hover:text-white-dark">Sign Out</span>
                                    </div>
                                </a>
                            </li>
                        <?php else: ?>
                            <!-- Show Sign In if not logged in -->
                            <li class="nav-item">
                                <a href="https://localhost/Specimen-Conservation/dashboard/admin-login.php" class="group">
                                    <div class="flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" class="shrink-0 group-hover:!text-primary">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M11 2C10.4477 2 10 2.44772 10 3V21C10 21.5523 10.4477 22 11 22C11.5523 22 12 21.5523 12 21V3C12 2.44772 11.5523 2 11 2ZM5 7.29289L6.70711 6.29289L10 9.58579V7H14V9.58579L17.2929 6.29289L19 7.29289L14.5 11.7929V12.7071L19 17.2071L17.2929 18.2071L14 14.9142V17H10V14.9142L6.70711 18.2071L5 17.2071L9.5 12.7071V11.7929L5 7.29289Z" fill="currentColor" />
                                        </svg>
                                        <span class="text-black ltr:pl-3 rtl:pr-3 dark:text-[#506690] dark:group-hover:text-white-dark">Sign In</span>
                                    </div>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>
</div>
<!-- end sidebar section -->