<?php
session_start();
// Check if the user is not logged in
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    // Redirect to the login page if not logged in
    header('Location: admin.php');
    exit;
}

include('includes/config.php');
require '../vendor/autoload.php'; // Include PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// Connect to the database using PDO
$pdo = pdo_connect_mysql(); // This returns a PDO object

// Fetch all the records from the database
$query = "SELECT * FROM application";
$stmt = $pdo->prepare($query);
$stmt->execute();
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch the application record based on the appID (when uploading or exporting)
if (isset($_POST['appID'])) {
    $appID = $_POST['appID'];
    $query = "SELECT * FROM application WHERE appID = :appID";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':appID', $appID, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle application export (without file upload)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_export'])) {
    // This part will handle export logic when no files are uploaded
    $appID = $_POST['appID'];

    try {
        if (ob_get_level()) {
            ob_end_clean();
        }

        $query = "SELECT * FROM application WHERE appID = :appID";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':appID', $appID, PDO::PARAM_STR);
        $stmt->execute();
        $application = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($application) {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Set column headers
            $sheet->setCellValue('A1', 'App ID');
            $sheet->setCellValue('B1', 'Email');
            $sheet->setCellValue('C1', 'Category Number');
            $sheet->setCellValue('D1', 'Specimen Name');
            $sheet->setCellValue('E1', 'Location');
            $sheet->setCellValue('F1', 'Examination');
            $sheet->setCellValue('G1', 'Condition');
            $sheet->setCellValue('H1', 'Material');
            $sheet->setCellValue('I1', 'Work Method');
            $sheet->setCellValue('J1', 'Inspector Name');
            $sheet->setCellValue('K1', 'Remarks');

            $sheet->setCellValue('A2', $application['appID']);
            $sheet->setCellValue('B2', $application['email']);
            $sheet->setCellValue('C2', $application['catnum']);
            $sheet->setCellValue('D2', $application['specname']);
            $sheet->setCellValue('E2', $application['location']);
            $sheet->setCellValue('F2', $application['examination']);
            $sheet->setCellValue('G2', $application['speccond']);
            $sheet->setCellValue('H2', $application['material']);
            $sheet->setCellValue('I2', $application['workmeth']);
            $sheet->setCellValue('J2', $application['inspectname']);
            $sheet->setCellValue('K2', $application['remarks']);

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="application_' . $appID . '.xlsx"');
            header('Cache-Control: max-age=0');
            header('Expires: 0');
            header('Pragma: public');

            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save('php://output');
            ob_end_flush();
        } else {
            throw new Exception('No application found with the provided appID.');
        }
    } catch (Exception $e) {
        if (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: text/plain');
        echo "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<style>
    .dataTables_length {
        display: none;
    }
</style>

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Specimen Conservation</title>
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
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css" />

    <style>
    </style>
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

            <div class="dvanimation animate__animated" :class="[$store.app.animation]">
                <!-- start main content section -->
                <ul class="flex space-x-2 rtl:space-x-reverse p-6">
                    <li>
                        <a href="index.php" class="text-primary hover:underline">Main</a>
                    </li>
                    <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                        <span>Dashboard</span>
                    </li>
                </ul>
                <div x-data="sales" class="flex justify-center">
                    <br>
                    <div class="panel w-full lg:w-2/3 shadow-lg rounded-lg">
                        <div class="mb-5 flex items-center justify-between">
                            <h5 class="text-lg font-semibold dark:text-white-light">Submissions</h5>
                        </div>

                        <div class="table-responsive" id="submission-records">
                            <!-- Removed Search Input as it's not needed -->

                            <!-- Table displaying application records -->
                            <table id="myTable" class="min-w-full table-auto">
                                <thead>
                                    <tr class="bg-gray-800">
                                        <th class="px-4 py-2 text-left text-sm font-medium text-white">No.</th>
                                        <th class="px-4 py-2 text-left text-sm font-medium text-white">App ID</th>
                                        <th class="px-4 py-2 text-left text-sm font-medium text-white">Category Number</th>
                                        <th class="px-4 py-2 text-left text-sm font-medium text-white">Specimen Name</th>
                                        <th class="px-4 py-2 text-left text-sm font-medium text-white">Inspector Name</th>
                                        <th class="px-4 py-2 text-left text-sm font-medium text-white">Application Status</th>
                                        <th class="px-4 py-2 text-left text-sm font-medium text-white">Export</th>
                                        <th class="px-4 py-2 text-left text-sm font-medium text-white">Print</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Initialize row count
                                    $count = 1;
                                    foreach ($applications as $row) :
                                    ?>
                                        <tr class="group text-white hover:text-black dark:hover:text-white-light/90">
                                            <td class="px-4 py-2 text-sm">
                                                <div class="flex items-center">
                                                    <span class="whitespace-nowrap"><?php echo $count; ?></span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-2 text-sm">
                                                <button onclick="showForm('<?php echo htmlspecialchars($row['appID']); ?>')" class="bg-green-500 text-white p-2 rounded-md"><?php echo htmlspecialchars($row['appID']); ?></button>
                                            </td>
                                            <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($row['catnum']); ?></td>
                                            <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($row['specname']); ?></td>
                                            <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($row['inspectname']); ?></td>
                                            <td class="px-4 py-2 text-sm">
                                                <?php
                                                // Check the status value from the database
                                                if (isset($row['status'])) {
                                                    switch ($row['status']) {
                                                        case 0:
                                                            $status = "Pending";
                                                            break;
                                                        case 1:
                                                            $status = "Accepted";
                                                            break;
                                                        case 2:
                                                            $status = "Rejected";
                                                            break;
                                                        case 3:
                                                            $status = "Completed";
                                                            break;
                                                        default:
                                                            $status = "Unknown"; // Fallback for unexpected values
                                                    }
                                                } else {
                                                    $status = "Pending"; // Default if the status column is missing or null
                                                }
                                                echo htmlspecialchars($status);
                                                ?>
                                            </td>
                                            <td class="px-4 py-2 text-sm">
                                                <form action="dashboard.php" method="post">
                                                    <input type="hidden" name="appID" value="<?php echo htmlspecialchars($row['appID']); ?>">
                                                    <button type="submit" name="submit_export" class="bg-blue-500 text-white p-2 rounded-md">Export</button>
                                                </form>
                                            </td>
                                            <td class="px-4 py-2 text-sm">
                                                <button onclick="printPage('<?php echo htmlspecialchars($row['appID']); ?>')" class="bg-green-500 text-white p-2 rounded-md">Print</button>
                                            </td>
                                        </tr>
                                    <?php
                                        $count++;
                                    endforeach;
                                    ?>
                                </tbody>
                            </table>
                            <br>
                        </div>
                    </div>
                    <br>
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
        <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
        <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

        <script>
            $(document).ready(function() {
                var dataTable = $('#myTable').DataTable({
                    "lengthMenu": [
                        [10],
                        [10]
                    ], // Set length menu to display 10 entries
                    "paging": true, // Enable pagination
                    "searching": true, // Enable searching
                });

                window.backBtn = function() {
                    document.getElementById('form-container').style.display = 'none';
                    document.getElementById('submission-records').style.display = 'block';
                };
            });

            function showForm(appID) {
                const url = `app-page.php?appID=${appID}`;
                window.location.href = url;
            }

            function printPage(appID) {
                const url = `print-page.php?appID=${appID}`;
                window.location.href = url;
            }
        </script>
</body>

</html>