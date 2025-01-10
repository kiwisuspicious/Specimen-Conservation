<?php
session_start();
// Check if the user is not logged in
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    // Redirect to the login page if not logged in
    header('Location: admin-login.php');
    exit;
}
include('includes/config.php');
$pdo = pdo_connect_mysql(); 

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // $mail = new PHPMailer(true);
    // $mail->isSMTP();
    // $mail->Host = 'smtp.gmail.com';
    // $mail->SMTPAuth = true;
    // $mail->Username = '';
    // $mail->Password = '';
    // $mail->SMTPSecure = 'ssl';
    // $mail->Port = 465;
    // $mail->setFrom('');
    // $mail->addAddress('');
    // $mail->isHTML(true);
    // $mail->Subject = '';
    // $mail->Body = ;
    // $mail->send();

    // Sanitize and validate inputs
    $catalogueNum = htmlspecialchars(trim($_POST['catalogueNum']));
    $specName = htmlspecialchars(trim($_POST['specName']));
    $location = isset($_POST['location']) ? htmlspecialchars($_POST['location']) : '';
    $otherLocation = isset($_POST['other-location']) ? htmlspecialchars(trim($_POST['other-location'])) : '';
    $finalLocation = ($location === 'Others') ? $otherLocation : $location;

    // Process examination checkboxes
    $examinationArray = isset($_POST['examination']) && is_array($_POST['examination']) ? $_POST['examination'] : [];
    if (in_array('Others', $examinationArray) && !empty($_POST['other-examination'])) {
        $examinationArray[] = htmlspecialchars(trim($_POST['other-examination']));
    }
    $examination = implode(', ', $examinationArray);

    // Process material checkboxes
    $materialArray = isset($_POST['material']) && is_array($_POST['material']) ? $_POST['material'] : [];
    if (in_array('Others', $materialArray) && !empty($_POST['other-material'])) {
        $materialArray[] = htmlspecialchars(trim($_POST['other-material']));
    }
    $material = implode(', ', $materialArray);

    // Other inputs
    $condition = isset($_POST['condition']) ? intval($_POST['condition']) : 0;
    $workMethod = htmlspecialchars(trim($_POST['method-statement']));
    $remarks = htmlspecialchars(trim($_POST['remarks']));
    $inspector = isset($_POST['inspector']) ? htmlspecialchars(trim($_POST['inspector'])) : '';

    // File upload handling
    $targetDir = "uploads/";

    // Generate a unique appID before folder creation and database insertion
    function generateAppID($pdo)
    {
        do {
            // Generate a random 5-digit number
            $randomNumber = str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
            $appID = 'APR' . $randomNumber;

            // Check if appID already exists in the database
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM application WHERE appID = :appID");
            $stmt->execute([':appID' => $appID]);
            $count = $stmt->fetchColumn();
        } while ($count > 0); // Repeat until a unique appID is found

        return $appID;
    }

    // Generate a unique appID
    $newAppID = generateAppID($pdo);

    // Create folder for the appID and subfolder 'Before'
    $appFolder = $targetDir . $newAppID . "/Before";
    if (!file_exists($appFolder)) {
        mkdir($appFolder, 0777, true); // Create folders with necessary permissions
    }

    // Array to store uploaded file paths (if needed for later use, not for DB)
    $uploadedFiles = [];
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

    // Check if photos were uploaded
    if (!empty($_FILES['photos']['name'][0])) {
        $fileCount = count($_FILES['photos']['name']);

        // // Limit to 5 files
        // if ($fileCount > 5) {
        //     die("Error: You can upload a maximum of 5 photos.");
        // }

        // Loop through uploaded files
        for ($i = 0; $i < $fileCount; $i++) {
            $fileName = basename($_FILES['photos']['name'][$i]);
            $fileType = pathinfo($fileName, PATHINFO_EXTENSION);

            // Validate file type
            if (in_array(strtolower($fileType), $allowedTypes)) {
                // Generate a new file name in the desired format
                $photoIndex = $i + 1; // Index starts at 1
                $newFileName = "before_{$newAppID}_{$photoIndex}.{$fileType}";
                $targetFilePath = $appFolder . '/' . $newFileName;

                // Move uploaded file
                if (move_uploaded_file($_FILES['photos']['tmp_name'][$i], $targetFilePath)) {
                    $uploadedFiles[] = $targetFilePath; // Store file path if needed
                } else {
                    die("Error: Failed to upload file " . $fileName);
                }
            } else {
                die("Error: Only JPG, JPEG, PNG, and GIF files are allowed.");
            }
        }
    }


    // Database interaction to insert the new record without file paths
    try {
        // Prepare SQL query (no file paths in specphoto column)
        $sql = "INSERT INTO application (email, catnum, specname, location, examination, speccond, material, workmeth, inspectname, remarks, appID) 
            VALUES (:email, :catnum, :specname, :location, :examination, :speccond, :material, :workmeth, :inspectname, :remarks, :appID)";

        // Replace this with a session email if using authentication
        $email = 'test@example.com';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':email'       => $email,
            ':catnum'      => $catalogueNum,
            ':specname'    => $specName,
            ':location'    => $finalLocation,
            ':examination' => $examination,
            ':speccond'    => $condition,
            ':material'    => $material,
            ':workmeth'    => $workMethod,
            ':inspectname' => $inspector,
            ':remarks'     => $remarks,
            ':appID'       => $newAppID
        ]);

        // Redirect to the same page after form submission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">

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

            <div class="dvanimation animate__animated p-6 flex-grow" :class="[$store.app.animation]">
                <?php include('includes/sidebar.php'); ?>

                <!-- start main content section -->
                <div x-data="sales">
                    <ul class="flex space-x-2 rtl:space-x-reverse">
                        <li>
                            <a href="index.php" class="text-primary hover:underline">Main</a>
                        </li>
                        <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                            <span>Submit Report</span>
                        </li>
                    </ul>
                    <br>
                    <br>
                    <div class="flex justify-center">
                        <div class="panel w-full lg:w-2/3 shadow-lg rounded-lg">
                            <div id="form-container">
                                <div class="mb-5">
                                    <form method="POST" action="submit-report.php" enctype="multipart/form-data" class="space-y-5">
                                        <!-- Catalogue Number -->
                                        <div class="flex flex-col sm:flex-row">
                                            <label for="catalogueNum" class="mb-2 sm:w-1/4 text-sm font-medium">Catalogue Number</label>
                                            <input id="catalogueNum" name="catalogueNum" type="text" placeholder="Enter Catalogue Number" class="form-input flex-1 rounded-md border border-gray-300 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 p-2" required />
                                        </div>

                                        <!-- Specimen/Object Name -->
                                        <div class="flex flex-col sm:flex-row">
                                            <label for="specName" class="mb-2 sm:w-1/4 text-sm font-medium">Specimen/Object Name</label>
                                            <input id="specName" name="specName" type="text" placeholder="Enter Specimen/Object Name" class="form-input flex-1 rounded-md border border-gray-300 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 p-2" required />
                                        </div>

                                        <!-- Location (Radio buttons for single selection) -->
                                        <div class="flex flex-col sm:flex-row">
                                            <label class="mb-2 sm:w-1/4 text-sm font-medium">Location</label>
                                            <div class="mt-2 space-y-2">
                                                <div class="flex items-center">
                                                    <input type="radio" id="location-1" name="location" value="Natural History Building" class="form-radio text-blue-500 focus:ring-blue-500 mr-2">
                                                    <label for="location-1">Natural History Building</label>
                                                </div>

                                                <div class="flex items-center">
                                                    <input type="radio" id="location-others" name="location" value="Others" class="form-radio text-blue-500 focus:ring-blue-500 mr-2" onclick="toggleOtherLocationInput(true)">
                                                    <label for="location-others">Others</label>
                                                </div>

                                                <!-- Text input for "Others" location, initially hidden -->
                                                <div id="other-location-container" class="mt-2 hidden">
                                                    <input type="text" id="other-location" name="other-location" class="form-input flex-1 rounded-md border border-gray-300 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 p-2" placeholder="Enter custom location">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Examination (Checkboxes for multiple selections) -->
                                        <div class="flex flex-col sm:flex-row">
                                            <label class="mb-2 sm:w-1/4 text-sm font-medium">Examination</label>
                                            <div class="mt-2 space-y-2">
                                                <div class="flex items-center">
                                                    <input type="checkbox" id="examination-1" name="examination[]" value="Dirt Accumulation (Pengumpulan kotoran)" class="form-checkbox text-blue-500 focus:ring-blue-500 mr-2">
                                                    <label for="examination-1">Dirt Accumulation (Pengumpulan kotoran)</label>
                                                </div>

                                                <div class="flex items-center">
                                                    <input type="checkbox" id="examination-2" name="examination[]" value="Stitch opening (Jahitan terbuka)" class="form-checkbox text-blue-500 focus:ring-blue-500 mr-2">
                                                    <label for="examination-2">Stitch opening (Jahitan terbuka)</label>
                                                </div>

                                                <div class="flex items-center">
                                                    <input type="checkbox" id="examination-3" name="examination[]" value="Skin cracking/tearing (Kulit retak/terkoyak)" class="form-checkbox text-blue-500 focus:ring-blue-500 mr-2">
                                                    <label for="examination-3">Skin cracking/tearing (Kulit retak/terkoyak)</label>
                                                </div>

                                                <div class="flex items-center">
                                                    <input type="checkbox" id="examination-others" name="examination[]" value="Others" class="form-checkbox text-blue-500 focus:ring-blue-500 mr-2" onclick="toggleOtherExaminationInput(true)">
                                                    <label for="examination-others">Others</label>
                                                </div>

                                                <!-- Text input for "Others" examination -->
                                                <div id="other-examination-container" class="mt-2 hidden">
                                                    <input type="text" id="other-examination" name="other-examination" class="form-input flex-1 rounded-md border border-gray-300 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 p-2" placeholder="Enter custom examination">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Condition (Radio buttons for rating 1-4) -->
                                        <div class="flex flex-col sm:flex-row">
                                            <label class="mb-2 sm:w-1/4 text-sm font-medium">Condition</label>
                                            <div class="mt-2 flex space-x-4">
                                                <span class="mr-8">Poor</span>
                                                <label class="flex flex-col items-center">
                                                    <span>1</span>
                                                    <input type="radio" name="condition" value="1" class="form-radio text-blue-500 mt-1">
                                                </label>
                                                <label class="flex flex-col items-center">
                                                    <span>2</span>
                                                    <input type="radio" name="condition" value="2" class="form-radio text-blue-500 mt-1">
                                                </label>
                                                <label class="flex flex-col items-center">
                                                    <span>3</span>
                                                    <input type="radio" name="condition" value="3" class="form-radio text-blue-500 mt-1">
                                                </label>
                                                <label class="flex flex-col items-center">
                                                    <span>4</span>
                                                    <input type="radio" name="condition" value="4" class="form-radio text-blue-500 mt-1">
                                                </label>
                                                <span class="ml-8">Excellent</span>
                                            </div>
                                        </div>

                                        <!-- Material (Checkboxes for multiple selections) -->
                                        <div class="flex flex-col sm:flex-row">
                                            <label class="mb-2 sm:w-1/4 text-sm font-medium">Material</label>
                                            <div class="mt-2 space-y-2">
                                                <div class="flex items-center">
                                                    <input type="checkbox" id="material-1" name="material[]" value="Japanese Tissue" class="form-checkbox text-blue-500 focus:ring-blue-500 mr-2">
                                                    <label for="material-1">Japanese Tissue</label>
                                                </div>

                                                <div class="flex items-center">
                                                    <input type="checkbox" id="material-2" name="material[]" value="Wood Clay" class="form-checkbox text-blue-500 focus:ring-blue-500 mr-2">
                                                    <label for="material-2">Wood Clay</label>
                                                </div>

                                                <div class="flex items-center">
                                                    <input type="checkbox" id="material-3" name="material[]" value="Putty" class="form-checkbox text-blue-500 focus:ring-blue-500 mr-2">
                                                    <label for="material-3">Putty</label>
                                                </div>

                                                <div class="flex items-center">
                                                    <input type="checkbox" id="material-4" name="material[]" value="Fibre Glass" class="form-checkbox text-blue-500 focus:ring-blue-500 mr-2">
                                                    <label for="material-4">Fibre Glass</label>
                                                </div>

                                                <div class="flex items-center">
                                                    <input type="checkbox" id="material-5" name="material[]" value="Clay" class="form-checkbox text-blue-500 focus:ring-blue-500 mr-2">
                                                    <label for="material-5">Clay</label>
                                                </div>

                                                <div class="flex items-center">
                                                    <input type="checkbox" id="material-6" name="material[]" value="Critter Clay" class="form-checkbox text-blue-500 focus:ring-blue-500 mr-2">
                                                    <label for="material-6">Critter Clay</label>
                                                </div>

                                                <div class="flex items-center">
                                                    <input type="checkbox" id="material-7" name="material[]" value="Powder of Paris (PoP)" class="form-checkbox text-blue-500 focus:ring-blue-500 mr-2">
                                                    <label for="material-7">Powder of Paris (PoP)</label>
                                                </div>

                                                <div class="flex items-center">
                                                    <input type="checkbox" id="material-others" name="material[]" value="Others" class="form-checkbox text-blue-500 focus:ring-blue-500 mr-2" onclick="toggleOtherMaterialInput(true)">
                                                    <label for="material-others">Others</label>
                                                </div>

                                                <!-- Text input for "Others" material, initially hidden -->
                                                <div id="other-material-container" class="mt-2 hidden">
                                                    <input type="text" id="other-material" name="other-material" class="form-input flex-1 rounded-md border border-gray-300 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 p-2" placeholder="Enter custom material">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Work Method Statement -->
                                        <div class="flex flex-col sm:flex-row">
                                            <label for="method-statement" class="mb-0 rtl:ml-2 sm:w-1/4 sm:ltr:mr-2">Work Method Statement</label>
                                            <textarea id="method-statement" name="method-statement" rows="4" class="form-input flex-1" placeholder="Enter Method Statement"></textarea>
                                        </div>

                                        <!-- Photo of Specimen -->
                                        <div class="flex flex-col sm:flex-row">
                                            <label for="photos" class="mb-2 sm:w-1/4 text-sm font-medium">Photo of Specimen</label>
                                            <input type="file" id="photos" name="photos[]" class="form-input flex-1 p-2" multiple accept=".jpg, .jpeg, .png, .gif">
                                        </div>

                                        <!-- Inspector (Dropdown Menu) -->
                                        <div class="flex flex-col sm:flex-row">
                                            <label for="inspector" class="mb-2 sm:w-1/4 text-sm font-medium">Inspector</label>
                                            <select id="inspector" name="inspector" class="form-input flex-1 rounded-md border border-gray-300 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 p-2">
                                                <option value="">Select Inspector</option>
                                                <option value="John Doe">John Doe</option>
                                                <option value="Jane Smith">Jane Smith</option>
                                                <option value="Alex Johnson">Alex Johnson</option>
                                            </select>
                                        </div>

                                        <!-- Examination Remarks -->
                                        <div class="flex flex-col sm:flex-row">
                                            <label for="remarks" class="mb-2 sm:w-1/4 text-sm font-medium">Examination Remarks</label>
                                            <input type="text" id="remarks" name="remarks" class="form-input flex-1 rounded-md border border-gray-300 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 p-2" placeholder="Enter Remarks">
                                        </div>

                                        <!-- Submit Button -->
                                        <div class="flex sm:flex-row space-x-4">
                                            <button id="submit-button" type="submit" class="btn btn-success mb-0 text-white bg-blue-500 hover:bg-blue-600 p-2 rounded-md">
                                                Submit
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                        </div>
                    </div>
                    <script>
                        // JavaScript function to toggle the visibility of the "Others" input
                        function toggleOtherLocationInput(show) {
                            const otherLocationContainer = document.getElementById('other-location-container');
                            if (show) {
                                otherLocationContainer.classList.remove('hidden');
                            } else {
                                otherLocationContainer.classList.add('hidden');
                            }
                        }

                        // Ensure that if any of the default locations are selected, the "Others" input is hidden
                        document.querySelectorAll('input[name="location"]').forEach(radio => {
                            radio.addEventListener('change', function() {
                                if (this.value !== "Others") {
                                    toggleOtherLocationInput(false);
                                }
                            });
                        });

                        function toggleOtherExaminationInput(show) {
                            const otherExaminationContainer = document.getElementById('other-examination-container');
                            if (show) {
                                otherExaminationContainer.classList.remove('hidden');
                            } else {
                                otherExaminationContainer.classList.add('hidden');
                            }
                        }

                        function toggleOtherMaterialInput(show) {
                            const otherMaterialContainer = document.getElementById('other-material-container');
                            if (show) {
                                otherMaterialContainer.classList.remove('hidden');
                            } else {
                                otherMaterialContainer.classList.add('hidden');
                            }
                        }
                    </script>
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
</body>

</html>