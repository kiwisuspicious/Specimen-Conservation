<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="favicon.png" />
    <title>Specimen Conservation Report</title>
    <style>
        /* General styles */
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
            background-color: #f4f4f9;
        }

        header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #444;
        }

        .logo {
            width: 100px;
            height: auto;
        }

        h1 {
            font-size: 2rem;
            margin: 0;
            color: #444;
        }

        .record-container {
            background-color: #fff;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        .record-header {
            font-size: 1.4rem;
            font-weight: bold;
            color: #222;
            margin-bottom: 20px;
            text-align: center;
            padding: 10px;
            background-color: #e6e9f0;
            border-radius: 6px;
        }

        .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            flex-wrap: wrap;
            width: 100%;
        }

        .row div {
            width: 48%;
        }

        .row div label {
            display: block;
            font-weight: bold;
            font-size: 0.9rem;
            color: #555;
            margin-bottom: 5px;
        }

        .row div span {
            display: block;
            font-size: 1rem;
            color: #222;
            padding: 8px;
            background-color: #f9f9fc;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .full-width {
            width: 100%;
        }

        .image-section {
            margin-top: 20px;
        }

        .image-section h3 {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 10px;
            color: #222;
        }

        .image-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            /* Space between images */
            justify-content: start;
            /* Align images to the left */
        }

        .image-grid img {
            width: calc(50% - 10px);
            /* Set fixed width for images */
            height: calc(50% - 10px);
            /* Set fixed height for images */
            object-fit: cover;
            /* Maintain aspect ratio */
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .no-images {
            font-size: 0.9rem;
            color: #555;
            text-align: center;
            margin-top: 10px;
        }

        .signature-section {
            border-bottom: 1px solid #333;
            /* Space between the top of the section and the word Signature */
            padding-bottom: 5px;
            /* Gap between the word "Signature" and the line */
            font-size: 0.9rem;
            color: #555;
            width: 250px;
            margin-left: auto;
        }

        .name-section {
            padding-top: 10px;
            /* Space between the top of the section and the word Signature */
            font-size: 0.9rem;
            color: #555;
            width: 250px;
            margin-left: auto;
        }

        @media print {
            body {
                margin: 0;
                padding: 10px;
                background-color: #fff;
            }

            .no-print {
                display: none;
            }

            /* Add page break before the inspector and remarks section */
            .row .full-width {
                page-break-before: always;
                /* Force a page break before the inspector and remarks section */
            }

            /* Optional: Add page break after the signature section if needed */
            .signature-section {
                page-break-after: always;
                /* Force a page break after the signature section */
            }
        }

        footer {
            text-align: center;
            font-size: 0.8rem;
            color: #666;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <header>
        <img src="favicon.png" alt="Company Logo" class="logo">
        <h1>Specimen Conservation Report</h1>
    </header>

    <button class="no-print" onclick="window.print()">Print This Page</button>

    <?php
    // Fetch data from the application table
    require 'includes/config.php'; // Your database connection file
    $appID = isset($_GET['appID']) ? htmlspecialchars($_GET['appID']) : '';
    $pdo = pdo_connect_mysql();

    $query = "SELECT * FROM application WHERE appID = :appID";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':appID', $appID, PDO::PARAM_STR); // Bind as string
    $stmt->execute();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Map the numeric condition to descriptive text
        $conditionMap = [
            4 => "Excellent",
            3 => "Good",
            2 => "Fair",
            1 => "Poor"
        ];
        $condition = isset($conditionMap[$row['speccond']]) ? $conditionMap[$row['speccond']] : "Unknown Condition";

        echo "
            <div class='record-header'>Application ID: {$row['appID']}</div>
            <div class='row'>
                <div>
                    <label>Catalog No:</label>
                    <span>{$row['catnum']}</span>
                </div>
                <div>
                    <label>Specimen Name:</label>
                    <span>{$row['specname']}</span>
                </div>
            </div>
            <div class='row'>
                <div>
                    <label>Location:</label>
                    <span>{$row['location']}</span>
                </div>
                <div>
                    <label>Examination:</label>
                    <span>{$row['examination']}</span>
                </div>
            </div>
            <div class='row'>
                <div>
                    <label>Condition:</label>
                    <span>{$condition}</span>
                </div>
                <div>
                    <label>Material:</label>
                    <span>{$row['material']}</span>
                </div>
            </div>
            <div class='row full-width'>
                <div>
                    <label>Work Method Statement:</label>
                    <span>" . htmlspecialchars($row['workmeth']) . "</span>
                </div>
                <div>
                    <label>Submit Date:</label>
                    <span>" . htmlspecialchars($row['submit_date']) . "</span>
                </div>
            </div>
            <div class='row'>
                <div>
                    <label>Inspector:</label>
                    <span>{$row['inspectname']}</span>
                </div>
                <div>
                    <label>Remarks:</label>
                    <span>" . htmlspecialchars($row['remarks']) . "</span>
                </div>";

        // Before Images Section
        echo "<div class='row'>
        <div style = 'width: 100%'>
            <h3>Before Images</h3>";
        $beforeImagesDir = "uploads/" . $appID . "/Before";
        if (is_dir($beforeImagesDir)) {
            $images = glob($beforeImagesDir . "/*.{jpg,jpeg,png,gif,JPG,JPEG,PNG,GIF}", GLOB_BRACE);
            if (!empty($images)) {
                echo "<div class='image-grid'>";
                foreach ($images as $image) {
                    echo '<img src="' . htmlspecialchars($image) . '" alt="Before Image">';
                }
                echo "</div>";
            } else {
                echo "<p class='no-images'>No images available in the 'Before' folder.</p>";
            }
        } else {
            echo "<p class='no-images'>Directory does not exist for the specified Application ID.</p>";
        }
        echo "</div></div>"; // End of Before Images Row

        // After Images Section
        echo "<div class='row'>
        <div style = 'width: 100%'>
            <h3>After Images</h3>";
        $afterImagesDir = "uploads/" . $appID . "/After";
        if (is_dir($afterImagesDir)) {
            $images = glob($afterImagesDir . "/*.{jpg,jpeg,png,gif,JPG,JPEG,PNG,GIF}", GLOB_BRACE);
            if (!empty($images)) {
                echo "<div class='image-grid'>";
                foreach ($images as $image) {
                    echo '<img src="' . htmlspecialchars($image) . '" alt="After Image">';
                }
                echo "</div>";
            } else {
                echo "<p class='no-images'>No images available in the 'After' folder.</p>";
            }
        } else {
            echo "<p class='no-images'>No images available in the 'After' folder.</p>";
        }
        echo "</div></div>"; // End of After Images Row

        // Signature Section
        echo "<div class='row'>";
        echo "</div>";
        echo "<div class='signature-section'>Signature:<br></div>";
    }
    ?>

</body>

</html>