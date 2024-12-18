<?php
session_start();
if (!isset($_SESSION['stafflogin'])) {
    header('Location: https://localhost/vg/login/login.php');
    exit();
} elseif ($_SESSION['designation'] !== 'archive' && $_SESSION['designation'] !== 'vg' && $_SESSION['designation'] !== 'pg' && $_SESSION['designation'] !== 'boss') {
    header('Location: https://localhost/vg/dashboard/index.php');
    exit();
}
include('includes/config.php');
$pdo_login = pdo_connect_mysql();

// Define an array of months
$months = [
    "January", "February", "March", "April", "May", "June",
    "July", "August", "September", "October", "November", "December"
];
$alert = '';

// Function to get archive counts for each month of a given year
function getMonthlyArchives($pdo, $year)
{
    $monthlyArchives = [];
    for ($month = 1; $month <= 12; $month++) {
        $stmt = $pdo->prepare("SELECT COUNT(*) AS count FROM archive WHERE YEAR(date) = ? AND MONTH(date) = ? AND status = 1");
        $stmt->execute([$year, $month]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $monthlyArchives[] = $result['count'];
    }
    return $monthlyArchives;
}

// Get archives counts for 2024 and 2025
$archives2024 = getMonthlyArchives($pdo_login, 2024);
$archives2025 = getMonthlyArchives($pdo_login, 2025);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the selected month and status
    $month = $_POST['month'];

    // Prepare and execute the SQL query to fetch data for the selected month with status 1
    $stmt = $pdo_login->prepare("SELECT * FROM archive WHERE YEAR(date) = ? AND MONTH(date) = ?");
    $stmt->execute([2024, $month]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Check if data is available
    if ($data) {
        // Define the file name
        $filename = "archives_" . $months[$month - 1] . "_2024.txt";

        // Open a file handle for writing
        $file = fopen($filename, "w");

        // Loop through each row of data and write it to the file
        foreach ($data as $row) {
            fwrite($file, "\n");
            fwrite($file, "Request ID: " . $row['requestId'] . "\n");
            fwrite($file, "Cutways: " . $row['cutways'] . "\n");
            fwrite($file, "Function Name: " . $row['functionName'] . "\n");
            fwrite($file, "Tag/Topic: " . $row['tagTopic'] . "\n");
            fwrite($file, "VIP: " . $row['vip'] . "\n");
            fwrite($file, "VG/PG: " . $row['vgPg'] . "\n");
            fwrite($file, "Date: " . $row['date'] . "\n");
            fwrite($file, "Submitted By: " . $row['submitBy'] . "\n");

            if ($row['status'] == 1) {
                fwrite($file, "Status: Completed\n");
            } else if ($row['status'] == -1) {
                fwrite($file, "Status: Rejected\n");
            } else {
                fwrite($file, "Status: Pending\n");
            }

            fwrite($file, "\n"); // Add a newline between each entry
        }

        // Close the file handle
        fclose($file);

        // Set headers to force download
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
        header('Content-Length: ' . filesize($filename));
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Content-Transfer-Encoding: binary');
        readfile($filename);

        // Delete the file after download
        unlink($filename);

        exit();
    } else {
        // No data found for the selected month
        $alert .= '<div id="alert-message" class="flex items-center rounded bg-danger-light p-3.5 text-danger dark:bg-danger-dark-light">
                     <span class="ltr:pr-2 rtl:pl-2"><strong class="ltr:mr-1 rtl:ml-1">Attention!</strong> Download failed. No data available for this month.</span>
                 </div>';
    }
}




?>
<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>MediaNest</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" type="image/x-icon" href="favicon.png" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" media="screen" href="assets/css/perfect-scrollbar.min.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="assets/css/style.css" />
    <link defer rel="stylesheet" type="text/css" media="screen" href="assets/css/animate.css" />
    <script src="assets/js/perfect-scrollbar.min.js"></script>
    <script defer src="assets/js/popper.min.js"></script>
    <script defer src="assets/js/tippy-bundle.umd.min.js"></script>
    <script defer src="assets/js/sweetalert.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
</head>

<body x-data="main" class="relative overflow-x-hidden font-nunito text-sm font-normal antialiased" :class="[ $store.app.sidebar ? 'toggle-sidebar' : '', $store.app.theme === 'dark' || $store.app.isDarkMode ?  'dark' : '', $store.app.menu, $store.app.layout,$store.app.rtlClass]">
    <!-- sidebar menu overlay -->
    <div x-cloak class="fixed inset-0 z-50 bg-[black]/60 lg:hidden" :class="{'hidden' : !$store.app.sidebar}" @click="$store.app.toggleSidebar()"></div>
    <!-- screen loader -->
    <div class="screen_loader animate__animated fixed inset-0 z-[60] grid place-content-center bg-[#fafafa] dark:bg-[#060818]">
        <svg width="64" height="64" viewBox="0 0 135 135" xmlns="http://www.w3.org/2000/svg" fill="#4361ee">
            <path d="M67.447 58c5.523 0 10-4.477 10-10s-4.477-10-10-10-10 4.477-10 10 4.477 10 10 10zm9.448 9.447c0 5.523 4.477 10 10 10 5.522 0 10-4.477 10-10s-4.478-10-10-10c-5.523 0-10 4.477-10 10zm-9.448 9.448c-5.523 0-10 4.477-10 10 0 5.522 4.477 10 10 10s10-4.478 10-10c0-5.523-4.477-10-10-10zM58 67.447c0-5.523-4.477-10-10-10s-10 4.477-10 10 4.477 10 10 10 10-4.477 10-10z">
                <animateTransform attributeName="transform" type="rotate" from="0 67 67" to="-360 67 67" dur="2.5s" repeatCount="indefinite" />
            </path>
            <path d="M28.19 40.31c6.627 0 12-5.374 12-12 0-6.628-5.373-12-12-12-6.628 0-12 5.372-12 12 0 6.626 5.372 12 12 12zm30.72-19.825c4.686 4.687 12.284 4.687 16.97 0 4.686-4.686 4.686-12.284 0-16.97-4.686-4.687-12.284-4.687-16.97 0-4.687 4.686-4.687 12.284 0 16.97zm35.74 7.705c0 6.627 5.37 12 12 12 6.626 0 12-5.373 12-12 0-6.628-5.374-12-12-12-6.63 0-12 5.372-12 12zm19.822 30.72c-4.686 4.686-4.686 12.284 0 16.97 4.687 4.686 12.285 4.686 16.97 0 4.687-4.686 4.687-12.284 0-16.97-4.685-4.687-12.283-4.687-16.97 0zm-7.704 35.74c-6.627 0-12 5.37-12 12 0 6.626 5.373 12 12 12s12-5.374 12-12c0-6.63-5.373-12-12-12zm-30.72 19.822c-4.686-4.686-12.284-4.686-16.97 0-4.686 4.687-4.686 12.285 0 16.97 4.686 4.687 12.284 4.687 16.97 0 4.687-4.685 4.687-12.283 0-16.97zm-35.74-7.704c0-6.627-5.372-12-12-12-6.626 0-12 5.373-12 12s5.374 12 12 12c6.628 0 12-5.373 12-12zm-19.823-30.72c4.687-4.686 4.687-12.284 0-16.97-4.686-4.686-12.284-4.686-16.97 0-4.687 4.686-4.687 12.284 0 16.97 4.686 4.687 12.284 4.687 16.97 0z">
                <animateTransform attributeName="transform" type="rotate" from="0 67 67" to="360 67 67" dur="8s" repeatCount="indefinite" />
            </path>
        </svg>
    </div>
    <!-- scroll to top button -->
    <div class="fixed bottom-6 z-50 ltr:right-6 rtl:left-6" x-data="scrollToTop">
        <template x-if="showTopButton">
            <button type="button" class="btn btn-outline-primary animate-pulse rounded-full bg-[#fafafa] p-2 dark:bg-[#060818] dark:hover:bg-primary" @click="goToTop">
                <svg width="24" height="24" class="h-4 w-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path opacity="0.5" fill-rule="evenodd" clip-rule="evenodd" d="M12 20.75C12.4142 20.75 12.75 20.4142 12.75 20L12.75 10.75L11.25 10.75L11.25 20C11.25 20.4142 11.5858 20.75 12 20.75Z" fill="currentColor" />
                    <path d="M6.00002 10.75C5.69667 10.75 5.4232 10.5673 5.30711 10.287C5.19103 10.0068 5.25519 9.68417 5.46969 9.46967L11.4697 3.46967C11.6103 3.32902 11.8011 3.25 12 3.25C12.1989 3.25 12.3897 3.32902 12.5304 3.46967L18.5304 9.46967C18.7449 9.68417 18.809 10.0068 18.6929 10.287C18.5768 10.5673 18.3034 10.75 18 10.75L6.00002 10.75Z" fill="currentColor" />
                </svg>
            </button>
        </template>
    </div>
    <!-- start main content section -->
    <div class="main-container min-h-screen text-black dark:text-white-dark" :class="[$store.app.navbar]">
        <div class="main-content flex min-h-screen flex-col">
            <?php include('includes/header.php'); ?>
            <div class="dvanimation animate__animated p-6" :class="[$store.app.animation]">
                <?php include('includes/sidebar.php'); ?>
                <div class="container mx-auto p-6">
                    <?php echo $alert; ?>
                    <br>
                    <h2 class="text-2xl font-bold mb-4">2024</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <?php
                        foreach ($months as $index => $month) {
                            $textColor = 'text-black';
                            $bgColor = $index % 2 === 0 ? 'bg-gray-100' : 'bg-white';
                            echo '<div class="border border-gray-300 p-4 rounded-md ' . $bgColor . '">';
                            echo '<div class="flex justify-between items-center mb-2"><span class="' . $textColor . '">' . $month . ' Archives</span>';
                            echo '<div class="flex space-x-2">';
                            echo '<form method="post" action="archive-footages.php">';
                            echo '<input type="hidden" name="month" value="' . ($index + 1) . '">';
                            echo '<button type="submit" class="download-btn ' . $textColor . '"><i class="fas fa-download"></i> Download</button>';
                            echo '</form>';
                            echo '</div></div></div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
            <div class="dvanimation animate__animated p-6" :class="[$store.app.animation]">
                <?php include('includes/sidebar.php'); ?>
                <div class="container mx-auto p-6">
                    <h2 class="text-2xl font-bold mb-4">2025</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <?php
                        foreach ($months as $index => $month) {
                            $textColor = 'text-black';
                            $bgColor = $index % 2 === 0 ? 'bg-gray-100' : 'bg-white';
                            echo '<div class="border border-gray-300 p-4 rounded-md ' . $bgColor . '">';
                            echo '<div class="flex justify-between items-center mb-2"><span class="' . $textColor . '">' . $month . ' Archives</span>';
                            echo '<div class="flex space-x-2">';
                            echo '<form method="post" action="archive-footages.php">';
                            echo '<input type="hidden" name="month" value="' . ($index + 1) . '">';
                            echo '<button type="submit" class="download-btn ' . $textColor . '"><i class="fas fa-download"></i> Download</button>';
                            echo '</form>';
                            echo '</div></div></div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
            <!-- start footer section -->
            <!-- end footer section -->
        </div>
    </div>
    <!-- end main content section -->
    <script src="assets/js/alpine-collaspe.min.js"></script>
    <script src="assets/js/alpine-persist.min.js"></script>
    <script defer src="assets/js/alpine-ui.min.js"></script>
    <script defer src="assets/js/alpine-focus.min.js"></script>
    <script defer src="assets/js/alpine.min.js"></script>
    <script src="assets/js/custom.js"></script>
    <script>
        // Remove the alert message after 2 seconds
        setTimeout(function() {
            var alertMessage = document.getElementById('alert-message');
            alertMessage.parentNode.removeChild(alertMessage);
        }, 2000);

        document.addEventListener('alpine:init', () => {
            // main section
            Alpine.data('scrollToTop', () => ({
                showTopButton: false,
                init() {
                    window.onscroll = () => {
                        this.scrollFunction();
                    };
                },

                scrollFunction() {
                    if (document.body.scrollTop > 50 || document.documentElement.scrollTop > 50) {
                        this.showTopButton = true;
                    } else {
                        this.showTopButton = false;
                    }
                },

                goToTop() {
                    document.body.scrollTop = 0;
                    document.documentElement.scrollTop = 0;
                },
            }));

            // theme customization
            Alpine.data('customizer', () => ({
                showCustomizer: false,
            }));

            // sidebar section
            Alpine.data('sidebar', () => ({
                init() {
                    const selector = document.querySelector('.sidebar ul a[href="' + window.location.pathname + '"]');
                    if (selector) {
                        selector.classList.add('active');
                        const ul = selector.closest('ul.sub-menu');
                        if (ul) {
                            let ele = ul.closest('li.menu').querySelectorAll('.nav-link');
                            if (ele) {
                                ele = ele[0];
                                setTimeout(() => {
                                    ele.click();
                                });
                            }
                        }
                    }
                },
            }));

            // header section
            Alpine.data('header', () => ({
                init() {
                    const selector = document.querySelector('ul.horizontal-menu a[href="' + window.location.pathname + '"]');
                    if (selector) {
                        selector.classList.add('active');
                        const ul = selector.closest('ul.sub-menu');
                        if (ul) {
                            let ele = ul.closest('li.menu').querySelectorAll('.nav-link');
                            if (ele) {
                                ele = ele[0];
                                setTimeout(() => {
                                    ele.classList.add('active');
                                });
                            }
                        }
                    }
                },

                notifications: [{
                        id: 1,
                        profile: 'user-profile.jpeg',
                        message: '<strong class="text-sm mr-1">John Doe</strong>invite you to <strong>Prototyping</strong>',
                        time: '45 min ago',
                    },
                    {
                        id: 2,
                        profile: 'profile-34.jpeg',
                        message: '<strong class="text-sm mr-1">Adam Nolan</strong>mentioned you to <strong>UX Basics</strong>',
                        time: '9h Ago',
                    },
                    {
                        id: 3,
                        profile: 'profile-16.jpeg',
                        message: '<strong class="text-sm mr-1">Anna Morgan</strong>Upload a file',
                        time: '9h Ago',
                    },
                ],

                messages: [{
                        id: 1,
                        image: '<span class="grid place-content-center w-9 h-9 rounded-full bg-success-light dark:bg-success text-success dark:text-success-light"><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg></span>',
                        title: 'Congratulations!',
                        message: 'Your OS has been updated.',
                        time: '1hr',
                    },
                    {
                        id: 2,
                        image: '<span class="grid place-content-center w-9 h-9 rounded-full bg-info-light dark:bg-info text-info dark:text-info-light"><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg></span>',
                        title: 'Did you know?',
                        message: 'You can switch between artboards.',
                        time: '2hr',
                    },
                    {
                        id: 3,
                        image: '<span class="grid place-content-center w-9 h-9 rounded-full bg-danger-light dark:bg-danger text-danger dark:text-danger-light"><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></span>',
                        title: 'Something went wrong!',
                        message: 'Send Reposrt',
                        time: '2days',
                    },
                    {
                        id: 4,
                        image: '<span class="grid place-content-center w-9 h-9 rounded-full bg-warning-light dark:bg-warning text-warning dark:text-warning-light"><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">    <circle cx="12" cy="12" r="10"></circle>    <line x1="12" y1="8" x2="12" y2="12"></line>    <line x1="12" y1="16" x2="12.01" y2="16"></line></svg></span>',
                        title: 'Warning',
                        message: 'Your password strength is low.',
                        time: '5days',
                    },
                ],

                languages: [{
                        id: 1,
                        key: 'Chinese',
                        value: 'zh',
                    },
                    {
                        id: 2,
                        key: 'Danish',
                        value: 'da',
                    },
                    {
                        id: 3,
                        key: 'English',
                        value: 'en',
                    },
                    {
                        id: 4,
                        key: 'French',
                        value: 'fr',
                    },
                    {
                        id: 5,
                        key: 'German',
                        value: 'de',
                    },
                    {
                        id: 6,
                        key: 'Greek',
                        value: 'el',
                    },
                    {
                        id: 7,
                        key: 'Hungarian',
                        value: 'hu',
                    },
                    {
                        id: 8,
                        key: 'Italian',
                        value: 'it',
                    },
                    {
                        id: 9,
                        key: 'Japanese',
                        value: 'ja',
                    },
                    {
                        id: 10,
                        key: 'Polish',
                        value: 'pl',
                    },
                    {
                        id: 11,
                        key: 'Portuguese',
                        value: 'pt',
                    },
                    {
                        id: 12,
                        key: 'Russian',
                        value: 'ru',
                    },
                    {
                        id: 13,
                        key: 'Spanish',
                        value: 'es',
                    },
                    {
                        id: 14,
                        key: 'Swedish',
                        value: 'sv',
                    },
                    {
                        id: 15,
                        key: 'Turkish',
                        value: 'tr',
                    },
                    {
                        id: 16,
                        key: 'Arabic',
                        value: 'ae',
                    },
                ],

                removeNotification(value) {
                    this.notifications = this.notifications.filter((d) => d.id !== value);
                },

                removeMessage(value) {
                    this.messages = this.messages.filter((d) => d.id !== value);
                },
            }));
            //notes
            Alpine.data('notes', () => ({
                defaultParams: {
                    id: null,
                    title: '',
                    description: '',
                    tag: '',
                    user: '',
                    thumb: '',
                },
                isAddNoteModal: false,
                isDeleteNoteModal: false,
                isViewNoteModal: false,
                params: {
                    id: null,
                    title: '',
                    description: '',
                    tag: '',
                    user: '',
                    thumb: '',
                },
                isShowNoteMenu: false,
                notesList: [{
                        id: 1,
                        user: 'Max Smith',
                        thumb: 'profile-16.jpeg',
                        title: 'Meeting with Kelly',
                        description: 'Curabitur facilisis vel elit sed dapibus sodales purus rhoncus.',
                        date: '11/01/2020',
                        isFav: false,
                        tag: 'personal',
                    },
                    {
                        id: 2,
                        user: 'John Doe',
                        thumb: 'profile-14.jpeg',
                        title: 'Receive Package',
                        description: 'Facilisis curabitur facilisis vel elit sed dapibus sodales purus.',
                        date: '11/02/2020',
                        isFav: true,
                        tag: '',
                    },
                    {
                        id: 3,
                        user: 'Kia Jain',
                        thumb: 'profile-15.jpeg',
                        title: 'Download Docs',
                        description: 'Proin a dui malesuada, laoreet mi vel, imperdiet diam quam laoreet.',
                        date: '11/04/2020',
                        isFav: false,
                        tag: 'work',
                    },
                    {
                        id: 4,
                        user: 'Max Smith',
                        thumb: 'profile-16.jpeg',
                        title: 'Meeting at 4:50pm',
                        description: 'Excepteur sint occaecat cupidatat non proident, anim id est laborum.',
                        date: '11/08/2020',
                        isFav: false,
                        tag: '',
                    },
                    {
                        id: 5,
                        user: 'Karena Courtliff',
                        thumb: 'profile-17.jpeg',
                        title: 'Backup Files EOD',
                        description: 'Maecenas condimentum neque mollis, egestas leo ut, gravida.',
                        date: '11/09/2020',
                        isFav: false,
                        tag: '',
                    },
                    {
                        id: 6,
                        user: 'Max Smith',
                        thumb: 'profile-16.jpeg',
                        title: 'Download Server Logs',
                        description: 'Suspendisse efficitur diam quis gravida. Nunc molestie est eros.',
                        date: '11/09/2020',
                        isFav: false,
                        tag: 'social',
                    },
                    {
                        id: 7,
                        user: 'Vladamir Koschek',
                        thumb: '',
                        title: 'Team meet at Starbucks',
                        description: 'Etiam a odio eget enim aliquet laoreet lobortis sed ornare nibh.',
                        date: '11/10/2020',
                        isFav: false,
                        tag: '',
                    },
                    {
                        id: 8,
                        user: 'Max Smith',
                        thumb: 'profile-16.jpeg',
                        title: 'Create new users Profile',
                        description: 'Duis aute irure in nulla pariatur. Etiam a odio eget enim aliquet.',
                        date: '11/11/2020',
                        isFav: false,
                        tag: 'important',
                    },
                    {
                        id: 9,
                        user: 'Robert Garcia',
                        thumb: 'profile-21.jpeg',
                        title: 'Create a compost pile',
                        description: 'Zombie ipsum reversus ab viral inferno, nam rick grimes malum cerebro.',
                        date: '11/12/2020',
                        isFav: true,
                        tag: '',
                    },
                    {
                        id: 10,
                        user: 'Marie Hamilton',
                        thumb: 'profile-2.jpeg',
                        title: 'Take a hike at a local park',
                        description: 'De carne lumbering animata corpora quaeritis. Summus brains sit',
                        date: '11/13/2020',
                        isFav: true,
                        tag: '',
                    },
                    {
                        id: 11,
                        user: 'Megan Meyers',
                        thumb: 'profile-1.jpeg',
                        title: 'Take a class at local community center that interests you',
                        description: 'Cupcake ipsum dolor. Sit amet marshmallow topping cheesecake muffin.',
                        date: '11/13/2020',
                        isFav: false,
                        tag: '',
                    },
                    {
                        id: 12,
                        user: 'Angela Hull',
                        thumb: 'profile-22.jpeg',
                        title: 'Research a topic interested in',
                        description: 'Lemon drops tootsie roll marshmallow halvah carrot cake.',
                        date: '11/14/2020',
                        isFav: false,
                        tag: '',
                    },
                    {
                        id: 13,
                        user: 'Karen Wolf',
                        thumb: 'profile-23.jpeg',
                        title: 'Plan a trip to another country',
                        description: 'Space, the final frontier. These are the voyages of the Starship Enterprise.',
                        date: '11/16/2020',
                        isFav: true,
                        tag: '',
                    },
                    {
                        id: 14,
                        user: 'Jasmine Barnes',
                        thumb: 'profile-1.jpeg',
                        title: 'Improve touch typing',
                        description: 'Well, the way they make shows is, they make one show.',
                        date: '11/16/2020',
                        isFav: false,
                        tag: '',
                    },
                    {
                        id: 15,
                        user: 'Thomas Cox',
                        thumb: 'profile-11.jpeg',
                        title: 'Learn Express.js',
                        description: 'Bulbasaur Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
                        date: '11/17/2020',
                        isFav: false,
                        tag: 'work',
                    },
                    {
                        id: 16,
                        user: 'Marcus Jones',
                        thumb: 'profile-12.jpeg',
                        title: 'Learn calligraphy',
                        description: 'Ivysaur Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
                        date: '11/17/2020',
                        isFav: false,
                        tag: '',
                    },
                    {
                        id: 17,
                        user: 'Matthew Gray',
                        thumb: 'profile-24.jpeg',
                        title: 'Have a photo session with some friends',
                        description: 'Venusaur Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
                        date: '11/18/2020',
                        isFav: false,
                        tag: 'important',
                    },
                    {
                        id: 18,
                        user: 'Chad Davis',
                        thumb: 'profile-31.jpeg',
                        title: 'Go to the gym',
                        description: 'Charmander Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
                        date: '11/18/2020',
                        isFav: false,
                        tag: '',
                    },
                    {
                        id: 19,
                        user: 'Linda Drake',
                        thumb: 'profile-23.jpeg',
                        title: 'Make own LEGO creation',
                        description: 'Charmeleon Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
                        date: '11/18/2020',
                        isFav: false,
                        tag: 'social',
                    },
                    {
                        id: 20,
                        user: 'Kathleen Flores',
                        thumb: 'profile-34.jpeg',
                        title: 'Take cat on a walk',
                        description: 'Baseball ipsum dolor sit amet cellar rubber win hack tossed. ',
                        date: '11/18/2020',
                        isFav: false,
                        tag: 'personal',
                    },
                ],
                filterdNotesList: '',
                selectedTab: 'all',
                deletedNote: null,
                selectedNote: {
                    id: null,
                    title: '',
                    description: '',
                    tag: '',
                    user: '',
                    thumb: '',
                },

                init() {
                    this.searchNotes();
                },

                searchNotes() {
                    if (this.selectedTab != 'fav') {
                        if (this.selectedTab != 'all' || this.selectedTab === 'delete') {
                            this.filterdNotesList = this.notesList.filter((d) => d.tag === this.selectedTab);
                        } else {
                            this.filterdNotesList = this.notesList;
                        }
                    } else {
                        this.filterdNotesList = this.notesList.filter((d) => d.isFav);
                    }
                },

                saveNote() {
                    if (!this.params.title) {
                        this.showMessage('Title is required.', 'error');
                        return false;
                    }
                    if (this.params.id) {
                        //update task
                        let note = this.notesList.find((d) => d.id === this.params.id);
                        note.title = this.params.title;
                        note.user = this.params.user;
                        note.description = this.params.description;
                        note.tag = this.params.tag;
                    } else {
                        //add note
                        let maxNoteId = this.notesList.length ?
                            this.notesList.reduce((max, character) => (character.id > max ? character.id : max), this.notesList[0].id) :
                            0;
                        if (!maxNoteId) {
                            maxNoteId = 0;
                        }
                        let dt = new Date();
                        let note = {
                            id: maxNoteId + 1,
                            title: this.params.title,
                            user: this.params.user,
                            thumb: 'profile-21.jpeg',
                            description: this.params.description,
                            date: dt.getDate() + '/' + Number(dt.getMonth()) + 1 + '/' + dt.getFullYear(),
                            isFav: false,
                            tag: this.params.tag,
                        };
                        this.notesList.splice(0, 0, note);
                        this.searchNotes();
                    }

                    this.showMessage('Note has been saved successfully.');
                    this.isAddNoteModal = false;
                    this.searchNotes();
                },

                tabChanged(type) {
                    this.selectedTab = type;
                    this.searchNotes();
                    this.isShowNoteMenu = false;
                },

                setFav(note) {
                    let item = this.filterdNotesList.find((d) => d.id === note.id);
                    item.isFav = !item.isFav;
                    this.searchNotes();
                },

                setTag(note, name) {
                    let item = this.filterdNotesList.find((d) => d.id === note.id);
                    item.tag = name;
                    this.searchNotes();
                },

                deleteNoteConfirm(note) {
                    setTimeout(() => {
                        this.deletedNote = note;
                        this.isDeleteNoteModal = true;
                    });
                },

                viewNote(note) {
                    setTimeout(() => {
                        this.selectedNote = note;
                        this.isViewNoteModal = true;
                    });
                },

                editNote(note) {
                    this.isShowNoteMenu = false;
                    setTimeout(() => {
                        this.params = JSON.parse(JSON.stringify(this.defaultParams));
                        if (note) {
                            this.params = JSON.parse(JSON.stringify(note));
                        }
                        this.isAddNoteModal = true;
                    });
                },

                deleteNote() {
                    this.notesList = this.notesList.filter((d) => d.id != this.deletedNote.id);
                    this.searchNotes();
                    this.showMessage('Note has been deleted successfully.');
                    this.isDeleteNoteModal = false;
                },

                showMessage(msg = '', type = 'success') {
                    const toast = window.Swal.mixin({
                        toast: true,
                        position: 'top',
                        showConfirmButton: false,
                        timer: 3000,
                    });
                    toast.fire({
                        icon: type,
                        title: msg,
                        padding: '10px 20px',
                    });
                },
            }));
        });
    </script>
</body>

</html>