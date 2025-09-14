<?php
require_once 'includes/header.php';
require_once 'includes/db.php';

// Get latest sensor readings
$sql = "SELECT * FROM sensor_data ORDER BY id DESC LIMIT 1";
$result = $conn->query($sql);
$latest = $result->fetch_assoc();

// Get total readings count
$sql = "SELECT COUNT(*) as total FROM sensor_data";
$result = $conn->query($sql);
$total = $result->fetch_assoc()['total'];

$conn->close();
?>

<div class="dashboard">
    <h1 class="dashboard-title">Mars Rover Monitoring Dashboard</h1>
    
    <div class="dashboard-grid">
        <div class="card">
            <h2>Latest Readings</h2>
            <?php if ($latest): ?>
                <div class="readings-grid">
                    <div class="reading-item">
                        <span class="reading-label">Temperature</span>
                        <span class="reading-value"><?php echo number_format($latest['temperature'], 1); ?>°C</span>
                    </div>
                    <div class="reading-item">
                        <span class="reading-label">Humidity</span>
                        <span class="reading-value"><?php echo number_format($latest['humidity'], 1); ?>%</span>
                    </div>
                    <div class="reading-item">
                        <span class="reading-label">CO2 Level</span>
                        <span class="reading-value"><?php echo number_format($latest['mq135_co2'], 1); ?> PPM</span>
                    </div>
                    <div class="reading-item">
                        <span class="reading-label">CO Level</span>
                        <span class="reading-value"><?php echo number_format($latest['mq7_co'], 1); ?> PPM</span>
                    </div>
                </div>
                <p class="timestamp">Last updated: <?php echo $latest['timestamp']; ?></p>
            <?php else: ?>
                <p>No sensor data available</p>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Quick Stats</h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-label">Total Readings</span>
                    <span class="stat-value"><?php echo number_format($total); ?></span>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>Quick Actions</h2>
            <div class="actions-grid">
                <a href="view_data.php" class="btn btn-primary">View All Data</a>
                <a href="environment_status.php" class="btn btn-primary">Check Environment Status</a>
            </div>
        </div>
    </div>
</div>

<style>
.dashboard {
    padding: 2rem 0;
}

.dashboard-title {
    text-align: center;
    color: var(--primary-color);
    margin-bottom: 2rem;
    font-size: 2.5rem;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
}

.readings-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
    margin: 1.5rem 0;
}

.reading-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.reading-label {
    color: var(--secondary-color);
    font-weight: 600;
}

.reading-value {
    font-size: 1.5rem;
    font-weight: 600;
}

.timestamp {
    color: #666;
    font-size: 0.9rem;
    margin-top: 1rem;
}

.stats-grid {
    margin: 1.5rem 0;
}

.stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 0;
    border-bottom: 1px solid #eee;
}

.stat-label {
    color: var(--secondary-color);
    font-weight: 600;
}

.stat-value {
    font-size: 1.25rem;
    font-weight: 600;
}

.actions-grid {
    display: grid;
    gap: 1rem;
    margin: 1.5rem 0;
}
</style>

<?php require_once 'includes/footer.php'; ?> 