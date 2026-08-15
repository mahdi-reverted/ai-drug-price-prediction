<!DOCTYPE html>
<html>
<head>
    <title>AI Drug Price Recommendation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">

<div class="container" style="max-width: 750px;">

    <h2 class="mb-4">AI-Based Drug Price Prediction and
        Recommendation System for Bangladesh</h2>

    <form method="POST" class="border p-4 bg-white rounded mb-4">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Generic Name</label>
                <input type="text" class="form-control" name="generic_name" value="Paracetamol" required>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Strength</label>
                <input type="text" class="form-control" name="strength" value="500 mg" required>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Manufacturer</label>
                <input type="text" class="form-control" name="manufacturer" value="Beximco" required>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Pack Size</label>
                <input type="text" class="form-control" name="pack_size" value="1 x 1 With Combo Pack" required>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Dosage Description</label>
                <input type="text" class="form-control" name="dosage_description" value="Tablet">
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Use For</label>
                <textarea class="form-control" name="use_for" rows="1">Human</textarea>
            </div>
        </div>

        <button type="submit" class="btn btn-primary mt-2">Predict Price</button>
    </form>

    <?php
    if ($_SERVER["REQUEST_METHOD"] === "POST") {

        $data = [
                "generic_name" => $_POST["generic_name"],
                "strength" => $_POST["strength"],
                "pack_size" => $_POST["pack_size"],
                "manufacturer" => $_POST["manufacturer"],
                "dosage_description" => $_POST["dosage_description"],
                "use_for" => $_POST["use_for"]
        ];

        $ch = curl_init("http://127.0.0.1:8000/predict");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        if ($response === false) {
            echo '<div class="alert alert-danger">';
            echo "Could not connect to FastAPI: " . curl_error($ch);
            echo '</div>';
        } else {
            $result = json_decode($response, true);
            if (isset($result["ai_prediction"])) {
                ?>

                <div class="border p-4 bg-white rounded">
                    <h4 class="text-primary">Your Input</h4>
                    <div class="p-3 border rounded bg-light mb-3">
                        <p class="mb-1"><strong>Medicine:</strong> <?php echo htmlspecialchars($result["medicine"]["generic_name"]); ?></p>
                        <p class="mb-1"><strong>Strength:</strong> <?php echo htmlspecialchars($result["medicine"]["strength"]); ?></p>
                        <p class="mb-1"><strong>Dosage Description:</strong> <?php echo htmlspecialchars($result["medicine"]["dosage_description"]); ?></p>
                        <p class="mb-0"><strong>Use For:</strong> <?php echo htmlspecialchars($result["medicine"]["use_for"]); ?></p>
                    </div>

                    <hr>

                    <h4 class="text-primary">AI Model Performance</h4>
                    <div class="row text-center my-3">
                        <div class="col-3"><div class="p-2 border bg-light"><small>MAE</small><br><strong>৳<?= number_format($result["model_performance"]["mae"], 2) ?></strong></div></div>
                        <div class="col-3"><div class="p-2 border bg-light"><small>MSE</small><br><strong><?= number_format($result["model_performance"]["mse"], 2) ?></strong></div></div>
                        <div class="col-3"><div class="p-2 border bg-light"><small>RMSE</small><br><strong>৳<?= number_format($result["model_performance"]["rmse"], 2) ?></strong></div></div>
                        <div class="col-3"><div class="p-2 border bg-light"><small>R² Score</small><br><strong><?= number_format($result["model_performance"]["r2"], 4) ?></strong></div></div>
                    </div>

                    <hr>


                    <h4 class="text-primary">AI Predicted Price</h4>
                    <div class="alert alert-info text-center fs-4 fw-bold">
                        ৳<?php echo number_format($result["ai_prediction"], 2); ?>
                    </div>

                    <h4 class="text-primary">Market Analysis</h4>
                    <p>Comparable Medicines: <strong><?php echo $result["market_analysis"]["comparable_medicines"]; ?></strong></p>
                    <p>Minimum Price: <strong>৳<?php echo number_format($result["market_analysis"]["minimum_price"], 2); ?></strong></p>
                    <p>Median Price: <strong>৳<?php echo number_format($result["market_analysis"]["median_price"], 2); ?></strong></p>
                    <p>Maximum Price: <strong>৳<?php echo number_format($result["market_analysis"]["maximum_price"], 2); ?></strong></p>

                    <hr>

                    <h3 class="text-success text-center">Recommended Competitive Price</h3>
                    <div class="alert alert-success text-center fs-3 fw-bold">
                        ৳<?php echo number_format($result["recommended_price"], 2); ?>
                    </div>

                </div>

                <?php
            } else {
                echo '<div class="alert alert-warning">';
                echo "API returned an error:";
                echo '<pre>';
                print_r($result);
                echo '</pre>';
                echo '</div>';
            }
        }
        curl_close($ch);
    }
    ?>

</div>

</body>
</html>