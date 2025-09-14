<?php
require_once 'includes/header.php';
require_once 'includes/db.php';

// Fetch last 10 data entries
$sql = "SELECT humidity, temperature, mq2_lpg, mq2_methane, mq135_nh3, mq135_co2, mq7_co, mq8_h2 
        FROM sensor_data ORDER BY id DESC LIMIT 10";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    die("No data available to analyze.");
}

// Initialize sums
$sum = [
    'humidity' => 0,
    'temperature' => 0,
    'mq2_lpg' => 0,
    'mq2_methane' => 0,
    'mq135_nh3' => 0,
    'mq135_co2' => 0,
    'mq7_co' => 0,
    'mq8_h2' => 0,
];

$count = 0;
while($row = $result->fetch_assoc()) {
    $count++;
    foreach ($sum as $key => $value) {
        $sum[$key] += $row[$key];
    }
}

// Calculate averages
$avg = [];
foreach ($sum as $key => $value) {
    $avg[$key] = $value / $count;
}

// Thresholds and checks
$thresholds = [
    'humidity' => ['min' => 30, 'max' => 90, 'label' => 'Humidity (%)', 'reason' => 'Humidity should be between 30% and 60%.'],
    'temperature' => ['min' => 18, 'max' => 37, 'label' => 'Temperature (°C)', 'reason' => 'Temperature should be between 18°C and 28°C.'],
    'mq2_lpg' => ['max' => 100, 'label' => 'MQ2 (LPG) PPM', 'reason' => 'LPG levels should be below 100 PPM.'],
    'mq2_methane' => ['max' => 100, 'label' => 'MQ2 (Methane) PPM', 'reason' => 'Methane levels should be below 100 PPM.'],
    'mq135_nh3' => ['max' => 50, 'label' => 'MQ135 (NH3) PPM', 'reason' => 'Ammonia (NH3) levels should be below 50 PPM.'],
    'mq135_co2' => ['max' => 1000, 'label' => 'MQ135 (CO2) PPM', 'reason' => 'CO2 levels should be below 1000 PPM.'],
    'mq7_co' => ['max' => 50, 'label' => 'MQ7 (CO) PPM', 'reason' => 'Carbon Monoxide (CO) levels should be below 50 PPM.'],
    'mq8_h2' => ['max' => 100, 'label' => 'MQ8 (H2) PPM', 'reason' => 'Hydrogen (H2) levels should be below 100 PPM.'],
];

// Check each parameter and collect fail reasons
$fail_reasons = [];
foreach ($thresholds as $key => $thresh) {
    $value = $avg[$key];
    $min = $thresh['min'] ?? null;
    $max = $thresh['max'] ?? null;
    
    if ($min !== null && $value < $min) {
        $fail_reasons[] = "{$thresh['label']} is too low (" . number_format($value, 1) . "). {$thresh['reason']}";
    }
    if ($max !== null && $value > $max) {
        $fail_reasons[] = "{$thresh['label']} is too high (" . number_format($value, 1) . "). {$thresh['reason']}";
    }
}

$is_livable = count($fail_reasons) === 0;
$conn->close();
?>

<div class="environment-status">
    <h1>Environment Status Analysis</h1>
    
    <div class="status-grid">
        <div class="card">
            <h2>Current Status</h2>
            <div class="status-indicator <?php echo $is_livable ? 'status-success' : 'status-warning'; ?>">
                <?php echo $is_livable 
                    ? "Environment is Safe for Human Habitation ✅" 
                    : "Warning! Environment Not Suitable for Humans ⚠️"; 
                ?>
            </div>
            
            <?php if (!$is_livable): ?>
                <div class="warnings">
                    <h3>Issues Detected:</h3>
                    <ul>
                        <?php foreach ($fail_reasons as $reason): ?>
                            <li><?php echo htmlspecialchars($reason); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Average Readings (Last 10 Measurements)</h2>
            <div class="readings-grid">
                <?php foreach ($avg as $key => $value): ?>
                    <div class="reading-item">
                        <span class="reading-label"><?php echo htmlspecialchars($thresholds[$key]['label']); ?></span>
                        <span class="reading-value <?php 
                            $min = $thresholds[$key]['min'] ?? null;
                            $max = $thresholds[$key]['max'] ?? null;
                            if (($min !== null && $value < $min) || ($max !== null && $value > $max)) {
                                echo 'warning';
                            }
                        ?>"><?php echo number_format($value, 1); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card">
            <h2>Actions</h2>
            <div class="actions-grid">
                <button class="btn btn-primary" onclick="window.location.reload()">
                    🔄 Refresh Analysis
                </button>
                <a href="view_data.php" class="btn btn-primary">
                    📊 View Raw Data
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.environment-status {
    padding: 2rem 0;
}

.environment-status h1 {
    text-align: center;
    color: var(--primary-color);
    margin-bottom: 2rem;
}

.status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
}

.status-indicator {
    text-align: center;
    padding: 1.5rem;
    border-radius: 8px;
    font-size: 1.2rem;
    font-weight: 600;
    margin: 1.5rem 0;
}

.warnings {
    margin-top: 1.5rem;
    padding: 1.5rem;
    background: #fff3f3;
    border-radius: 8px;
}

.warnings h3 {
    color: var(--warning-color);
    margin-bottom: 1rem;
}

.warnings ul {
    list-style-type: none;
}

.warnings li {
    margin-bottom: 0.5rem;
    padding-left: 1.5rem;
    position: relative;
}

.warnings li::before {
    content: "⚠️";
    position: absolute;
    left: 0;
}

.readings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin: 1.5rem 0;
}

.reading-item {
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.reading-label {
    color: var(--secondary-color);
    font-weight: 600;
}

.reading-value {
    font-size: 1.25rem;
    font-weight: 600;
}

.reading-value.warning {
    color: var(--warning-color);
}

.actions-grid {
    display: grid;
    gap: 1rem;
    margin: 1.5rem 0;
}

@media (max-width: 768px) {
    .status-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>
