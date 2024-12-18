<?php
session_start();
if (!isset($_SESSION['stafflogin'])) {
    header('Location: https://localhost/vg/login/login.php');
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/config.php');
$pdo_login = pdo_connect_mysql();

$name = $_SESSION['name'];

if ($_SESSION['designation'] === 'archive') {
    // Prepare the SQL statement with a WHERE clause
    $stmt = $pdo_login->prepare("SELECT * FROM requestFootage");

    // Execute the statement
    $stmt->execute();

    // Fetch all matching records
    $requestFootages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Prepare the SQL statement with a WHERE clause
    $stmt = $pdo_login->prepare("SELECT * FROM requestFootage WHERE requesterName = :requesterName");

    // Bind the session variable to the prepared statement
    $stmt->bindParam(':requesterName', $name, PDO::PARAM_STR);

    // Execute the statement
    $stmt->execute();

    // Fetch all matching records
    $requestFootages = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = ''; //email
    $mail->Password = '';
    $mail->SMTPSecure = 'ssl';
    $mail->Port = 465;
    $mail->setFrom(''); //email
    $mail->addAddress('');
    $mail->isHTML(true);

    if (isset($_POST['request'])) {
        $titleFootage = $_POST['titleFootage'];
        $remarksFootage = $_POST['remarksFootage'];
        $requesterName = $_SESSION['name'];
        $requestStatus = 0;
        $collectDate = $_POST['collectDate'];
        $adminRemarks = '';
        $currentDate = date('Y-m-d');

        $mail->Subject = 'New Footage Request';

        $stmt = $pdo_login->prepare("INSERT INTO requestfootage (titleFootage, remarksFootage, requesterName, requestStatus, collectDate, adminRemarks, submitDate) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$titleFootage, $remarksFootage, $requesterName, $requestStatus, $collectDate, $adminRemarks, $currentDate]);

        // Redirect to the same page to refresh
        header("Location: {$_SERVER['PHP_SELF']}");
        $mail->Body = "Dear Archive Team, <br><br>
        " . htmlspecialchars($_SESSION['name'], ENT_QUOTES, 'UTF-8') . " has submitted a new footage request. Please login into <a href='https://localhost/vg/dashboard/index.php'>MediaNest</a> for more details.<br><br>
        Thank you.<br><br>
        Regards,<br>
        SMG MediaNest.";
        $mail->send();
        exit();
    } elseif (isset($_POST['accept'])) {
        $requestId = $_POST['requestIdForm'];
        $status = 1;
        $adminRemarks = $_POST['adminRemarks'];
        $adminInchargeRequest = $_SESSION['name'];

        $mail->Subject = 'Footage Request Completed';

        $sql = "UPDATE requestfootage SET requestStatus = ?, adminRemarks = ?, adminInchargeRequest = ? WHERE requestId = ?";
        $stmt = $pdo_login->prepare($sql);
        $stmt->execute([$status, $adminRemarks, $adminInchargeRequest, $requestId]);
        $mail->Body = "Dear requester,<br><br>" .  htmlspecialchars($_SESSION['name'], ENT_QUOTES, 'UTF-8') . " has completed your request. Please login into <a href='https://localhost/vg/dashboard/index.php'>MediaNest</a> for more details.<br><br>
    
        Thank you.<br><br>
        
        Regards,<br>
        SMG MediaNest.";
        $mail->send();
        header("Location: {$_SERVER['PHP_SELF']}");
        exit();
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
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@simonwep/pickr/dist/themes/nano.min.css" /> <!-- 'nano' theme -->

    <style>
        /* Styling for the popup */
        .popup {
            display: none;
            position: fixed;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            z-index: 1000;
        }

        .popup-content {
            background-color: #fefefe;
            margin: 20% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 600px;
            text-align: center;
        }
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

            <div class="dvanimation animate__animated p-6" :class="[$store.app.animation]">
                <!-- start main content section -->
                <div class="flex flex-col items-center justify-center space-y-3">
                    <?php if ($_SESSION['designation'] != "archive") { ?>
                        <div id="initialForm" class="panel w-full sm:w-2/3 lg:w-1/2 xl:w-1/3 p-6 shadow-lg rounded-lg">
                            <div class="mb-5 flex items-center justify-between">
                                <h5 class="text-lg font-semibold dark:text-white-light">Request Footage</h5>
                            </div>
                            <div id="form-container">
                                <div class="mb-5">
                                    <form method="post" action="request-footage.php" class="space-y-5">
                                        <div class="flex flex-col sm:flex-row">
                                            <label for="titleFootage" class="mb-0 rtl:ml-2 sm:w-1/4 sm:ltr:mr-2">Title</label>
                                            <input id="titleFootage" name="titleFootage" type="text" placeholder="Enter Title" class="form-input flex-1" required />
                                        </div>
                                        <div class="flex flex-col sm:flex-row">
                                            <label for="remarksFootage" class="mb-0 rtl:ml-2 sm:w-1/4 sm:ltr:mr-2">Remarks</label>
                                            <textarea id="remarksFootage" name="remarksFootage" type="text" placeholder="Enter Remarks" class="form-input flex-1"></textarea>
                                        </div>
                                        <div class="flex flex-col sm:flex-row">
                                            <label for="collectDate" class="mb-0 rtl:ml-2 sm:w-1/4 sm:ltr:mr-2">Collection Date</label>
                                            <input id="collectDate" name="collectDate" type="date" class="form-input flex-1" required>
                                        </div>
                                        <div class="flex flex-col sm:flex-row" id="buttonContainerSubmit">
                                            <button type="submit" name="request" class="btn btn-success mb-0 rtl:ml-2 sm:w-1/6 sm:ltr:mr-2">Submit</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                    <div class="panel w-full lg:w-2/3 shadow-lg rounded-lg">
                        <div class="table-responsive" id="submission-footage">
                            <table id="footageTable">
                                <thead>
                                    <tr>
                                        <th class="ltr:rounded-l-md rtl:rounded-r-md">No.</th>
                                        <th>Title</th>
                                        <?php
                                        if ($_SESSION['designation'] === 'archive') {
                                        ?>
                                            <th>Requester</th>
                                        <?php
                                        }
                                        ?>
                                        <th>Collection Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $count = +1;
                                    foreach ($requestFootages as $row) :
                                    ?>
                                        <tr onclick='showRequestForm(<?php echo json_encode($row, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' class="group text-white-dark hover:text-black dark:hover:text-white-light/90">
                                            <td class="min-w-[150px] text-black dark:text-white">
                                                <div class="flex items-center">
                                                    <span class="whitespace-nowrap"><?php echo $count; ?></span>
                                                </div>
                                            </td>
                                            <td class="text-primary"><?php echo htmlspecialchars($row['titleFootage']); ?></td>
                                            <?php
                                            if ($_SESSION['designation'] === 'archive') {
                                            ?>
                                                <td class="text-primary"><?php echo htmlspecialchars($row['requesterName']); ?></td>
                                            <?php
                                            }
                                            ?>
                                            <td><a><?php echo htmlspecialchars($row['collectDate']); ?></a></td>
                                            <td class="text-primary">
                                                <?php
                                                if ($row["requestStatus"] == 0) {
                                                    echo '<span class="badge bg-warning shadow-md dark:group-hover:bg-transparent">In Process</span>';
                                                } elseif ($row["requestStatus"] == 1) {
                                                    echo '<span class="badge bg-success shadow-md dark:group-hover:bg-transparent">Completed</span>';
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php
                                        $count++;
                                    endforeach;
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        <div id="requestform-container" style="display: none">
                            <div class="mb-5">
                                <button id="back-button" onclick="requestBackBtn()"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left" viewBox="0 0 16 16">
                                        <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8" />
                                    </svg></button>
                                <form method="post" action="request-footage.php" class="space-y-5">
                                    <input id="requestIdForm" name="requestIdForm" type="hidden" placeholder="ID" class="form-input flex-1" required />
                                    <input id="requestStatusForm" name="requestStatusForm" type="hidden" placeholder="status" class="form-input flex-1" required />
                                    <div class="flex flex-col sm:flex-row">
                                        <label for="requesterNameForm" class="mb-0 rtl:ml-2 sm:w-1/4 sm:ltr:mr-2">Requested By</label>
                                        <input id="requesterNameForm" name="requesterNameForm" type="text" placeholder="Requested By" class="form-input flex-1" required readonly />
                                    </div>
                                    <div class="flex flex-col sm:flex-row">
                                        <label for="titleFootageForm" class="mb-0 rtl:ml-2 sm:w-1/4 sm:ltr:mr-2">Title</label>
                                        <input id="titleFootageForm" name="titleFootageForm" type="text" placeholder="Enter Title" class="form-input flex-1" required readonly />
                                    </div>
                                    <div class="flex flex-col sm:flex-row">
                                        <label for="collectDateForm" class="mb-0 rtl:ml-2 sm:w-1/4 sm:ltr:mr-2">Collection Date</label>
                                        <input id="collectDateForm" name="collectDateForm" type="text" placeholder="Enter Date" class="form-input flex-1" required readonly />
                                    </div>
                                    <div class="flex flex-col sm:flex-row">
                                        <label for="remarksFootageForm" class="mb-0 rtl:ml-2 sm:w-1/4 sm:ltr:mr-2">Remarks</label>
                                        <textarea id="remarksFootageForm" name="remarksFootageForm" type="text" placeholder="Enter Remarks" class="form-input flex-1" required readonly></textarea>
                                    </div>
                                    <div id="divAdminRemarks" class="flex flex-col sm:flex-row">
                                        <label for="adminRemarks" class="mb-0 rtl:ml-2 sm:w-1/4 sm:ltr:mr-2">Admin Remarks</label>
                                        <textarea id="adminRemarks" name="adminRemarks" type="text" placeholder="Enter Remarks" class="form-input flex-1" required readonly></textarea>
                                    </div>
                                    <?php
                                    if ($_SESSION['designation'] === 'archive') {
                                    ?>
                                        <div class="flex sm:flex-row space-x-4" id="buttonContainer">
                                            <button type="button" class="btn btn-success mb-0 rtl:ml-2 sm:w-1/6 sm:ltr:mr-2" onclick="showRejectionPopup()">Submit</button>
                                        </div>
                                    <?php
                                    }
                                    ?>
                                    <div id="rejectionPopup" class="popup">
                                        <div class="popup-content dark:bg-[#060818] rounded-lg shadow-lg p-6 mx-8 relative">
                                            <button type="button" onclick="hideRejectionPopup()" class="absolute top-0 right-0 m-2 bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                            <h2 class="text-xl font-bold mb-4">Provide Remarks</h2>
                                            <textarea id="adminRemarks" name="adminRemarks" type="text" placeholder="Enter Remarks" class="form-input mb-4 px-4 py-2 border rounded-lg w-full" required></textarea>
                                            <button type="submit" name="accept" onclick="confirmReject()" class="btn btn-success text-white font-bold py-2 px-4 rounded">Confirm</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
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
    <script src="https://cdn.jsdelivr.net/npm/@simonwep/pickr/dist/pickr.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

    <script>
        function showRejectionPopup() {
            document.getElementById('rejectionPopup').style.display = 'block';
        }

        function hideRejectionPopup() {
            document.getElementById('rejectionPopup').style.display = 'none';
        }

        function confirmReject() {
            // You can perform additional actions here, such as submitting the form
            // For now, let's just hide the popup
            document.getElementById('rejectionPopup').style.display = 'none';
        }

        $(document).ready(function() {
            var dataTable = $('#footageTable').DataTable({
                "lengthMenu": [
                    [10],
                    [10]
                ], // Set the length menu to only display 10 entries
                "paging": true, // Enable pagination
                "searching": true, // Enable searching
                "dom": 'ftip' // Exclude the length menu control
            });
        });

        function showRequestForm(rowData) {
            document.getElementById('requestform-container').style.display = 'block';
            document.getElementById('submission-footage').style.display = 'none';
            document.getElementById('initialForm').style.display = 'none';
            document.getElementById('requesterNameForm').value = rowData.requesterName;
            document.getElementById('titleFootageForm').value = rowData.titleFootage;
            document.getElementById('remarksFootageForm').value = rowData.remarksFootage;
            document.getElementById('collectDateForm').value = rowData.collectDate;
            document.getElementById('adminRemarks').value = rowData.adminRemarks;
            document.getElementById('requestIdForm').value = rowData.requestId;
            document.getElementById('requestStatusForm').value = rowData.requestStatus;

            if (rowData.requestStatus == 1) {
                document.getElementById('buttonContainer').style.display = 'none';
            } else {
                document.getElementById('buttonContainer').style.display = 'block';
                document.getElementById('divAdminRemarks').style.display = 'none';
            }
        }

        function requestBackBtn() {
            document.getElementById('requestform-container').style.display = 'none';
            document.getElementById('submission-footage').style.display = 'block';
            document.getElementById('initialForm').style.display = 'block';
        }
    </script>
</body>

</html>