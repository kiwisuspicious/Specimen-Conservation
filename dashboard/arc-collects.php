<?php
session_start();
if (!isset($_SESSION['stafflogin'])) {
    header('Location: https://localhost/vg/login/login.php');
    exit();
} elseif ($_SESSION['designation'] !== 'archive' && $_SESSION['designation'] !== 'vg' && $_SESSION['designation'] !== 'pg' && $_SESSION['designation'] !== 'boss') {
    header('Location: https://localhost/vg/dashboard/index.php');
    exit();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/config.php');
$pdo_vg = pdo_connect_mysql();
$pdo_smg = pdo_connect_mysql2();

$adminInchargeName = $_SESSION['name'];

if ($_SESSION['designation'] == 'archive') {
    // Fetch data for pie chart
    $sqlPie = "SELECT adminIncharge AS name, COUNT(*) as count FROM archive WHERE (status = 1 OR status = -1) AND adminIncharge = :adminInchargeName AND (folderName IS NULL OR folderName = '') GROUP BY adminIncharge";
    $queryPie = $pdo_vg->prepare($sqlPie);
    $queryPie->bindParam(':adminInchargeName', $adminInchargeName, PDO::PARAM_STR);
    $queryPie->execute();
    $dataPie = $queryPie->fetchAll(PDO::FETCH_ASSOC);

    // SQL query to fetch the required data
    $sqlLine = "SELECT vgPg AS name, DATE_FORMAT(date, '%Y-%m') as month FROM archive WHERE (status = 1 OR status = -1) AND adminIncharge = :adminInchargeName AND (folderName IS NULL OR folderName = '')";
    $queryLine = $pdo_vg->prepare($sqlLine);
    $queryLine->bindParam(':adminInchargeName', $adminInchargeName, PDO::PARAM_STR);

    // Execute the query
    $queryLine->execute();
    $dataLine = $queryLine->fetchAll(PDO::FETCH_ASSOC);
} elseif ($_SESSION['designation'] == 'vg' || $_SESSION['designation'] == 'pg' || $_SESSION['designation'] == 'boss') {
    $sqlPie = "SELECT vgPg AS name, COUNT(*) as count FROM archive GROUP BY vgPg";
    $queryPie = $pdo_vg->prepare($sqlPie);
    $queryPie->execute();
    $dataPie = $queryPie->fetchAll(PDO::FETCH_ASSOC);

    // Fetch data for line chart
    $sqlLine = "SELECT vgPg AS name, DATE_FORMAT(date, '%Y-%m') as month FROM archive";
    $queryLine = $pdo_vg->prepare($sqlLine);
    $queryLine->execute();
    $dataLine = $queryLine->fetchAll(PDO::FETCH_ASSOC);
}

// Initialize arrays for storing monthly submissions for pg and vg
$months = [
    '01' => 'JANUARY', '02' => 'FEBRUARY', '03' => 'MARCH', '04' => 'APRIL',
    '05' => 'MAY', '06' => 'JUNE', '07' => 'JULY', '08' => 'AUGUST',
    '09' => 'SEPTEMBER', '10' => 'OCTOBER', '11' => 'NOVEMBER', '12' => 'DECEMBER'
];
$pgSubmissions = array_fill_keys(array_keys($months), 0);
$vgSubmissions = array_fill_keys(array_keys($months), 0);

foreach ($dataLine as $submission) {
    $month = date('m', strtotime($submission['month']));
    // Check the designation of the name in the smg table
    $stmt = $pdo_smg->prepare("SELECT designation FROM tblemployee WHERE name = :name");
    $stmt->execute(['name' => $submission['name']]);
    $designation = $stmt->fetchColumn();

    if ($designation == 'pg') {
        $pgSubmissions[$month]++;
    } elseif ($designation == 'vg') {
        $vgSubmissions[$month]++;
    }
}

// Format data for the pie chart
$labelsPie = [];
$seriesPie = [];
foreach ($dataPie as $row) {
    $labelsPie[] = $row['name'];
    $seriesPie[] = (int)$row['count']; // Convert count to integer
}

$pgData = array_values($pgSubmissions);
$vgData = array_values($vgSubmissions);

if ($_SESSION['designation'] == 'archive' || $_SESSION['designation'] == 'boss') {
    if ($_SESSION['designation'] == 'archive') {
        $sqlPieRequest = "SELECT adminInchargeRequest AS name, COUNT(*) as count FROM requestfootage WHERE requestStatus = 1 AND adminInchargeRequest = :adminInchargeName GROUP BY adminInchargeRequest";
        $queryPieRequest = $pdo_vg->prepare($sqlPieRequest);
        $queryPieRequest->bindParam(':adminInchargeName', $adminInchargeName, PDO::PARAM_STR);
        $queryPieRequest->execute();
        $dataPieRequest = $queryPieRequest->fetchAll(PDO::FETCH_ASSOC);

        $sqlLineRequest = "SELECT adminInchargeRequest AS name, DATE_FORMAT(submitDate, '%Y-%m') as month FROM requestfootage WHERE requestStatus = 1 AND adminInchargeRequest = :adminInchargeName";
        $queryLineRequest = $pdo_vg->prepare($sqlLineRequest);
        $queryLineRequest->bindParam(':adminInchargeName', $adminInchargeName, PDO::PARAM_STR);

        // Execute the query
        $queryLineRequest->execute();
        $dataLineRequest = $queryLineRequest->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($_SESSION['designation'] == 'boss') {
        $sqlPieRequest = "
        SELECT name, SUM(count) as total_count FROM (
            SELECT adminInchargeRequest AS name, COUNT(*) as count FROM requestfootage WHERE requestStatus = 1 GROUP BY adminInchargeRequest
            UNION ALL
            SELECT adminIncharge AS name, COUNT(*) as count FROM archive WHERE (status = 1 OR status = -1) AND (folderName IS NULL OR folderName = '') GROUP BY adminIncharge
        ) AS combined_data
        GROUP BY name";
        $queryPieRequest = $pdo_vg->prepare($sqlPieRequest);
        $queryPieRequest->execute();
        $dataPieRequest = $queryPieRequest->fetchAll(PDO::FETCH_ASSOC);

        // Fetch data for line chart (Request Footage)
        $sqlLineRequest = "SELECT
    name,
    month,
    COUNT(*) AS total_requests
FROM (
    SELECT
        adminIncharge AS name,
        DATE_FORMAT(date, '%Y-%m') as month
    FROM
        archive
    WHERE
        (status = 1 OR status = -1) AND (folderName IS NULL OR folderName = '')
    
    UNION ALL
    
    SELECT
        adminInchargeRequest AS name,
        DATE_FORMAT(submitDate, '%Y-%m') as month
    FROM
        requestfootage
    WHERE
        requestStatus = 1
) AS combined_requests
GROUP BY
    name,
    month
ORDER BY
    month
";
        $queryLineRequest = $pdo_vg->prepare($sqlLineRequest);
        $queryLineRequest->execute();
        $dataLineRequest = $queryLineRequest->fetchAll(PDO::FETCH_ASSOC);

        // Initialize arrays for storing monthly submissions for request footage
        $footageRequester = array_fill_keys(array_keys($months), 0); // Initialize the array with month keys and 0 values

        foreach ($dataLineRequest as $submission) {
            $month = date('m', strtotime($submission['month'])); // Extract the numeric month part from the date
            $footageRequester[$month] += $submission['total_requests'];
        }
        // Format data for the pie chart (Request Footage)
        $labelsPieRequest = [];
        $seriesPieRequest = [];
        foreach ($dataPieRequest as $row) {
            $labelsPieRequest[] = $row['name'];
            $seriesPieRequest[] = (int)$row['total_count']; // Convert count to integer
        }

        $requesterData = array_values($footageRequester);
    }

    if ($_SESSION['designation'] == 'archive') {
        // Initialize arrays for storing monthly submissions for request footage
        $footageRequester = array_fill_keys(array_keys($months), 0);

        foreach ($dataLineRequest as $submission) {
            $month = date('m', strtotime($submission['month']));
            $footageRequester[$month]++;
        }
        // Format data for the pie chart (Request Footage)
        $labelsPieRequest = [];
        $seriesPieRequest = [];
        foreach ($dataPieRequest as $row) {
            $labelsPieRequest[] = $row['name'];
            $seriesPieRequest[] = (int)$row['count']; // Convert count to integer
        }

        $requesterData = array_values($footageRequester);
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Initialize months array
    $months = [
        '01' => 'JANUARY', '02' => 'FEBRUARY', '03' => 'MARCH', '04' => 'APRIL',
        '05' => 'MAY', '06' => 'JUNE', '07' => 'JULY', '08' => 'AUGUST',
        '09' => 'SEPTEMBER', '10' => 'OCTOBER', '11' => 'NOVEMBER', '12' => 'DECEMBER'
    ];

    // Fetch submissions by each submitter for each month
    $sqlSubmissionsBySubmitter = "
    SELECT vgPg AS submitter, DATE_FORMAT(date, '%Y-%m') AS month, COUNT(*) as submissions 
    FROM archive 
    GROUP BY submitter, month
    ORDER BY month, submitter";

    $querySubmissionsBySubmitter = $pdo_vg->prepare($sqlSubmissionsBySubmitter);
    $querySubmissionsBySubmitter->execute();
    $submissionsBySubmitterData = $querySubmissionsBySubmitter->fetchAll(PDO::FETCH_ASSOC);

    // Fetch combined completed requests data
    $sqlCombinedRequests = "
    SELECT
        name,
        month,
        COUNT(*) AS total_requests
    FROM (
        SELECT
            adminIncharge AS name,
            DATE_FORMAT(date, '%Y-%m') as month
        FROM
            archive
        WHERE
            (status = 1 OR status = -1) AND (folderName IS NULL OR folderName = '')
        
        UNION ALL
        
        SELECT
            adminInchargeRequest AS name,
            DATE_FORMAT(submitDate, '%Y-%m') as month
        FROM
            requestfootage
        WHERE
            requestStatus = 1
    ) AS combined_requests
    GROUP BY
        name,
        month
    ORDER BY
        month, name";

    $queryCombinedRequests = $pdo_vg->prepare($sqlCombinedRequests);
    $queryCombinedRequests->execute();
    $combinedRequestsData = $queryCombinedRequests->fetchAll(PDO::FETCH_ASSOC);

    // Construct text content for submissions by each submitter
    $textContent = "VG/PG:\n";
    $textContent .= "\nTOTAL FOOTAGES/PHOTOS SUBMITTED FOR EACH MONTH:\n";
    $previousMonth = null;
    foreach ($submissionsBySubmitterData as $row) {
        $formattedMonth = $months[date('m', strtotime($row['month']))] . ' ' . date('Y', strtotime($row['month']));
        if ($row['month'] !== $previousMonth) {
            // Add an empty line if the month changes
            if ($previousMonth !== null) {
                $textContent .= "\n";
            }
            $textContent .= $formattedMonth . ":\n";
            $previousMonth = $row['month'];
        }
        $textContent .= "    " . $row['submitter'] . " = " . $row['submissions'] . "\n";
    }

    // Construct text content for completed requests
    $textContent .= "\nARCHIVERS:\n";
    $textContent .= "\nCOMPLETED REQUESTS FOR EACH MONTH:\n";
    $previousMonth = null;
    foreach ($combinedRequestsData as $row) {
        $formattedMonth = $months[date('m', strtotime($row['month']))] . ' ' . date('Y', strtotime($row['month']));
        if ($row['month'] !== $previousMonth) {
            // Add an empty line if the month changes
            if ($previousMonth !== null) {
                $textContent .= "\n";
            }
            $textContent .= $formattedMonth . ":\n";
            $previousMonth = $row['month'];
        }
        $textContent .= "    " . $row['name'] . " = " . $row['total_requests'] . "\n";
    }

    // Set headers for file download
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="performance_data.txt"');

    // Output text content
    echo $textContent;
    exit();
}


?>


<script>
    const lineChartData = {
        pgData: <?php echo json_encode($pgData); ?>,
        vgData: <?php echo json_encode($vgData); ?>
    };

    const pieChartData = {
        labels: <?php echo json_encode($labelsPie); ?>,
        series: <?php echo json_encode($seriesPie); ?>
    };

    var chartData = {
        labels: <?php echo json_encode($labelsPie); ?>,
        series: [<?php echo json_encode($seriesPie); ?>]
    };

    // Declare the variables once
    let lineChartDataRequest = {};
    let pieChartDataRequest = {};
    let chartDataRequest = {};

    // Check the designation in PHP and conditionally assign values
    <?php if ($_SESSION['designation'] == 'archive' || $_SESSION['designation'] == 'boss') : ?>
        lineChartDataRequest = {
            requesterData: <?php echo json_encode($requesterData); ?>
        };

        pieChartDataRequest = {
            labels: <?php echo json_encode($labelsPieRequest); ?>,
            series: <?php echo json_encode($seriesPieRequest); ?>
        };

        chartDataRequest = {
            labels: <?php echo json_encode($labelsPieRequest); ?>,
            series: [<?php echo json_encode($seriesPieRequest); ?>]
        };
    <?php else : ?>
        // Provide default values or handle other designations if needed
        lineChartDataRequest = {
            requesterData: []
        };

        pieChartDataRequest = {
            labels: [],
            series: []
        };

        chartDataRequest = {
            labels: [],
            series: [
                []
            ]
        };
    <?php endif; ?>
</script>


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
        <!-- start sidebar section -->

        <?php include('includes/sidebar.php'); ?>

        <div class="main-content flex min-h-screen flex-col">
            <!-- start header section -->

            <?php include('includes/header.php'); ?>



            <!-- end header section -->

            <div class="dvanimation animate__animated p-6" :class="[$store.app.animation]">
                <!-- start main content section -->
                <div x-data="sales">
                    <ul class="flex space-x-2 rtl:space-x-reverse">
                        <li>
                            <a href="index.php" class="text-primary hover:underline">Dashboard</a>
                        </li>
                        <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                            <span>Performances / Total Footages/Photos</span>
                        </li>
                    </ul>

                    <div class="pt-5">
                        <div class="mb-6 grid gap-6 xl:grid-cols-3">
                            <div class="panel h-full xl:col-span-2">
                                <p class="text-lg dark:text-white-light/90">Total Footages/Photos Monthly </p>
                                <div class="relative overflow-hidden">
                                    <div x-ref="revenueChart" class="rounded-lg bg-white dark:bg-black">
                                        <!-- loader -->
                                        <div class="grid min-h-[325px] place-content-center bg-white-light/30 dark:bg-dark dark:bg-opacity-[0.08]">
                                            <span class="inline-flex h-5 w-5 animate-spin rounded-full border-2 border-black !border-l-transparent dark:border-white"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="panel h-full">
                                <div class="mb-5 flex items-center">
                                    <?php
                                    if ($_SESSION['designation'] === 'vg' || $_SESSION['designation'] === 'pg' || $_SESSION['designation'] === 'boss') {
                                    ?>
                                        <h5 class="text-lg font-semibold dark:text-white-light">Videographers / Photographers</h5>
                                    <?php } elseif ($_SESSION['designation'] === 'archive') { ?>
                                        <h5 class="text-lg font-semibold dark:text-white-light">Archives</h5>
                                    <?php } ?>
                                </div>
                                <div class="overflow-hidden">
                                    <div x-ref="salesByCategory" class="rounded-lg bg-white dark:bg-black">
                                        <!-- loader -->
                                        <div class="grid min-h-[353px] place-content-center bg-white-light/30 dark:bg-dark dark:bg-opacity-[0.08]">
                                            <span class="inline-flex h-5 w-5 animate-spin rounded-full border-2 border-black !border-l-transparent dark:border-white"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <br>
                        <?php
                        if ($_SESSION['designation'] === 'archive' || $_SESSION['designation'] == 'boss') {
                        ?>
                            <div class="mb-6 grid gap-6 xl:grid-cols-3">
                                <div class="panel h-full xl:col-span-2">
                                    <?php
                                    if ($_SESSION['designation'] === 'archive') {
                                    ?>
                                        <p class="text-lg dark:text-white-light/90">Total Footage Request Monthly </p>
                                    <?php } elseif ($_SESSION['designation'] === 'boss') { ?>
                                        <p class="text-lg dark:text-white-light/90">Total Request Completed Monthly </p>
                                    <?php } ?>
                                    <div class="relative overflow-hidden">
                                        <div x-ref="requestFootageLine" class="rounded-lg bg-white dark:bg-black">
                                            <!-- loader -->
                                            <div class="grid min-h-[325px] place-content-center bg-white-light/30 dark:bg-dark dark:bg-opacity-[0.08]">
                                                <span class="inline-flex h-5 w-5 animate-spin rounded-full border-2 border-black !border-l-transparent dark:border-white"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="panel h-full">
                                    <div class="mb-5 flex items-center">
                                        <h5 class="text-lg font-semibold dark:text-white-light">Archives</h5>
                                    </div>
                                    <div class="overflow-hidden">
                                        <div x-ref="requestFootagePie" class="rounded-lg bg-white dark:bg-black">
                                            <!-- loader -->
                                            <div class="grid min-h-[353px] place-content-center bg-white-light/30 dark:bg-dark dark:bg-opacity-[0.08]">
                                                <span class="inline-flex h-5 w-5 animate-spin rounded-full border-2 border-black !border-l-transparent dark:border-white"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                    <?php
                    if ($_SESSION['designation'] == 'boss') {
                    ?>
                        <form id="downloadForm" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
                            <button type="submit" class="download-btn"><i class="fas fa-download"></i> Download Performance</button>
                        </form>
                    <?php } ?>
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
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('downloadForm').addEventListener('submit', function(e) {
                e.preventDefault(); // Prevent the default form submission

                var form = this;

                // Create a new FormData object
                var formData = new FormData(form);

                // Create a new XMLHttpRequest object
                var xhr = new XMLHttpRequest();

                // Define the callback function when the request is completed
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        // Create a blob from the response
                        var blob = new Blob([xhr.response], {
                            type: 'text/plain'
                        });

                        // Create a link element to trigger the download
                        var link = document.createElement('a');
                        link.href = window.URL.createObjectURL(blob);
                        link.download = 'performance_data.txt';

                        // Append the link to the document body and click it
                        document.body.appendChild(link);
                        link.click();

                        // Remove the link from the document body
                        document.body.removeChild(link);
                    }
                };

                // Open a POST request to the form action URL
                xhr.open('POST', form.action);

                // Set the response type to blob
                xhr.responseType = 'blob';

                // Send the form data
                xhr.send(formData);
            });
        });
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

            // content section
            Alpine.data('sales', () => ({
                init() {
                    isDark = this.$store.app.theme === 'dark' || this.$store.app.isDarkMode ? true : false;
                    isRtl = this.$store.app.rtlClass === 'rtl' ? true : false;

                    const revenueChart = null;
                    const salesByCategory = null;
                    const requestFootagePie = null;
                    const requestFootageLine = null;
                    const dailySales = null;
                    const totalOrders = null;

                    // revenue
                    setTimeout(() => {
                        this.revenueChart = new ApexCharts(this.$refs.revenueChart, this.revenueChartOptions);
                        this.$refs.revenueChart.innerHTML = '';
                        this.revenueChart.render();

                        // sales by category
                        this.salesByCategory = new ApexCharts(this.$refs.salesByCategory, this.salesByCategoryOptions);
                        this.$refs.salesByCategory.innerHTML = '';
                        this.salesByCategory.render();

                        this.requestFootageLine = new ApexCharts(this.$refs.requestFootageLine, this.requestFootageLineOptions);
                        this.$refs.requestFootageLine.innerHTML = '';
                        this.requestFootageLine.render();

                        // sales by category
                        this.requestFootagePie = new ApexCharts(this.$refs.requestFootagePie, this.requestFootagePieOptions);
                        this.$refs.requestFootagePie.innerHTML = '';
                        this.requestFootagePie.render();

                        // daily sales
                        this.dailySales = new ApexCharts(this.$refs.dailySales, this.dailySalesOptions);
                        this.$refs.dailySales.innerHTML = '';
                        this.dailySales.render();

                        // total orders
                        this.totalOrders = new ApexCharts(this.$refs.totalOrders, this.totalOrdersOptions);
                        this.$refs.totalOrders.innerHTML = '';
                        this.totalOrders.render();
                    }, 300);

                    this.$watch('$store.app.theme', () => {
                        isDark = this.$store.app.theme === 'dark' || this.$store.app.isDarkMode ? true : false;

                        this.revenueChart.updateOptions(this.revenueChartOptions);
                        this.salesByCategory.updateOptions(this.salesByCategoryOptions);
                        this.requestFootageLine.updateOptions(this.requestFootageLineOptions);
                        this.requestFootagePie.updateOptions(this.requestFootagePieOptions);
                        this.dailySales.updateOptions(this.dailySalesOptions);
                        this.totalOrders.updateOptions(this.totalOrdersOptions);
                    });

                    this.$watch('$store.app.rtlClass', () => {
                        isRtl = this.$store.app.rtlClass === 'rtl' ? true : false;
                        this.revenueChart.updateOptions(this.revenueChartOptions);
                    });
                },

                // revenue
                get revenueChartOptions() {
                    const findHighestIndex = (dataArray) => {
                        let maxIndex = 0;
                        for (let i = 1; i < dataArray.length; i++) {
                            if (dataArray[i] > dataArray[maxIndex]) {
                                maxIndex = i;
                            }
                        }
                        return maxIndex;
                    };

                    const highestIndexPhotos = findHighestIndex(lineChartData.pgData);
                    const highestIndexFootages = findHighestIndex(lineChartData.vgData);

                    // Calculate the highest value in the dataset
                    const highestValue = Math.max(...lineChartData.pgData, ...lineChartData.vgData);
                    // Calculate the max value for the y-axis as the next multiple of 50 above the highest value
                    const yAxisMax = Math.ceil(highestValue / 50) * 50;

                    return {
                        series: [{
                                name: 'Photos',
                                data: lineChartData.pgData,
                            },
                            {
                                name: 'Footages',
                                data: lineChartData.vgData,
                            },
                        ],
                        chart: {
                            height: 325,
                            type: 'area',
                            fontFamily: 'Nunito, sans-serif',
                            zoom: {
                                enabled: false,
                            },
                            toolbar: {
                                show: false,
                            },
                        },
                        dataLabels: {
                            enabled: false,
                        },
                        stroke: {
                            show: true,
                            curve: 'smooth',
                            width: 2,
                            lineCap: 'square',
                        },
                        dropShadow: {
                            enabled: true,
                            opacity: 0.2,
                            blur: 10,
                            left: -7,
                            top: 22,
                        },
                        colors: isDark ? ['#2196f3', '#e7515a'] : ['#1b55e2', '#e7515a'],
                        markers: {
                            discrete: [{
                                    seriesIndex: 0,
                                    dataPointIndex: highestIndexPhotos, // Use the index of the highest value in the 'Photos' series
                                    fillColor: '#1b55e2',
                                    strokeColor: 'transparent',
                                    size: 7,
                                },
                                {
                                    seriesIndex: 1,
                                    dataPointIndex: highestIndexFootages, // Use the index of the highest value in the 'Footages' series
                                    fillColor: '#e7515a',
                                    strokeColor: 'transparent',
                                    size: 7,
                                },
                            ],
                        },
                        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                        xaxis: {
                            axisBorder: {
                                show: false,
                            },
                            axisTicks: {
                                show: false,
                            },
                            crosshairs: {
                                show: true,
                            },
                            labels: {
                                offsetX: isRtl ? 2 : 0,
                                offsetY: 5,
                                style: {
                                    fontSize: '12px',
                                    cssClass: 'apexcharts-xaxis-title',
                                },
                            },
                        },
                        yaxis: {
                            min: 0,
                            max: yAxisMax,
                            tickAmount: yAxisMax / 50,
                            labels: {
                                formatter: (value) => {
                                    return value;
                                },
                                offsetX: isRtl ? -30 : -10,
                                offsetY: 0,
                                style: {
                                    fontSize: '12px',
                                    cssClass: 'apexcharts-yaxis-title',
                                },
                            },
                            opposite: isRtl ? true : false,
                        },
                        grid: {
                            borderColor: isDark ? '#191e3a' : '#e0e6ed',
                            strokeDashArray: 5,
                            xaxis: {
                                lines: {
                                    show: true,
                                },
                            },
                            yaxis: {
                                lines: {
                                    show: false,
                                },
                            },
                            padding: {
                                top: 0,
                                right: 0,
                                bottom: 0,
                                left: 0,
                            },
                        },
                        legend: {
                            position: 'top',
                            horizontalAlign: 'right',
                            fontSize: '16px',
                            markers: {
                                width: 10,
                                height: 10,
                                offsetX: -2,
                            },
                            itemMargin: {
                                horizontal: 10,
                                vertical: 5,
                            },
                        },
                        tooltip: {
                            marker: {
                                show: true,
                            },
                            x: {
                                show: false,
                            },
                        },
                        fill: {
                            type: 'gradient',
                            gradient: {
                                shadeIntensity: 1,
                                inverseColors: !1,
                                opacityFrom: isDark ? 0.19 : 0.28,
                                opacityTo: 0.05,
                                stops: isDark ? [100, 100] : [45, 100],
                            },
                        },
                    };
                },



                // sales by category
                get salesByCategoryOptions() {
                    return {
                        series: pieChartData.series,
                        chart: {
                            type: 'donut',
                            height: 460,
                            fontFamily: 'Nunito, sans-serif',
                        },
                        dataLabels: {
                            enabled: false,
                        },
                        stroke: {
                            show: true,
                            width: 25,
                            colors: isDark ? ['#0e1726', '#0e1726', '#0e1726', '#0e1726'] : ['#fff', '#fff', '#fff'],
                        },
                        colors: isDark ? ['#5c1ac3', '#e2a03f', '#e7515a', '#e2a03f', '#2196F3'] : ['#5c1ac3', '#e2a03f', '#e7515a', '#e2a03f', '#2196F3'],
                        legend: {
                            position: 'bottom',
                            horizontalAlign: 'center',
                            fontSize: '14px',
                            markers: {
                                width: 10,
                                height: 10,
                                offsetX: -2,
                            },
                            height: 50,
                            offsetY: 20,
                        },
                        plotOptions: {
                            pie: {
                                donut: {
                                    size: '65%',
                                    background: 'transparent',
                                    labels: {
                                        show: true,
                                        name: {
                                            show: true,
                                            fontSize: '29px',
                                            offsetY: -10,
                                        },
                                        value: {
                                            show: true,
                                            fontSize: '26px',
                                            color: isDark ? '#bfc9d4' : undefined,
                                            offsetY: 16,
                                            formatter: (val) => {
                                                return val;
                                            },
                                        },
                                        total: {
                                            show: true,
                                            label: 'Total',
                                            color: '#888ea8',
                                            fontSize: '29px',
                                            formatter: (w) => {
                                                return w.globals.seriesTotals.reduce(function(a, b) {
                                                    return a + b;
                                                }, 0);
                                            },
                                        },
                                    },
                                },
                            },
                        },
                        labels: chartData.labels,
                        states: {
                            hover: {
                                filter: {
                                    type: 'none',
                                    value: 0.15,
                                },
                            },
                            active: {
                                filter: {
                                    type: 'none',
                                    value: 0.15,
                                },
                            },
                        },
                    };
                },

                get requestFootageLineOptions() {
                    const findHighestIndex = (dataArray) => {
                        let maxIndex = 0;
                        for (let i = 1; i < dataArray.length; i++) {
                            if (dataArray[i] > dataArray[maxIndex]) {
                                maxIndex = i;
                            }
                        }
                        return maxIndex;
                    };

                    const highestIndexFootages = findHighestIndex(lineChartDataRequest.requesterData);

                    // Calculate the highest value in the dataset
                    const highestValue = Math.max(...lineChartDataRequest.requesterData);
                    // Calculate the max value for the y-axis as the next multiple of 50 above the highest value
                    const yAxisMax = Math.ceil(highestValue / 50) * 50;

                    return {
                        series: [{
                            name: 'Requests',
                            data: lineChartDataRequest.requesterData,
                        }, ],
                        chart: {
                            height: 325,
                            type: 'area',
                            fontFamily: 'Nunito, sans-serif',
                            zoom: {
                                enabled: false,
                            },
                            toolbar: {
                                show: false,
                            },
                        },
                        dataLabels: {
                            enabled: false,
                        },
                        stroke: {
                            show: true,
                            curve: 'smooth',
                            width: 2,
                            lineCap: 'square',
                        },
                        dropShadow: {
                            enabled: true,
                            opacity: 0.2,
                            blur: 10,
                            left: -7,
                            top: 22,
                        },
                        colors: isDark ? ['#2196f3', '#e7515a'] : ['#1b55e2', '#e7515a'],
                        markers: {
                            discrete: [{
                                seriesIndex: 1,
                                dataPointIndex: highestIndexFootages, // Use the index of the highest value in the 'Footages' series
                                fillColor: '#e7515a',
                                strokeColor: 'transparent',
                                size: 7,
                            }, ],
                        },
                        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                        xaxis: {
                            axisBorder: {
                                show: false,
                            },
                            axisTicks: {
                                show: false,
                            },
                            crosshairs: {
                                show: true,
                            },
                            labels: {
                                offsetX: isRtl ? 2 : 0,
                                offsetY: 5,
                                style: {
                                    fontSize: '12px',
                                    cssClass: 'apexcharts-xaxis-title',
                                },
                            },
                        },
                        yaxis: {
                            min: 0,
                            max: yAxisMax,
                            tickAmount: yAxisMax / 50,
                            labels: {
                                formatter: (value) => {
                                    return value;
                                },
                                offsetX: isRtl ? -30 : -10,
                                offsetY: 0,
                                style: {
                                    fontSize: '12px',
                                    cssClass: 'apexcharts-yaxis-title',
                                },
                            },
                            opposite: isRtl ? true : false,
                        },
                        grid: {
                            borderColor: isDark ? '#191e3a' : '#e0e6ed',
                            strokeDashArray: 5,
                            xaxis: {
                                lines: {
                                    show: true,
                                },
                            },
                            yaxis: {
                                lines: {
                                    show: false,
                                },
                            },
                            padding: {
                                top: 0,
                                right: 0,
                                bottom: 0,
                                left: 0,
                            },
                        },
                        legend: {
                            position: 'top',
                            horizontalAlign: 'right',
                            fontSize: '16px',
                            markers: {
                                width: 10,
                                height: 10,
                                offsetX: -2,
                            },
                            itemMargin: {
                                horizontal: 10,
                                vertical: 5,
                            },
                        },
                        tooltip: {
                            marker: {
                                show: true,
                            },
                            x: {
                                show: false,
                            },
                        },
                        fill: {
                            type: 'gradient',
                            gradient: {
                                shadeIntensity: 1,
                                inverseColors: !1,
                                opacityFrom: isDark ? 0.19 : 0.28,
                                opacityTo: 0.05,
                                stops: isDark ? [100, 100] : [45, 100],
                            },
                        },
                    };
                },



                // Request Footage Pie Chart
                get requestFootagePieOptions() {
                    return {
                        series: pieChartDataRequest.series,
                        chart: {
                            type: 'donut',
                            height: 460,
                            fontFamily: 'Nunito, sans-serif',
                        },
                        dataLabels: {
                            enabled: false,
                        },
                        stroke: {
                            show: true,
                            width: 25,
                            colors: isDark ? ['#0e1726', '#0e1726', '#0e1726', '#0e1726'] : ['#fff', '#fff', '#fff'],
                        },
                        colors: isDark ? ['#5c1ac3', '#e2a03f', '#e7515a', '#e2a03f', '#2196F3'] : ['#5c1ac3', '#e2a03f', '#e7515a', '#e2a03f', '#2196F3'],
                        legend: {
                            position: 'bottom',
                            horizontalAlign: 'center',
                            fontSize: '14px',
                            markers: {
                                width: 10,
                                height: 10,
                                offsetX: -2,
                            },
                            height: 50,
                            offsetY: 20,
                        },
                        plotOptions: {
                            pie: {
                                donut: {
                                    size: '65%',
                                    background: 'transparent',
                                    labels: {
                                        show: true,
                                        name: {
                                            show: true,
                                            fontSize: '29px',
                                            offsetY: -10,
                                        },
                                        value: {
                                            show: true,
                                            fontSize: '26px',
                                            color: isDark ? '#bfc9d4' : undefined,
                                            offsetY: 16,
                                            formatter: (val) => {
                                                return val;
                                            },
                                        },
                                        total: {
                                            show: true,
                                            label: 'Total',
                                            color: '#888ea8',
                                            fontSize: '29px',
                                            formatter: (w) => {
                                                return w.globals.seriesTotals.reduce(function(a, b) {
                                                    return a + b;
                                                }, 0);
                                            },
                                        },
                                    },
                                },
                            },
                        },
                        labels: pieChartDataRequest.labels,
                        states: {
                            hover: {
                                filter: {
                                    type: 'none',
                                    value: 0.15,
                                },
                            },
                            active: {
                                filter: {
                                    type: 'none',
                                    value: 0.15,
                                },
                            },
                        },
                    };
                }
            }));
        });
    </script>
</body>

</html>