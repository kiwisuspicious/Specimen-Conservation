<?php
session_start();
if (!isset($_SESSION['stafflogin'])) {
    header('Location: https://localhost/vg/login/login.php');
    exit();
} elseif ($_SESSION['designation'] !== 'archive') {
    header('Location: https://localhost/vg/dashboard/index.php');
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

include('includes/config.php');
$pdo_login = pdo_connect_mysql();
$pdo_smg = pdo_connect_mysql2();

if ($_SESSION['designation'] != 'archive') {
    $stmt = $pdo_login->prepare("SELECT * FROM archive");
} else {
    $stmt = $pdo_login->prepare("SELECT * FROM archive WHERE folderName IS NULL OR folderName = ''");
}

$stmt->execute();
$archives = $stmt->fetchAll(PDO::FETCH_ASSOC);

try {
    $sql = "SELECT nameTag, colourTag FROM tagging";
    $query = $pdo_login->prepare($sql);
    $query->execute();
    $tags = $query->fetchAll(PDO::FETCH_ASSOC);

    // Create a mapping of tags to their colors
    $tagColors = [];
    foreach ($tags as $tag) {
        $tagColors[$tag['nameTag']] = $tag['colourTag'];
    }
} catch (PDOException $e) {
    // Handle error
    echo "Error: " . $e->getMessage();
}

try {
    $sql = "SELECT nameTag, colourTag FROM tagging";
    $query = $pdo_login->prepare($sql);
    $query->execute();
    $formTags = $query->fetchAll(PDO::FETCH_ASSOC);

    // Create a mapping of tags to their colors
    $formTagColors = [];
    foreach ($formTags as $formTag) {
        $formTagColors[$formTag['nameTag']] = $formTag['colourTag'];
    }
} catch (PDOException $e) {
    // Handle error
    echo "Error: " . $e->getMessage();
}

try {
    $sql = "SELECT vipName, colourVIP FROM viplist";
    $query = $pdo_login->prepare($sql);
    $query->execute();
    $vipNames = $query->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle error
    echo "Error: " . $e->getMessage();
}


try {
    $sql = "SELECT name, email FROM tblemployee WHERE designation IN ('vg', 'pg')";
    $query = $pdo_smg->prepare($sql);
    $query->execute();
    $names = $query->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

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

    if (isset($_POST['update'])) {
        $requestId = $_POST['requestId'];
        $cutways = $_POST['cutways'];
        $folderName = $_POST['folderName'];
        $functionName = $_POST['functionName'];
        $locationName = $_POST['locationName'];
        $tagTopic = $_POST['tagTopicForm'];
        $vip = $_POST['vipForm'];
        $vgPg = $_POST['vgPg'];

        try {
            if ($_SESSION['designation'] != 'archive') {
                $sql = "UPDATE archive SET cutways = ?, folderName = ?, functionName = ?, locationName = ?, tagTopic = ?, vip = ?, vgPg = ? WHERE requestId = ?";
                $stmt = $pdo_login->prepare($sql);
                $stmt->execute([$cutways, $folderName, $functionName, $locationName, $tagTopic, $vip, $vgPg, $requestId]);
                header('Location: https://localhost/vg/dashboard/admin.php');
            } else {
                $sql = "UPDATE archive SET cutways = ?, functionName = ?, locationName = ?, tagTopic = ?, vip = ?, vgPg = ? WHERE requestId = ?";
                $stmt = $pdo_login->prepare($sql);
                $stmt->execute([$cutways, $functionName, $locationName, $tagTopic, $vip, $vgPg, $requestId]);
                header('Location: https://localhost/vg/dashboard/admin.php');
            }
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    } elseif (isset($_POST['reject'])) {
        $requestId = $_POST['requestId'];
        $status = -1;
        $staff_input = $_POST['remarks'];

        $mail->Subject = 'Archive Request Rejected';

        try {
            $sql = "UPDATE archive SET status = ?, staff_input = ? WHERE requestId = ?";
            $stmt = $pdo_login->prepare($sql);
            $stmt->execute([$status, $staff_input, $requestId]);
            $mail->Body = "Dear requester,<br><br>" .  htmlspecialchars($_SESSION['name'], ENT_QUOTES, 'UTF-8') . " has rejected your request. Please login into <a href='https://localhost/vg/dashboard/index.php'>MediaNest</a> for more details.<br><br>
    
            Thank you.<br><br>
            
            Regards,<br>
            SMG MediaNest.";
            $mail->send();
            header('Location: https://localhost/vg/dashboard/admin.php');
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    } elseif (isset($_POST['accept'])) {
        $requestId = $_POST['requestId'];
        $status = 1;
        $adminIncharge = $_SESSION['name'];

        $mail->Subject = 'Archive Request Completed';

        try {
            $sql = "UPDATE archive SET status = ?, adminIncharge = ? WHERE requestId = ?";
            $stmt = $pdo_login->prepare($sql);
            $stmt->execute([$status, $adminIncharge, $requestId]);
            $mail->Body = "Dear archive team,<br><br>" .  htmlspecialchars($_SESSION['name'], ENT_QUOTES, 'UTF-8') . " has completed your request. Please login into <a href='https://localhost/vg/dashboard/index.php'>MediaNest</a> for more details.<br><br>
    
            Thank you.<br><br>
            
            Regards,<br>
            SMG MediaNest.";
            $mail->send();
            header('Location: https://localhost/vg/dashboard/admin.php');
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<style>
    .tag-pill {
        display: inline-block;
        background-color: #e0e0e0;
        color: #333;
        padding: 5px 10px;
        margin: 2px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: bold;
    }

    .remove-btn {
        margin-left: 10px;
        /* Adjust this value to increase or decrease the space */
        background: none;
        border: none;
        color: black;
        font-weight: bold;
        cursor: pointer;
    }

    .dataTables_length {
        display: none;
    }

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

    <style>
        /* Hide the search box for the specific table */
        #myTable_filter {
            display: none;
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
                <ul class="flex space-x-2 rtl:space-x-reverse p-6">
                    <li>
                        <a href="index.php" class="text-primary hover:underline">Dashboard</a>
                    </li>
                    <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                        <span>Admin</span>
                    </li>
                </ul>
                <div x-data="sales" class="flex flex-col items-center justify-center space-y-3">
                    <br>
                    <div class="panel w-full lg:w-2/3 shadow-lg rounded-lg">
                        <div class="mb-5 flex items-center justify-between">
                            <h5 class="text-lg font-semibold dark:text-white-light">Submissions</h5>
                        </div>
                        <div class="table-responsive" id="submission-records">
                            <div class="flex flex-col sm:flex-row">
                                <!-- <label for="taggingTopic" class="mb-0 rtl:ml-2 sm:w-1/6 sm:ltr:mr-2">Tag/Topic</label> -->
                                <input type="text" id="taggingTopic" name="taggingTopic" class="form-input flex-1" list="tagTopicList" placeholder="Enter Tag/Topic" onchange="handleTagInput(this)" />
                                <datalist id="tagTopicList">
                                    <option value="">Select Tag/Topic</option>
                                    <?php foreach ($tags as $tag) : ?>
                                        <option value="<?php echo htmlspecialchars($tag['nameTag']); ?>" data-color="<?php echo htmlspecialchars($tag['colourTag']); ?>"><?php echo htmlspecialchars($tag['nameTag']); ?></option>
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                            <div id="selectedTagsContainer" class="mt-2 flex flex-wrap"></div>
                            <input id="tagTopic_" name="tagTopic[]" type="hidden" placeholder="Enter Tag/Topic" class="form-input flex-1" readonly />
                            <br>
                            <table id="myTable">
                                <thead>
                                    <tr>
                                        <th class="ltr:rounded-l-md rtl:rounded-r-md">No.</th>
                                        <th>Videographer/Photographer</th>
                                        <th>Cutways</th>
                                        <th>Tag / Topic</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $count = +1;
                                    foreach ($archives as $row) :
                                    ?>
                                        <tr onclick='showForm(<?php echo json_encode($row, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' class="group text-white-dark hover:text-black dark:hover:text-white-light/90">
                                            <td class="min-w-[150px] text-black dark:text-white">
                                                <div class="flex items-center">
                                                    <span class="whitespace-nowrap"><?php echo $count; ?></span>
                                                </div>
                                            </td>
                                            <td class="text-primary"><?php echo htmlspecialchars($row['vgPg']); ?></td>
                                            <td><a><?php echo htmlspecialchars($row['cutways']); ?></a></td>
                                            <td>
                                                <?php
                                                $tags = explode(';', $row['tagTopic']); // Assuming tags are semicolon-separated
                                                foreach ($tags as $tag) {
                                                    $trimmedTag = htmlspecialchars(trim($tag));
                                                    $color = isset($tagColors[$trimmedTag]) ? $tagColors[$trimmedTag] : '#000'; // Default color if not found
                                                    echo '<span class="tag-pill" style="background-color: ' . $color . ';">' . $trimmedTag . '</span>';
                                                }
                                                ?>
                                            </td>
                                            <td><a><?php echo htmlspecialchars($row['date']); ?></a></td>
                                            <td class="text-primary">
                                                <?php
                                                if ($row["status"] == 0) {
                                                    echo '<span class="badge bg-warning shadow-md dark:group-hover:bg-transparent">Pending</span>';
                                                } elseif ($row["status"] == 1) {
                                                    echo '<span class="badge bg-success shadow-md dark:group-hover:bg-transparent">Completed</span>';
                                                } elseif ($row["status"] == -1) {
                                                    echo '<span class="badge bg-danger shadow-md dark:group-hover:bg-transparent">Rejected</span>';
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
                            <br>
                        </div>
                        <br>
                        <div id="form-container" style="display: none">
                            <div class="mb-5">
                                <button id="back-button" onclick="backBtn()"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left" viewBox="0 0 16 16">
                                        <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8" />
                                    </svg></button>
                                <form method="post" action="admin.php" class="space-y-5">
                                    <input type="hidden" id="requestId" name="requestId" type="text" placeholder="ID" class="form-input flex-1" required />
                                    <input type="hidden" id="status" name="status" type="text" placeholder="status" class="form-input flex-1" required />
                                    <div class="flex flex-col sm:flex-row">
                                        <label for="submitBy" class="mb-0 rtl:ml-2 sm:w-1/4 sm:ltr:mr-2">Submitted By</label>
                                        <input id="submitBy" name="submitBy" type="text" placeholder="Submitted By" class="form-input flex-1" required disabled />
                                    </div>
                                    <div class="flex flex-col sm:flex-row">
                                        <label for="date" class="mb-0 rtl:ml-2 sm:w-1/4 sm:ltr:mr-2">Submit Date</label>
                                        <input id="date" name="date" type="text" placeholder="Submit Date" class="form-input flex-1" required disabled />
                                    </div>
                                    <div class="flex flex-col sm:flex-row">
                                        <label for="cutways" class="mb-0 rtl:ml-2 sm:w-1/4 sm:ltr:mr-2">Cutways</label>
                                        <input id="cutways" name="cutways" type="text" placeholder="Enter Cutways" class="form-input flex-1" required />
                                    </div>
                                    <?php if ($_SESSION['designation'] != 'archive') { ?>
                                        <div class="flex flex-col sm:flex-row">
                                            <label for="folderName" class="mb-0 rtl:ml-2 sm:w-1/4 sm:ltr:mr-2">Folder Name</label>
                                            <input id="folderName" name="folderName" type="text" placeholder="Enter Folder Name" class="form-input flex-1" required />
                                        </div>
                                    <?php } ?>
                                    <div class="flex flex-col sm:flex-row">
                                        <label for="functionName" class="mb-0 rtl:ml-2 sm:w-1/4 sm:ltr:mr-2">Function Name</label>
                                        <input id="functionName" name="functionName" type="text" placeholder="Enter Function Name" class="form-input flex-1" required />
                                    </div>
                                    <div class="flex flex-col sm:flex-row">
                                        <label for="locationName" class="mb-0 rtl:ml-2 sm:w-1/4 sm:ltr:mr-2">Location</label>
                                        <input id="locationName" name="locationName" type="text" placeholder="Enter Location" class="form-input flex-1" required />
                                    </div>
                                    <div class="flex flex-col sm:flex-row">
                                        <label for="taggingTopicForm" class="mb-0 rtl:ml-2 sm:w-1/4 sm:ltr:mr-2">Tag/Topic</label>
                                        <input type="text" id="taggingTopicForm" name="taggingTopicForm" class="form-input flex-1" list="tagTopicListForm" placeholder="Enter Tag/Topic" onchange="handleTagInputForm(this)" />
                                        <datalist id="tagTopicListForm">
                                            <option value="">Select Tag/Topic</option>
                                            <?php foreach ($formTags as $formTag) : ?>
                                                <option value="<?php echo htmlspecialchars($formTag['nameTag']); ?>" data-color="<?php echo htmlspecialchars($formTag['colourTag']); ?>"><?php echo htmlspecialchars($formTag['nameTag']); ?></option>
                                            <?php endforeach; ?>
                                        </datalist>
                                    </div>
                                    <div class="flex flex-col sm:flex-row">
                                        <label for="selectedTagsContainerForm" class="mb-0 rtl:ml-2 sm:w-1/4 sm:ltr:mr-2"></label>
                                        <div id="selectedTagsContainerForm" class="mt-2 flex flex-wrap"></div>
                                    </div>
                                    <input id="tagTopicForm" name="tagTopicForm" type="hidden" placeholder="Enter Tag/Topic" class="form-input flex-1" required />
                                    <div class="flex flex-col sm:flex-row">
                                        <label for="vipNameList" class="mb-0 rtl:ml-2 sm:w-1/4 sm:ltr:mr-2">VIP</label>
                                        <input type="text" id="vipNameList" name="vipNameList" class="form-input flex-1" list="vipList" placeholder="Enter VIP's Name" onchange="handleVipInput(this)" />
                                        <datalist id="vipList">
                                            <option value="">Select VIP Name</option>
                                            <?php foreach ($vipNames as $vipName) : ?>
                                                <option value="<?php echo htmlspecialchars($vipName['vipName']); ?>" data-color="<?php echo htmlspecialchars($vipName['colourVIP']); ?>"><?php echo htmlspecialchars($vipName['vipName']); ?></option>
                                            <?php endforeach; ?>
                                        </datalist>
                                    </div>
                                    <div class="flex flex-col sm:flex-row">
                                        <label for="selectedVipContainer" class="mb-0 rtl:ml-2 sm:w-1/4 sm:ltr:mr-2"></label>
                                        <div id="selectedVipContainer" class="mt-2 flex flex-wrap"></div>
                                    </div>
                                    <input id="vipForm" name="vipForm" type="hidden" placeholder="Enter VIP's Name" class="form-input flex-1" required readonly />
                                    <div class="flex flex-col sm:flex-row">
                                        <label for="vgPg" class="mb-0 rtl:ml-2 sm:w-1/4 sm:ltr:mr-2">VG/PG</label>
                                        <input id="vgPg" name="vgPg" type="text" placeholder="Enter VG/PG Name" class="form-input flex-1" required />
                                    </div>
                                    <div class="flex sm:flex-row space-x-4" id="buttonContainer">
                                        <button type="submit" name="accept" class="btn btn-success mb-0 rtl:ml-2 sm:w-1/6 sm:ltr:mr-2">Accept</button>
                                        <button type="submit" name="update" class="btn btn-warning mb-0 rtl:ml-2 sm:w-1/6 sm:ltr:mr-2">Update</button>
                                        <button type="button" class="btn btn-danger mb-0 rtl:ml-2 sm:w-1/6 sm:ltr:mr-2" onclick="showRejectionPopup()">Reject</button>
                                    </div>
                                    <div id="rejectionPopup" class="popup">
                                        <div class="popup-content dark:bg-[#060818] rounded-lg shadow-lg p-6 mx-8 relative">
                                            <button type="button" onclick="hideRejectionPopup()" class="absolute top-0 right-0 m-2 bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                            <h2 class="text-xl font-bold mb-4">Provide Remarks</h2>
                                            <input id="remarks" name="remarks" type="text" placeholder="Enter Remarks" class="form-input mb-4 px-4 py-2 border rounded-lg w-full" required />
                                            <button type="submit" name="reject" onclick="confirmReject()" class="btn btn-danger text-white font-bold py-2 px-4 rounded">Reject</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <br>
                <!-- end main content section -->

                <style>
                    .neon-white {
                        color: white;
                        text-shadow: 0 0 10px white, 0 0 20px white, 0 0 30px white, 0 0 40px white, 0 0 50px white, 0 0 60px white, 0 0 70px white;
                    }
                </style>


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
                ], // Set the length menu to only display 10 entries
                "paging": true, // Enable pagination
                "searching": true, // Enable searching
            });
        });

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

        function handleTagInputForm(inputElement) {
            const selectedValue = inputElement.value.trim();
            if (!selectedValue) {
                return; // Exit if no value is selected
            }

            const dataList = document.getElementById('tagTopicListForm');
            const option = Array.from(dataList.options).find(opt => opt.value === selectedValue);

            // Check if the value is in the datalist options
            if (!option) {
                alert("Please select a valid tag from the list.");
                inputElement.value = ''; // Clear the original input field
                return;
            }

            const selectedTagsContainer = document.getElementById('selectedTagsContainerForm');
            const currentTags = Array.from(selectedTagsContainer.children).map(tag => tag.getAttribute('data-value'));

            // Check if the value has already been added
            if (currentTags.includes(selectedValue)) {
                alert("This value has already been added.");
                inputElement.value = ''; // Clear the original input field
                return; // Exit if the value is already added
            }

            // Get the color of the selected tag
            const tagColor = option.getAttribute('data-color') || '#ccc'; // Default to grey if no color found

            // Create a new tag pill element
            const tagPill = document.createElement('span');
            tagPill.className = 'tag-pill';
            tagPill.setAttribute('data-value', selectedValue);
            tagPill.textContent = selectedValue;
            tagPill.style.backgroundColor = tagColor;

            // Create the remove button
            const removeBtn = document.createElement('button');
            removeBtn.className = 'remove-btn';
            removeBtn.textContent = 'X';
            removeBtn.onclick = () => {
                selectedTagsContainer.removeChild(tagPill);
                updateHiddenInputForm(); // Update hidden input when a tag is removed
            };

            if (!option) {
                alert("Please select a valid tag from the list.");
                inputElement.value = ''; // Clear the original input field
                return;
            }

            // Append the remove button to the tag pill
            tagPill.appendChild(removeBtn);

            // Append the tag pill to the container
            selectedTagsContainer.appendChild(tagPill);

            // Update the hidden input field
            updateHiddenInputForm();

            // Clear the original input field
            inputElement.value = '';
        }

        function updateHiddenInputForm() {
            const selectedTagsContainer = document.getElementById('selectedTagsContainerForm');
            const hiddenInput = document.getElementById('tagTopicForm');
            const currentTags = Array.from(selectedTagsContainer.children).map(tag => tag.getAttribute('data-value'));
            hiddenInput.value = currentTags.join(';');
        }


        function handleTagInput(inputElement) {
            const selectedValue = inputElement.value.trim();
            if (!selectedValue) {
                return; // Exit if no value is selected
            }

            const dataList = document.getElementById('tagTopicList');
            const option = Array.from(dataList.options).find(opt => opt.value === selectedValue);

            // Check if the value is in the datalist options
            if (!option) {
                alert("Please select a valid tag from the list.");
                inputElement.value = ''; // Clear the original input field
                return;
            }

            const selectedTagsContainer = document.getElementById('selectedTagsContainer');
            const currentTags = Array.from(selectedTagsContainer.children).map(tag => tag.getAttribute('data-value'));

            // Check if the value has already been added
            if (currentTags.includes(selectedValue)) {
                alert("This value has already been added.");
                inputElement.value = ''; // Clear the original input field
                return; // Exit if the value is already added
            }

            // Get the color of the selected tag
            const tagColor = option.getAttribute('data-color') || '#ccc'; // Default to grey if no color found

            // Create a new tag pill element
            const tagPill = document.createElement('span');
            tagPill.className = 'tag-pill';
            tagPill.setAttribute('data-value', selectedValue);
            tagPill.textContent = selectedValue;
            tagPill.style.backgroundColor = tagColor;

            // Create the remove button
            const removeBtn = document.createElement('button');
            removeBtn.className = 'remove-btn';
            removeBtn.textContent = 'X';
            removeBtn.onclick = () => {
                selectedTagsContainer.removeChild(tagPill);
                updateHiddenInput(); // Update hidden input when a tag is removed
            };

            // Append the remove button to the tag pill
            tagPill.appendChild(removeBtn);

            // Append the tag pill to the container
            selectedTagsContainer.appendChild(tagPill);

            // Update the hidden input field
            updateHiddenInput();

            // Clear the original input field
            inputElement.value = '';
        }

        function handleVipInput(inputElement) {
            const selectedValue = inputElement.value.trim();
            if (!selectedValue) {
                return; // Exit if no value is selected
            }

            const dataList = document.getElementById('vipList');
            const option = Array.from(dataList.options).find(opt => opt.value === selectedValue);

            // Check if the value is in the datalist options
            if (!option) {
                alert("Please select a valid VIP name from the list.");
                inputElement.value = ''; // Clear the original input field
                return;
            }

            const selectedTagsContainer = document.getElementById('selectedVipContainer');
            const currentTags = Array.from(selectedTagsContainer.children).map(tag => tag.getAttribute('data-value'));

            // Check if the value has already been added
            if (currentTags.includes(selectedValue)) {
                alert("This value has already been added.");
                inputElement.value = ''; // Clear the original input field
                return; // Exit if the value is already added
            }

            // Get the color of the selected tag
            const tagColor = option.getAttribute('data-color') || '#ccc'; // Default to grey if no color found

            // Create a new tag pill element
            const tagPill = document.createElement('span');
            tagPill.className = 'tag-pill';
            tagPill.setAttribute('data-value', selectedValue);
            tagPill.textContent = selectedValue;
            tagPill.style.backgroundColor = tagColor;

            // Create the remove button
            const removeBtn = document.createElement('button');
            removeBtn.className = 'remove-btn';
            removeBtn.textContent = 'X';
            removeBtn.onclick = () => {
                selectedTagsContainer.removeChild(tagPill);
                updateHiddenInputVip(); // Update hidden input when a tag is removed
            };

            // Append the remove button to the tag pill
            tagPill.appendChild(removeBtn);

            // Append the tag pill to the container
            selectedTagsContainer.appendChild(tagPill);

            // Update the hidden input field
            updateHiddenInputVip();

            // Clear the original input field
            inputElement.value = '';
        }

        function updateHiddenInputVip() {
            const selectedTagsContainer = document.getElementById('selectedVipContainer');
            const hiddenInput = document.getElementById('vipForm');
            const currentTags = Array.from(selectedTagsContainer.children).map(tag => tag.getAttribute('data-value'));
            hiddenInput.value = currentTags.join(';');
        }

        document.getElementById('taggingTopic' + i).addEventListener('keydown', function(event) {
            if (event.key === 'Enter' || event.key === 'Tab') {
                var input = this.value;
                var options = document.getElementById('tagTopicList').getElementsByTagName('option');
                var matchFound = false;
                for (var i = 0; i < options.length; i++) {
                    if (options[i].value === input) {
                        matchFound = true;
                        break;
                    }
                }
                if (!matchFound) {
                    event.preventDefault(); // Prevent default behavior (submitting form)
                    this.value = ''; // Clear input if no match found
                }
            }
        });

        function updateHiddenInput() {
            const selectedTagsContainer = document.getElementById('selectedTagsContainer');
            const hiddenInput = document.getElementById('tagTopic_');
            const currentTags = Array.from(selectedTagsContainer.children).map(tag => tag.getAttribute('data-value'));
            hiddenInput.value = currentTags.join(';');

            // Get the DataTables search input field
            const searchInput = $('#myTable_filter input');

            // Set the value of the search input field
            searchInput.val(currentTags.join(' '));

            // Trigger the DataTables search
            searchInput.trigger('input');
        }

        <?php
        $isNotArchive = $_SESSION['designation'] != 'archive';
        ?>

        function showForm(rowData) {
            var isNotArchive = <?php echo json_encode($isNotArchive); ?>;

            document.getElementById('form-container').style.display = 'block';
            document.getElementById('submission-records').style.display = 'none';
            document.getElementById('submitBy').value = rowData.submitBy;
            document.getElementById('date').value = rowData.date;
            document.getElementById('cutways').value = rowData.cutways;
            if (isNotArchive) {
                document.getElementById('folderName').value = rowData.folderName;
            }
            document.getElementById('functionName').value = rowData.functionName;
            document.getElementById('locationName').value = rowData.locationName;
            document.getElementById('tagTopicForm').value = rowData.tagTopic;
            document.getElementById('vipForm').value = rowData.vip;
            document.getElementById('vgPg').value = rowData.vgPg;
            document.getElementById('status').value = rowData.status;
            document.getElementById('remarks').value = rowData.staff_input;
            document.getElementById('requestId').value = rowData.requestId; // Set the request ID

            // Create tag pills based on the value in tagTopicForm
            const selectedTagsContainer = document.getElementById('selectedTagsContainerForm');
            selectedTagsContainer.innerHTML = ''; // Clear existing tags
            const tags = rowData.tagTopic.split(';').map(tag => tag.trim());
            tags.forEach(tag => {
                if (tag) {
                    const option = Array.from(document.getElementById('tagTopicListForm').options).find(opt => opt.value === tag);
                    const tagColor = option ? option.getAttribute('data-color') : '#ccc'; // Default to grey if no color found

                    // Create a new tag pill element
                    const tagPill = document.createElement('span');
                    tagPill.className = 'tag-pill';
                    tagPill.setAttribute('data-value', tag);
                    tagPill.textContent = tag;
                    tagPill.style.backgroundColor = tagColor;

                    // Create the remove button
                    const removeBtn = document.createElement('button');
                    removeBtn.className = 'remove-btn';
                    removeBtn.textContent = 'X';
                    removeBtn.onclick = () => {
                        selectedTagsContainer.removeChild(tagPill);
                        updateHiddenInputForm(); // Update hidden input when a tag is removed
                    };

                    // Append the remove button to the tag pill
                    tagPill.appendChild(removeBtn);

                    // Append the tag pill to the container
                    selectedTagsContainer.appendChild(tagPill);
                }
            });

            // Create tag pills based on the value in tagTopicForm
            const selectedVipContainer = document.getElementById('selectedVipContainer');
            selectedVipContainer.innerHTML = ''; // Clear existing tags
            const vipNames = rowData.vip.split(';').map(vipName => vipName.trim());
            vipNames.forEach(vipName => {
                if (vipName) {
                    const option = Array.from(document.getElementById('vipList').options).find(opt => opt.value === vipName);
                    const tagColor = option ? option.getAttribute('data-color') : '#ccc'; // Default to grey if no color found

                    // Create a new tag pill element
                    const tagPill = document.createElement('span');
                    tagPill.className = 'tag-pill';
                    tagPill.setAttribute('data-value', vipName);
                    tagPill.textContent = vipName;
                    tagPill.style.backgroundColor = tagColor;

                    // Create the remove button
                    const removeBtn = document.createElement('button');
                    removeBtn.className = 'remove-btn';
                    removeBtn.textContent = 'X';
                    removeBtn.onclick = () => {
                        selectedVipContainer.removeChild(tagPill);
                        updateHiddenInputVip(); // Update hidden input when a tag is removed
                    };

                    // Append the remove button to the tag pill
                    tagPill.appendChild(removeBtn);

                    // Append the tag pill to the container
                    selectedVipContainer.appendChild(tagPill);
                }
            });

            updateHiddenInputForm();
            updateHiddenInputVip();
        }

        function backBtn() {
            document.getElementById('form-container').style.display = 'none';
            document.getElementById('submission-records').style.display = 'block';
            document.getElementById('selectedTagsContainerForm').innerHTML = ''; // Empty the div
        }
    </script>
</body>

</html>