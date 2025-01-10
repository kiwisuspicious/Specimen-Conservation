<?php
session_start();
// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    // Redirect to the login page if not logged in
    header('Location: admin-login.php');
    exit;
}


include('includes/config.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// Connect to the database using PDO
$pdo = pdo_connect_mysql(); // This returns a PDO object
$appID = isset($_GET['appID']) ? htmlspecialchars($_GET['appID']) : '';

// Fetch the application record based on the appID
if (!empty($appID)) {
    $query = "SELECT * FROM application WHERE appID = :appID";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':appID', $appID, PDO::PARAM_STR); // Bind as string
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);


    if ($row) {
        // Extract fields from the application row
        $email = $row['email'];
        $catnum = $row['catnum'];
        $specname = $row['specname'];
        $location = $row['location'];
        $examination = $row['examination'];
        $speccond = $row['speccond'];
        $material = $row['material'];
        $workmeth = $row['workmeth'];
        $inspectname = $row['inspectname'];
        $remarks = $row['remarks'];
        $status = $row['status'];
    } else {
        die("Error: Application ID not found.");
    }
} else {
    die("Error: Missing Application ID.");
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get appID from the hidden input field
    $appID = filter_var($_POST['requestId']);

    // Get the reject remarks if provided
    $rejectRemarks = isset($_POST['rejectRemarks']) ? filter_var($_POST['rejectRemarks']) : '';

    try {
        // Check for 'Accept' button submission
        if (isset($_POST['acceptApp'])) {
            // Update application status to Accepted (1)
            $query = "UPDATE application SET status = 1 WHERE appID = :appID";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':appID', $appID, PDO::PARAM_STR);
            $stmt->execute();

            // Redirect after update
            header('Location: admin.php');
            exit;

            // Check for 'Reject' button submission
        } elseif (isset($_POST['rejectApp'])) {
            // Update application status to Rejected (2)
            // If remarks are provided, update them in the database
            $query = "UPDATE application SET status = 2, reject_remarks = :rejectRemarks WHERE appID = :appID";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':appID', $appID, PDO::PARAM_STR);
            $stmt->bindParam(':rejectRemarks', $rejectRemarks, PDO::PARAM_STR);
            $stmt->execute();

            // Redirect after update
            header('Location: admin.php');
            exit;
        }
    } catch (Exception $e) {
        // Error handling if query execution fails
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
                        <span>Admin</span>
                    </li>
                </ul>
                <div x-data="sales" class="flex justify-center">
                    <br>
                    <div class="panel w-full lg:w-2/3 shadow-lg rounded-lg">
                        <div class="mb-5 flex items-center justify-between">
                            <h5 class="text-lg font-semibold dark:text-white-light">Submissions</h5>
                        </div>

                        <br>
                        <div id="form-container">
                            <div class="mb-5">
                                <button id="back-button" onclick="backBtn()">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left" viewBox="0 0 16 16">
                                        <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8" />
                                    </svg>
                                </button>
                                <form method="post" action="" class="space-y-5">
                                    <!-- Hidden inputs to pass data -->
                                    <input type="hidden" id="requestId" name="requestId" value="<?php echo $appID; ?>" required />
                                    <input type="hidden" id="status" name="status" value="pending" required /> <!-- Example, adjust as needed -->

                                    <div class="flex flex-col sm:flex-row">
                                        <label for="appliID" class="mb-0 rtl:ml-2 sm:w-1/4 sm:ltr:mr-2">Application ID</label>
                                        <input id="appliID" name="appliID" type="text" value="<?php echo $appID; ?>" class="form-input flex-1" required disabled />
                                    </div>

                                    <!-- Email -->
                                    <div class="flex flex-col sm:flex-row">
                                        <label for="email" class="mb-0 rtl:ml-2 sm:w-1/4 sm:ltr:mr-2">Email</label>
                                        <input id="email" name="email" type="text" value="<?php echo htmlspecialchars($email); ?>" class="form-input flex-1" required disabled />
                                    </div>

                                    <!-- Category Number -->
                                    <div class="flex flex-col sm:flex-row">
                                        <label for="catnum" class="mb-0 rtl:ml-2 sm:w-1/4 sm:ltr:mr-2">Category Number</label>
                                        <input id="catnum" name="catnum" type="text" value="<?php echo htmlspecialchars($catnum); ?>" class="form-input flex-1" required disabled />
                                    </div>

                                    <!-- Specimen Name -->
                                    <div class="flex flex-col sm:flex-row">
                                        <label for="specname" class="mb-0 rtl:ml-2 sm:w-1/4 sm:ltr:mr-2">Specimen Name</label>
                                        <input id="specname" name="specname" type="text" value="<?php echo htmlspecialchars($specname); ?>" class="form-input flex-1" required disabled />
                                    </div>

                                    <!-- Location -->
                                    <div class="flex flex-col sm:flex-row">
                                        <label for="location" class="mb-0 rtl:ml-2 sm:w-1/4 sm:ltr:mr-2">Location</label>
                                        <input id="location" name="location" type="text" value="<?php echo htmlspecialchars($location); ?>" class="form-input flex-1" required disabled />
                                    </div>

                                    <!-- Examination -->
                                    <div class="flex flex-col sm:flex-row">
                                        <label for="examination" class="mb-0 rtl:ml-2 sm:w-1/4 sm:ltr:mr-2">Examination</label>
                                        <textarea id="examination" name="examination" class="form-input flex-1" required disabled><?php echo htmlspecialchars($examination); ?></textarea>
                                    </div>

                                    <!-- Specimen Condition -->
                                    <div class="flex flex-col sm:flex-row">
                                        <label for="speccond" class="mb-0 rtl:ml-2 sm:w-1/4 sm:ltr:mr-2">Specimen Condition</label>
                                        <input id="speccond" name="speccond" type="text" value="<?php echo htmlspecialchars($speccond); ?>" class="form-input flex-1" required disabled />
                                    </div>

                                    <!-- Material -->
                                    <div class="flex flex-col sm:flex-row">
                                        <label for="material" class="mb-0 rtl:ml-2 sm:w-1/4 sm:ltr:mr-2">Material</label>
                                        <textarea id="material" name="material" class="form-input flex-1" required disabled><?php echo htmlspecialchars($material); ?></textarea>
                                    </div>

                                    <!-- Work Method -->
                                    <div class="flex flex-col sm:flex-row">
                                        <label for="workmeth" class="mb-0 rtl:ml-2 sm:w-1/4 sm:ltr:mr-2">Work Method</label>
                                        <textarea id="workmeth" name="workmeth" class="form-input flex-1" required disabled><?php echo htmlspecialchars($workmeth); ?></textarea>
                                    </div>

                                    <!-- Inspector Name -->
                                    <div class="flex flex-col sm:flex-row">
                                        <label for="inspectname" class="mb-0 rtl:ml-2 sm:w-1/4 sm:ltr:mr-2">Inspector Name</label>
                                        <input id="inspectname" name="inspectname" type="text" value="<?php echo htmlspecialchars($inspectname); ?>" class="form-input flex-1" required disabled />
                                    </div>

                                    <!-- Remarks -->
                                    <div class="flex flex-col sm:flex-row">
                                        <label for="remarks" class="mb-0 rtl:ml-2 sm:w-1/4 sm:ltr:mr-2">Remarks</label>
                                        <textarea id="remarks" name="remarks" class="form-input flex-1" required disabled><?php echo htmlspecialchars($remarks); ?></textarea>
                                    </div>

                                    <!-- Before Images -->
                                    <div class="flex flex-col sm:flex-row">
                                        <h3 class="mb-0 rtl:ml-2 sm:w-1/4 sm:ltr:mr-2">Before Images</h3>
                                        <?php
                                        $beforeImagesDir = "uploads/" . $appID . "/Before";

                                        if (is_dir($beforeImagesDir)) {
                                            $images = glob($beforeImagesDir . "/*.{jpg,jpeg,png,gif,JPG,JPEG,PNG,GIF}", GLOB_BRACE);
                                            if (!empty($images)) {
                                                foreach ($images as $image) {
                                                    echo '<div class="flex-shrink-0 p-2">';
                                                    echo '<img src="' . htmlspecialchars($image) . '" alt="Before Image" class="w-32 h-32 rounded-md shadow-md cursor-pointer" onclick="openModal(\'' . htmlspecialchars($image) . '\')">';
                                                    echo '</div>';
                                                }
                                            } else {
                                                echo '<p class="w-full text-gray-500">No images available in the "Before" folder.</p>';
                                            }
                                        } else {
                                            echo '<p class="w-full text-gray-500">Directory does not exist for the specified Application ID.</p>';
                                        }
                                        ?>
                                    </div>

                                    <?php if ($status === 3): ?>
                                        <!-- After Images -->
                                        <div class="flex flex-col sm:flex-row">
                                            <h3 class="mb-0 rtl:ml-2 sm:w-1/4 sm:ltr:mr-2">After Images</h3>
                                            <?php
                                            $afterImagesDir = "uploads/" . $appID . "/After";

                                            if (is_dir($afterImagesDir)) {
                                                $images = glob($afterImagesDir . "/*.{jpg,jpeg,png,gif,JPG,JPEG,PNG,GIF}", GLOB_BRACE);
                                                if (!empty($images)) {
                                                    foreach ($images as $image) {
                                                        echo '<div class="flex-shrink-0 p-2">';
                                                        echo '<img src="' . htmlspecialchars($image) . '" alt="After Image" class="w-32 h-32 rounded-md shadow-md cursor-pointer" onclick="openModal(\'' . htmlspecialchars($image) . '\')">';
                                                        echo '</div>';
                                                    }
                                                } else {
                                                    echo '<p class="w-full text-gray-500">No images available in the "After" folder.</p>';
                                                }
                                            } else {
                                                echo '<p class="w-full text-gray-500">Directory does not exist for the specified Application ID.</p>';
                                            }
                                            ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Modal for displaying enlarged image -->
                                    <div id="imageModal" class="fixed top-0 left-0 w-full h-full hidden z-50 flex justify-center items-center" style="background-color: rgba(0, 0, 0, 0.6);">
                                        <div class="relative">
                                            <img id="modalImage" class="w-80 h-80" />
                                        </div>
                                    </div>

                                    <!-- Modal for Reject Remarks -->
                                    <div id="rejectModal" class="fixed top-0 left-0 w-full h-full hidden z-50 flex justify-center items-center" style="background-color: rgba(0, 0, 0, 0.6);">
                                        <div class="relative bg-black text-white p-6 rounded-lg shadow-lg max-w-md w-full">
                                            <!-- Modal Header -->
                                            <div class="mb-6">
                                                <h2 class="text-xl font-semibold text-white">Enter Reject Remarks</h2>
                                                <p class="text-sm text-gray-400">Please provide your reasons for rejecting this application.</p>
                                            </div>

                                            <!-- Textarea for Remarks -->
                                            <textarea id="rejectRemarks" name="rejectRemarks" class="form-input mt-4 p-4 border rounded-md focus:ring-2 focus:ring-red-400 resize-none bg-gray-800 text-white placeholder-gray-500" rows="5" placeholder="Enter your remarks here..."></textarea>

                                            <!-- Modal Footer with Centered Buttons -->
                                            <div class="flex justify-center gap-10 mt-6">
                                                <button id="cancelRejectBtn" class="btn btn-success text-white bg-gray-500 hover:bg-gray-600 py-3 px-6 rounded-md" onclick="closeRejectModal()">Cancel</button>
                                                <button id="confirmRejectBtn" name="rejectApp" class="btn btn-danger text-white bg-red-500 hover:bg-red-600 py-3 px-6 rounded-md w-32">Reject</button>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if ($status === 0): ?>
                                        <div class="flex space-x-4">
                                            <!-- Accept Button -->
                                            <button id="accept-btn" type="submit" name="acceptApp" value="acceptApp" class="btn btn-success text-white bg-green-500 hover:bg-green-600 p-2 rounded-md">
                                                Accept
                                            </button>
                                            <!-- Reject Button (modified to show modal) -->
                                            <button id="reject-btn" type="button" class="btn btn-danger text-white bg-red-500 hover:bg-red-600 p-2 rounded-md" onclick="openRejectModal()">
                                                Reject
                                            </button>

                                        </div>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
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
        document.addEventListener("DOMContentLoaded", function() {
            // Assuming `status` is dynamically added as a hidden input or data attribute
            const status = <?php echo $status ?>;

            // Get the buttons
            const acceptBtn = document.getElementById("accept-btn");
            const rejectBtn = document.getElementById("reject-btn");

            // Hide or show buttons based on status
            if (acceptBtn) {
                acceptBtn.disabled = status !== 0;
            }
            if (rejectBtn) {
                rejectBtn.disabled = status !== 0;
            }

            // Modal open and close functionality
            window.openModal = function(imageSrc) {
                const modal = document.getElementById("imageModal");
                const modalImage = document.getElementById("modalImage");
                modalImage.src = imageSrc;
                modal.style.display = "flex";
            };

            document.getElementById("imageModal").addEventListener("click", function(e) {
                if (e.target === this) {
                    this.style.display = "none";
                }
            });

            // Back button functionality
            window.backBtn = function() {
                window.location.href = 'admin.php';
            };
        });

        // Open the Reject Modal
        function openRejectModal() {
            document.getElementById('rejectModal').style.display = 'flex';
        }

        // Close the Reject Modal
        function closeRejectModal() {
            document.getElementById('rejectModal').style.display = 'none';
        }
    </script>
</body>

</html>