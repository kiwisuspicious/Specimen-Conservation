<?php
session_start();
if (!isset($_SESSION['stafflogin'])) {
    header('Location: https://localhost/vg/login/login.php');
    exit();
}elseif ($_SESSION['designation'] !== 'archive' && $_SESSION['designation'] !== 'vg' && $_SESSION['designation'] !== 'pg' && $_SESSION['designation'] !== 'boss') {
    header('Location: https://localhost/vg/dashboard/index.php');
    exit();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/config.php');
$pdo_login = pdo_connect_mysql();

$stmt = $pdo_login->prepare("SELECT * FROM archive");
$stmt->execute();
$archives = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch count of total submissions
$stmtTotal = $pdo_login->prepare("SELECT COUNT(*) AS total FROM archive");
$stmtTotal->execute();
$totalResult = $stmtTotal->fetch(PDO::FETCH_ASSOC);
$totalSubmissions = $totalResult['total'];

// Fetch count of archives to be archived (status = 0)
$stmtToBeArchived = $pdo_login->prepare("SELECT COUNT(*) AS toBeArchived FROM archive WHERE status = 0");
$stmtToBeArchived->execute();
$toBeArchivedResult = $stmtToBeArchived->fetch(PDO::FETCH_ASSOC);
$archivesToBeArchived = $toBeArchivedResult['toBeArchived'];

// Fetch count of archives completed (status = 1)
$stmtCompleted = $pdo_login->prepare("SELECT COUNT(*) AS completed FROM archive WHERE status = 1");
$stmtCompleted->execute();
$completedResult = $stmtCompleted->fetch(PDO::FETCH_ASSOC);
$archivesCompleted = $completedResult['completed'];

// Fetch count of footages not found (status = -1)
$stmtFootagesNotFound = $pdo_login->prepare("SELECT COUNT(*) AS footagesNotFound FROM archive WHERE status = -1");
$stmtFootagesNotFound->execute();
$footagesNotFoundResult = $stmtFootagesNotFound->fetch(PDO::FETCH_ASSOC);
$footagesNotFound = $footagesNotFoundResult['footagesNotFound'];

// Placeholder for "Your Submission"
$stmtYourSubmission = $pdo_login->prepare("SELECT COUNT(*) AS yourSubmission FROM archive WHERE submitBy = ?");
$stmtYourSubmission->execute([$_SESSION['name']]);
$yourSubmissionResult = $stmtYourSubmission->fetch(PDO::FETCH_ASSOC);
$yourSubmission = $yourSubmissionResult['yourSubmission'];

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Get the record ID from the request
    $requestId = isset($_GET['requestId']) ? $_GET['requestId'] : null;

    if (!$requestId) {
        // Handle invalid request
        http_response_code(400);
        exit('Invalid request.');
    }

    // Prepare the SQL statement to delete the record
    $stmt = $pdo_login->prepare("DELETE FROM archive WHERE requestId = :requestId");
    $stmt->bindParam(':requestId', $requestId);

    // Execute the SQL statement
    if ($stmt->execute()) {
        // Send a success response
        http_response_code(204);
    } else {
        // Send an error response
        http_response_code(500);
        exit('Failed to delete the record.');
    }
}

?>

<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Medianest</title>
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



    <div class="main-container min-h-screen text-black dark:text-white-dark" :class="[$store.app.navbar]">


        <div class="main-content flex min-h-screen flex-col">

            <?php include('includes/header.php'); ?>
            <div class="dvanimation animate__animated p-6" :class="[$store.app.animation]">
                <?php include('includes/sidebar.php'); ?>

                <!-- start main content section -->
                <div x-data="chart">



                    <div class="mb-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        <!-- Display total submissions -->
                        <div class="panel grid h-full grid-cols-1 content-between overflow-hidden before:absolute before:-right-44 before:bottom-0 before:top-0 before:m-auto before:h-96 before:w-96 before:rounded-full" style="background: linear-gradient(0deg, #a0eaff, #74c7f8);">
                            <div class="z-[7] mb-4 flex items-start justify-between text-black">
                                <h5 class="text-lg font-semibold">Total Archive Submission</h5>
                                <div class="flex items-center">
                                    <svg class="h-6 w-6 text-black mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M3 2a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1V6.414a1 1 0 0 0-.293-.707l-4.293-4.293a1 1 0 0 0-.707-.293H3zM2 5l4 4h11v10H3a1 1 0 0 1-1-1V5z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="text-lg font-semibold">Files</span>
                                </div>
                            </div>
                            <div class="z-[7] flex items-start justify-between text-black">
                                <p class="text-3xl font-semibold"><?php echo $totalSubmissions; ?></p>
                            </div>
                        </div>

                        <!-- Display archives to be archived -->
                        <div class="panel grid h-full grid-cols-1 content-between overflow-hidden before:absolute before:-right-44 before:bottom-0 before:top-0 before:m-auto before:h-96 before:w-96 before:rounded-full" style="background: linear-gradient(0deg, #f8d49e, #f8a649);">
                            <div class="z-[7] mb-4 flex items-start justify-between text-black">
                                <h5 class="text-lg font-semibold">To Be Archived</h5>
                                <div class="flex items-center">
                                    <svg class="h-6 w-6 text-black mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M2 3a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1V4a1 1 0 0 0-1-1H2zm1 2h14v3H3V5zm0 5h3v9H3V10zm5 0h9v9H8V10zm10 0h-3v9h3V10z" />
                                    </svg>
                                    <span class="text-lg font-semibold">Archive</span>
                                </div>
                            </div>
                            <div class="z-[7] flex items-start justify-between text-black">
                                <p class="text-3xl font-semibold"><?php echo $archivesToBeArchived; ?></p>
                            </div>
                        </div>

                        <!-- Display archives completed -->
                        <div class="panel grid h-full grid-cols-1 content-between overflow-hidden before:absolute before:-right-44 before:bottom-0 before:top-0 before:m-auto before:h-96 before:w-96 before:rounded-full" style="background: linear-gradient(0deg, #9be5b5, #39ac73);">
                            <div class="z-[7] mb-4 flex items-start justify-between text-black">
                                <h5 class="text-lg font-semibold">Archives Completed</h5>
                                <div class="flex items-center">
                                    <svg class="h-6 w-6 text-black mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M18 3H2a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2zM9.223 11.508L2.927 6.784A1 1 0 0 1 3.5 5h12.992a1 1 0 0 1 .573 1.784l-6.296 4.724a.5.5 0 0 1-.554 0z" />
                                    </svg>
                                    <span class="text-lg font-semibold">Archive</span>
                                </div>
                            </div>
                            <div class="z-[7] flex items-start justify-between text-black">
                                <p class="text-3xl font-semibold"><?php echo $archivesCompleted; ?></p>
                            </div>
                        </div>

                        <!-- Display footages not found -->
                        <div class="panel grid h-full grid-cols-1 content-between overflow-hidden before:absolute before:-right-44 before:bottom-0 before:top-0 before:m-auto before:h-96 before:w-96 before:rounded-full" style="background: linear-gradient(0deg, #ffadad, #ff6f6f);">
                            <div class="z-[7] mb-4 flex items-start justify-between text-black">
                                <h5 class="text-lg font-semibold">Footages Not Found</h5>
                                <div class="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-black mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10" />
                                        <line x1="12" y1="8" x2="12" y2="12" />
                                        <line x1="12" y1="16" x2="12" y2="16" />
                                    </svg>
                                    <span class="text-lg font-semibold">Archive</span>
                                </div>
                            </div>
                            <div class="z-[7] flex items-start justify-between text-black">
                                <p class="text-3xl font-semibold"><?php echo $footagesNotFound; ?></p>
                            </div>
                        </div>

                        <!-- Display your submission -->
                        <?php
                        if ($_SESSION['designation'] === 'vg' || $_SESSION['designation'] === 'pg') {
                        ?>
                            <div class="panel grid h-full grid-cols-1 content-between overflow-hidden before:absolute before:-right-44 before:bottom-0 before:top-0 before:m-auto before:h-96 before:w-96 before:rounded-full" style="background: linear-gradient(0deg, #fffbbe, #ffea00);">
                                <div class="z-[7] mb-4 flex items-start justify-between text-black">
                                    <h5 class="text-lg font-semibold">Your Submission</h5>
                                    <div class="flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-black mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 6H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2M8 6a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-8a2 2 0 0 1-2-2V6z"></path>
                                        </svg>
                                        <span class="text-lg font-semibold">Clipboard</span>
                                    </div>
                                </div>
                                <div class="z-[7] flex items-start justify-between text-black">
                                    <p class="text-3xl font-semibold"><?php echo $yourSubmission; ?></p>
                                </div>
                            </div>
                        <?php
                        }
                        ?>
                    </div>
                    <div class="panel h-full w-full">
                        <div class="mb-5 flex items-center justify-between">
                            <h5 class="text-lg font-semibold dark:text-white-light">Latest Submissions</h5>
                        </div>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th class="ltr:rounded-l-md rtl:rounded-r-md">No.</th>
                                        <th>Videographer/Photographer</th>
                                        <th>Cutways</th>
                                        <th>Folder Name</th>
                                        <th>Status</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Reverse the order of the archives array to display the newest records first
                                    $archives = array_reverse($archives);

                                    // Loop through the first 10 records (the newest ones)
                                    $count = 1;
                                    foreach ($archives as $row) {
                                        if ($count > 10) {
                                            break; // Exit the loop once 10 records are displayed
                                        }
                                    ?>
                                        <tr class="group text-white-dark hover:text-black dark:hover:text-white-light/90">
                                            <td class="min-w-[150px] text-black dark:text-white">
                                                <div class="flex items-center">
                                                    <span class="whitespace-nowrap"><?php echo $count; ?></span>
                                                </div>
                                            </td>
                                            <td class="text-primary"><?php echo $row['vgPg']; ?></td>
                                            <td><?php echo $row['cutways']; ?></td>
                                            <td><?php echo $row['folderName']; ?></td>
                                            <td class="text-primary">
                                                <?php
                                                if ($row["status"] == 0) {
                                                    echo '<span class="badge bg-warning shadow-md">Pending</span>';
                                                } elseif ($row["status"] == 1) {
                                                    echo '<span class="badge bg-success shadow-md">Completed</span>';
                                                } elseif ($row["status"] == -1) {
                                                    echo '<span class="badge bg-danger shadow-md">Rejected</span>';
                                                } else {
                                                    echo ''; // Handle other cases here
                                                }
                                                ?>
                                            </td>
                                            <?php
                                            if ($_SESSION['designation'] === 'vg' || $_SESSION['designation'] === 'pg') {
                                                if ($row['submitBy'] === $_SESSION['name']) {
                                            ?>
                                                    <td class="text-primary">
                                                        <?php
                                                        if ($row["status"] == 0) {
                                                            echo '<span><button class="badge bg-secondary shadow-md dark:group-hover:bg-transparent" onclick="deleteRecord(' . $row['requestId'] . ')">Undo</button></span>';
                                                        }
                                                        ?>
                                                    </td>
                                            <?php
                                                }
                                            }
                                            ?>
                                        </tr>
                                    <?php
                                        $count++;
                                    }
                                    ?>
                                </tbody>

                            </table>
                        </div>
                    </div>
                </div>
                <!-- end main content section -->
            </div>

            <!-- start footer section -->
            <!-- end footer section -->


        </div>
    </div>

    <script src="assets/js/alpine-collaspe.min.js"></script>
    <script src="assets/js/alpine-persist.min.js"></script>
    <script defer src="assets/js/alpine-ui.min.js"></script>
    <script defer src="assets/js/alpine-focus.min.js"></script>
    <script defer src="assets/js/alpine.min.js"></script>
    <script src="assets/js/custom.js"></script>
    <script defer src="assets/js/apexcharts.js"></script>

    <script>
        function deleteRecord(requestId) {
            // Confirm deletion
            if (confirm("Are you sure you want to delete this record?")) {
                fetch('submission.php?requestId=' + requestId, {
                        method: 'DELETE'
                    })
                    .then(response => {
                        if (response.ok) {
                            // Reload the page to reflect the changes
                            location.reload();
                        } else {
                            // Handle errors
                            alert("Failed to delete the record.");
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert("Failed to delete the record.");
                    });
            }
        }
    </script>



</body>

</html>