<?php
session_start();
error_reporting(E_ALL); // Enable all errors for debugging
include('includes/config.php');
$pdo_login = pdo_connect_mysql2();

if (isset($_POST['signin'])) {
    $uname = $_POST['username'];
    $password = md5($_POST['password']);
    $sql = "SELECT * FROM tblemployee WHERE email=:uname AND password=:password";
    $query = $pdo_login->prepare($sql);
    $query->bindParam(':uname', $uname, PDO::PARAM_STR);
    $query->bindParam(':password', $password, PDO::PARAM_STR);
    $query->execute();
    $results = $query->fetchAll(PDO::FETCH_OBJ);
    if ($query->rowCount() > 0) {
        foreach ($results as $result) {
            $status = $result->status;
            $_SESSION['eid'] = $result->id;
            $_SESSION['stafflogin'] = $_POST['username'];
            $_SESSION['division'] = $result->division;
            $_SESSION['name'] = $result->name;
            $_SESSION['designation'] = $result->designation;
            $_SESSION['unit'] = $result->unit;
            $_SESSION['email'] = $result->email;

            if ($status == 0) {
                $msg = "Your account is inactive. Please contact IT desk.";
            } else {
                // Redirect to the home page or wherever you want to send the user after login
                header('Location: https://localhost/vg/dashboard/index.php');
                exit();
            }
        }
    } else {
        echo '<script>window.alert("The email and password you entered did not match our records. Please double-check and try again."); window.location.href="login.php";</script>';
    }
}
?>

<!doctype html>
<html lang="en">

<head>
    <title>Medianest</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="https://fonts.googleapis.com/css?family=Lato:300,400,700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="favicon.png" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="img js-fullheight" style="background-image: url(images/vg.jpg);">
    <section class="ftco-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 text-center mb-5">
                    <h2 class="heading-section">Medianest</h2>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-4">
                    <div class="login-wrap p-0">
                        <form action="#" class="signin-form" method="post">
                            <div class="form-group">
                                <input type="text" class="form-control" id="username" name="username" placeholder="E-mail" required>
                            </div>
                            <div class="form-group">
                                <input id="password" name="password" type="password" class="form-control" placeholder="Password" required>
                                <span toggle="#password-field" class="fa fa-fw fa-eye field-icon toggle-password"></span>
                            </div>
                            <div class="form-group">
                                <button type="submit" name="signin" class="form-control btn btn-primary submit px-3" style="background: linear-gradient(90deg, #333333, #000000); color: #000000;">Login</button>
                            </div>
                            <div class="form-group text-center">
                                <a href="https://it.smg.my/index" style="color: #fff;">IT Help Desk</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="js/jquery.min.js"></script>
    <script src="js/popper.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/main.js"></script>

</body>

</html>