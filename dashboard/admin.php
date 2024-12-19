<?php
session_start();
// Include config file
include('includes/config.php');
require '../vendor/autoload.php'; // Include PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;

// Connect to the database using PDO
$pdo = pdo_connect_mysql(); // This returns a PDO object

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// Remove search logic and directly fetch all application records
$query = "SELECT * FROM application";
$stmt = $pdo->prepare($query);
$stmt->execute();

// Fetch all the records from the database
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch the application record based on the appID or other identifier
// (You can keep this query if it's needed for fetching details based on appID)
$query = "SELECT * FROM application WHERE appID = :appID";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':appID', $appID, PDO::PARAM_INT);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch the appID from POST request (for the specific application to be exported)
// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ensure that appID is set before proceeding
    if (isset($_POST['appID'])) {
        $appID = $_POST['appID'];

        if (isset($_POST['acceptApp'])) {
            // Handle Accept action
            $query = "UPDATE application SET status = 1 WHERE appID = :appID";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':appID', $appID, PDO::PARAM_INT);

            if ($stmt->execute()) {
                echo "Application accepted successfully.";
            } else {
                echo "Error: Unable to accept the application.";
            }
        } elseif (isset($_POST['rejectApp'])) {
            // Handle Reject action
            $query = "UPDATE application SET status = 2 WHERE appID = :appID";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':appID', $appID, PDO::PARAM_INT);

            if ($stmt->execute()) {
                echo "Application rejected successfully.";
            } else {
                echo "Error: Unable to reject the application.";
            }
        } elseif (isset($_POST['export'])) {
            $appID = $_POST['appID'];
            try {
                // Start with clean output buffer
                if (ob_get_level()) {
                    ob_end_clean();
                }

                // Prepare the query to fetch the data for the specific application
                $query = "SELECT * FROM application WHERE appID = :appID";
                $stmt = $pdo->prepare($query);
                $stmt->bindParam(':appID', $appID, PDO::PARAM_STR);  // Using PDO::PARAM_STR as appID is a string
                $stmt->execute();

                // Fetch the record for the selected application
                $application = $stmt->fetch(PDO::FETCH_ASSOC);

                // Check if the application exists
                if ($application) {
                    // Create a new Spreadsheet
                    $spreadsheet = new Spreadsheet();
                    $sheet = $spreadsheet->getActiveSheet();

                    // Set column headers
                    $sheet->setCellValue('A1', 'App ID');
                    $sheet->setCellValue('B1', 'Email');
                    $sheet->setCellValue('C1', 'Category Number');
                    $sheet->setCellValue('D1', 'Specimen Name');
                    $sheet->setCellValue('E1', 'Location');
                    $sheet->setCellValue('F1', 'Examination');
                    $sheet->setCellValue('G1', 'Material');
                    $sheet->setCellValue('H1', 'Work Method');
                    $sheet->setCellValue('I1', 'Inspector Name');
                    $sheet->setCellValue('J1', 'Remarks');
                    $sheet->setCellValue('K1', 'Condition');

                    // Populate the spreadsheet with data from the selected application
                    $sheet->setCellValue('A2', $application['appID']);
                    $sheet->setCellValue('B2', $application['email']);
                    $sheet->setCellValue('C2', $application['catnum']);
                    $sheet->setCellValue('D2', $application['specname']);
                    $sheet->setCellValue('E2', $application['location']);
                    $sheet->setCellValue('F2', $application['examination']);
                    $sheet->setCellValue('G2', $application['material']);
                    $sheet->setCellValue('H2', $application['workmeth']);
                    $sheet->setCellValue('I2', $application['inspectname']);
                    $sheet->setCellValue('J2', $application['remarks']);
                    $sheet->setCellValue('K2', $application['speccond']);

                    // Set proper headers for Excel file download
                    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                    header('Content-Disposition: attachment; filename="application_' . $appID . '.xlsx"');
                    header('Cache-Control: max-age=0');
                    header('Expires: 0');
                    header('Pragma: public');

                    // Save the file to output
                    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
                    $writer->save('php://output');

                    // Clear the output buffer after writing the file
                    ob_end_flush();  // This ensures the script completes, but the website can continue displaying
                } else {
                    // If no application found
                    throw new Exception('No application found with the provided appID.');
                }
            } catch (Exception $e) {
                // Handle errors
                if (ob_get_level()) {
                    ob_end_clean();
                }
                header('Content-Type: text/plain');
                echo "Error: " . $e->getMessage();
            }
        }
    } else {
        echo "Error: Application ID not provided.";
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
                        <span>Admin</span>
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
                                    <tr class="bg-gray-800"> <!-- Updated the background color for a dark header -->
                                        <th class="px-4 py-2 text-left text-sm font-medium text-white">No.</th>
                                        <th class="px-4 py-2 text-left text-sm font-medium text-white">App ID</th>
                                        <th class="px-4 py-2 text-left text-sm font-medium text-white">Category Number</th>
                                        <th class="px-4 py-2 text-left text-sm font-medium text-white">Specimen Name</th>
                                        <th class="px-4 py-2 text-left text-sm font-medium text-white">Inspector Name</th>
                                        <th class="px-4 py-2 text-left text-sm font-medium text-white">Application Status</th>
                                        <th class="px-4 py-2 text-left text-sm font-medium text-white">Export</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Initialize row count
                                    $count = 1;
                                    foreach ($applications as $row) :
                                    ?>
                                        <tr onclick='showForm(<?php echo json_encode($row, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' class="group text-white hover:text-black dark:hover:text-white-light/90">
                                            <td class="px-4 py-2 text-sm">
                                                <div class="flex items-center">
                                                    <span class="whitespace-nowrap"><?php echo $count; ?></span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($row['appID']); ?></td>
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
                                                <form action="admin.php" method="post">
                                                    <input type="hidden" name="appID" value="<?php echo htmlspecialchars($row['appID']); ?>">
                                                    <button type="submit" name="export" class="bg-blue-500 text-white p-2 rounded-md">Export</button>
                                                </form>
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

                        <br>
                        <div id="form-container" style="display: none">
                            <div class="mb-5">
                                <button id="back-button" onclick="backBtn()">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left" viewBox="0 0 16 16">
                                        <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8" />
                                    </svg>
                                </button>
                                <form method="post" action="admin.php" class="space-y-5">
                                    <!-- Hidden inputs to pass data -->
                                    <input type="hidden" id="requestId" name="requestId" value="<?php echo htmlspecialchars($row['appID']); ?>" required />
                                    <input type="hidden" id="status" name="status" value="pending" required /> <!-- Example, adjust as needed -->

                                    <div class="flex flex-col sm:flex-row">
                                        <label for="appID" class="mb-0 rtl:ml-2 sm:w-1/4 sm:ltr:mr-2">Application ID</label>
                                        <input id="appID" name="appID" type="text" value="<?php echo htmlspecialchars($row['appID']); ?>" placeholder="Application ID" class="form-input flex-1" required disabled />
                                    </div>

                                    <!-- Email -->
                                    <div class="flex flex-col sm:flex-row">
                                        <label for="email" class="mb-0 rtl:ml-2 sm:w-1/4 sm:ltr:mr-2">Email</label>
                                        <input id="email" name="email" type="text" value="<?php echo htmlspecialchars($row['email']); ?>" placeholder="Enter Email" class="form-input flex-1" required disabled />
                                    </div>

                                    <!-- Category Number -->
                                    <div class="flex flex-col sm:flex-row">
                                        <label for="catnum" class="mb-0 rtl:ml-2 sm:w-1/4 sm:ltr:mr-2">Category Number</label>
                                        <input id="catnum" name="catnum" type="text" value="<?php echo htmlspecialchars($row['catnum']); ?>" placeholder="Enter Category Number" class="form-input flex-1" required disabled />
                                    </div>

                                    <!-- Specimen Name -->
                                    <div class="flex flex-col sm:flex-row">
                                        <label for="specname" class="mb-0 rtl:ml-2 sm:w-1/4 sm:ltr:mr-2">Specimen Name</label>
                                        <input id="specname" name="specname" type="text" value="<?php echo htmlspecialchars($row['specname']); ?>" placeholder="Enter Specimen Name" class="form-input flex-1" required disabled />
                                    </div>

                                    <!-- Location -->
                                    <div class="flex flex-col sm:flex-row">
                                        <label for="location" class="mb-0 rtl:ml-2 sm:w-1/4 sm:ltr:mr-2">Location</label>
                                        <input id="location" name="location" type="text" value="<?php echo htmlspecialchars($row['location']); ?>" placeholder="Enter Location" class="form-input flex-1" required disabled />
                                    </div>

                                    <!-- Examination -->
                                    <div class="flex flex-col sm:flex-row">
                                        <label for="examination" class="mb-0 rtl:ml-2 sm:w-1/4 sm:ltr:mr-2">Examination</label>
                                        <input id="examination" name="examination" type="text" value="<?php echo htmlspecialchars($row['examination']); ?>" placeholder="Enter Examination" class="form-input flex-1" required disabled />
                                    </div>

                                    <!-- Examination -->
                                    <div class="flex flex-col sm:flex-row">
                                        <label for="speccond" class="mb-0 rtl:ml-2 sm:w-1/4 sm:ltr:mr-2">Specimen Condition</label>
                                        <input id="speccond" name="speccond" type="text" value="<?php echo htmlspecialchars($row['speccond']); ?>" placeholder="Enter Condition" class="form-input flex-1" required disabled />
                                    </div>

                                    <!-- Material -->
                                    <div class="flex flex-col sm:flex-row">
                                        <label for="material" class="mb-0 rtl:ml-2 sm:w-1/4 sm:ltr:mr-2">Material</label>
                                        <input id="material" name="material" type="text" value="<?php echo htmlspecialchars($row['material']); ?>" placeholder="Enter Material" class="form-input flex-1" required disabled />
                                    </div>

                                    <!-- Work Method -->
                                    <div class="flex flex-col sm:flex-row">
                                        <label for="workmeth" class="mb-0 rtl:ml-2 sm:w-1/4 sm:ltr:mr-2">Work Method</label>
                                        <textarea id="workmeth" name="workmeth" placeholder="Enter Work Method" class="form-input flex-1" required disabled><?php echo htmlspecialchars($row['workmeth']); ?></textarea>
                                    </div>

                                    <!-- Inspector Name -->
                                    <div class="flex flex-col sm:flex-row">
                                        <label for="inspectname" class="mb-0 rtl:ml-2 sm:w-1/4 sm:ltr:mr-2">Inspector Name</label>
                                        <input id="inspectname" name="inspectname" type="text" value="<?php echo htmlspecialchars($row['inspectname']); ?>" placeholder="Enter Inspector Name" class="form-input flex-1" required disabled />
                                    </div>

                                    <!-- Remarks -->
                                    <div class="flex flex-col sm:flex-row">
                                        <label for="remarks" class="mb-0 rtl:ml-2 sm:w-1/4 sm:ltr:mr-2">Remarks</label>
                                        <textarea id="remarks" name="remarks" placeholder="Enter Remarks" class="form-input flex-1" required disabled><?php echo htmlspecialchars($row['remarks']); ?></textarea>
                                    </div>
                                    <div class="flex space-x-4">
                                        <!-- Accept Button -->
                                        <button id="accept-btn" type="submit" name="acceptApp" value="accept" class="btn btn-success text-white bg-green-500 hover:bg-green-600 p-2 rounded-md">
                                            Accept
                                        </button>
                                        <!-- Reject Button -->
                                        <button id="reject-btn" type="submit" name="rejectApp" value="reject" class="btn btn-danger text-white bg-red-500 hover:bg-red-600 p-2 rounded-md">
                                            Reject
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <br>
                <!-- end main content section -->
            </div>

            <!-- start footer section -->
            <!-- <div class="mt-auto p-6 pt-0 text-center dark:text-white-dark ltr:sm:text-left rtl:sm:text-right">
                © <span id="footer-year">2024</span>. Sarawak Media Group All rights reserved.
            </div> -->
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
                ], // Set the length menu to only display 10 entries
                "paging": true, // Enable pagination
                "searching": true, // Enable searching
            });
            // $('.dataTables_filter').hide();
        });

        function showForm(rowData) {
            // Make sure the form is displayed
            document.getElementById('form-container').style.display = 'block';
            document.getElementById('submission-records').style.display = 'none';

            // Populate form fields with the data from the clicked row
            document.getElementById('email').value = rowData.email;
            document.getElementById('catnum').value = rowData.catnum;
            document.getElementById('specname').value = rowData.specname;
            document.getElementById('location').value = rowData.location;
            document.getElementById('examination').value = rowData.examination;
            document.getElementById('speccond').value = rowData.speccond;
            document.getElementById('material').value = rowData.material;
            document.getElementById('workmeth').value = rowData.workmeth;
            document.getElementById('inspectname').value = rowData.inspectname;
            document.getElementById('remarks').value = rowData.remarks;
            document.getElementById('appID').value = rowData.appID; // Set the appID

            // Handle the status value
            const status = rowData.status;
            if (status == 0) {
                // Show the "Accept" and "Reject" buttons if status is 0 (Pending)
                document.getElementById('accept-btn').style.display = 'inline-block';
                document.getElementById('reject-btn').style.display = 'inline-block';
            } else {
                // Hide the buttons for other statuses
                document.getElementById('accept-btn').style.display = 'none';
                document.getElementById('reject-btn').style.display = 'none';
            }
        }

        function backBtn() {
            document.getElementById('form-container').style.display = 'none';
            document.getElementById('submission-records').style.display = 'block';
            document.getElementById('selectedTagsContainerForm').innerHTML = ''; // Empty the div
        }
    </script>
</body>

</html>