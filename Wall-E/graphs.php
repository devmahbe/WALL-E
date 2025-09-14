<?php
require_once 'includes/header.php';
require_once 'includes/db.php';

// Fetch last 50 readings
$sql = "SELECT * FROM sensor_data ORDER BY timestamp DESC LIMIT 50";
$result = $conn->query($sql);

// Prepare data for JSON
$data = array();
while ($row = $result->fetch_assoc()) {
    $data[] = array(
        'timestamp' => $row['timestamp'],
        'temperature' => floatval($row['temperature']),
        'humidity' => floatval($row['humidity']),
        'mq2_lpg' => floatval($row['mq2_lpg']),
        'mq2_methane' => floatval($row['mq2_methane']),
        'mq135_nh3' => floatval($row['mq135_nh3']),
        'mq135_co2' => floatval($row['mq135_co2']),
        'mq7_co' => floatval($row['mq7_co']),
        'mq8_h2' => floatval($row['mq8_h2'])
    );
}
$conn->close();

// Reverse the array to show oldest to newest
$data = array_reverse($data);
?>

<div class="graphs-page">
    <h1>Sensor Data Graphs</h1>

    <div class="card">
        <div class="sensor-toggles">
            <h2>Select Sensors to Display</h2>
            <div class="toggle-grid">
                <label class="toggle">
                    <input type="checkbox" data-sensor="temperature" checked>
                    <span class="toggle-label">Temperature</span>
                </label>
                <label class="toggle">
                    <input type="checkbox" data-sensor="humidity" checked>
                    <span class="toggle-label">Humidity</span>
                </label>
                <label class="toggle">
                    <input type="checkbox" data-sensor="mq2_lpg" checked>
                    <span class="toggle-label">LPG</span>
                </label>
                <label class="toggle">
                    <input type="checkbox" data-sensor="mq2_methane" checked>
                    <span class="toggle-label">Methane</span>
                </label>
                <label class="toggle">
                    <input type="checkbox" data-sensor="mq135_nh3" checked>
                    <span class="toggle-label">NH3</span>
                </label>
                <label class="toggle">
                    <input type="checkbox" data-sensor="mq135_co2" checked>
                    <span class="toggle-label">CO2</span>
                </label>
                <label class="toggle">
                    <input type="checkbox" data-sensor="mq7_co" checked>
                    <span class="toggle-label">CO</span>
                </label>
                <label class="toggle">
                    <input type="checkbox" data-sensor="mq8_h2" checked>
                    <span class="toggle-label">H2</span>
                </label>
            </div>
        </div>

        <div class="chart-container">
            <canvas id="sensorChart"></canvas>
        </div>
    </div>
</div>

<style>
.graphs-page {
    padding: 2rem 0;
}

.graphs-page h1 {
    text-align: center;
    color: var(--primary-color);
    margin-bottom: 2rem;
}

.sensor-toggles {
    margin-bottom: 2rem;
}

.sensor-toggles h2 {
    margin-bottom: 1rem;
    font-size: 1.2rem;
    color: var(--secondary-color);
}

.toggle-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
}

.toggle {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}

.toggle input[type="checkbox"] {
    width: 1.2rem;
    height: 1.2rem;
}

.toggle-label {
    font-weight: 500;
}

.chart-container {
    position: relative;
    height: 60vh;
    width: 100%;
}

@media (max-width: 768px) {
    .toggle-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .chart-container {
        height: 50vh;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Sensor data from PHP
const sensorData = <?php echo json_encode($data); ?>;

// Define colors for each sensor
const sensorColors = {
    temperature: '#e63946',
    humidity: '#457b9d',
    mq2_lpg: '#2a9d8f',
    mq2_methane: '#e9c46a',
    mq135_nh3: '#f4a261',
    mq135_co2: '#264653',
    mq7_co: '#bc6c25',
    mq8_h2: '#6d597a'
};

// Define labels for each sensor
const sensorLabels = {
    temperature: 'Temperature (°C)',
    humidity: 'Humidity (%)',
    mq2_lpg: 'LPG (PPM)',
    mq2_methane: 'Methane (PPM)',
    mq135_nh3: 'NH3 (PPM)',
    mq135_co2: 'CO2 (PPM)',
    mq7_co: 'CO (PPM)',
    mq8_h2: 'H2 (PPM)'
};

// Prepare chart datasets
const datasets = Object.keys(sensorColors).map(sensor => ({
    label: sensorLabels[sensor],
    data: sensorData.map(reading => reading[sensor]),
    borderColor: sensorColors[sensor],
    backgroundColor: sensorColors[sensor] + '20',
    borderWidth: 2,
    pointRadius: 1,
    pointHoverRadius: 5,
    fill: false,
    tension: 0.4
}));

// Create chart
const ctx = document.getElementById('sensorChart').getContext('2d');
const chart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: sensorData.map(reading => {
            const date = new Date(reading.timestamp);
            return date.toLocaleTimeString();
        }),
        datasets: datasets
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            intersect: false,
            mode: 'index'
        },
        plugins: {
            title: {
                display: true,
                text: 'Sensor Readings Over Time'
            },
            tooltip: {
                enabled: true,
                mode: 'index',
                intersect: false
            }
        },
        scales: {
            x: {
                display: true,
                title: {
                    display: true,
                    text: 'Time'
                }
            },
            y: {
                display: true,
                title: {
                    display: true,
                    text: 'Value'
                }
            }
        }
    }
});

// Handle checkbox changes
document.querySelectorAll('.toggle input[type="checkbox"]').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const sensor = this.dataset.sensor;
        const dataset = chart.data.datasets.find(ds => ds.label === sensorLabels[sensor]);
        dataset.hidden = !this.checked;
        chart.update();
    });
});

// Auto-refresh every 30 seconds
setInterval(() => {
    window.location.reload();
}, 30000);
</script>

<?php require_once 'includes/footer.php'; ?> 