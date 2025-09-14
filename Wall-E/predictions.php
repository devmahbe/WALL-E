<?php
require_once 'includes/header.php';

// Function to run Python script and get predictions
function getPredictions() {
    $output = [];
    $return_var = 0;
    exec('python predict_safety.py 2>&1', $output, $return_var);
    return ['output' => $output, 'status' => $return_var];
}

// Only run predictions if button is clicked
$prediction_results = isset($_POST['run_prediction']) ? getPredictions() : null;
?>

<div class="predictions-page">
    <h1>Environment Safety Predictions</h1>

    <!-- Prediction Button -->
    <div class="prediction-controls">
        <form method="post" id="predictionForm">
            <button type="submit" name="run_prediction" class="btn btn-primary prediction-btn" onclick="showPredictionSteps()">
                🔮 Run New Prediction
            </button>
        </form>
    </div>

    <!-- Prediction Steps Modal -->
    <div id="predictionModal" class="modal">
        <div class="modal-content">
            <h2>Prediction in Progress</h2>
            <div class="steps-container">
                <div class="prediction-step" id="step1">
                    <div class="step-icon">📊</div>
                    <div class="step-content">
                        <h3>Fetching Historical Data</h3>
                        <p>Loading sensor data from database...</p>
                    </div>
                    <div class="step-status">⏳</div>
                </div>
                <div class="prediction-step" id="step2">
                    <div class="step-icon">🤖</div>
                    <div class="step-content">
                        <h3>Training Models</h3>
                        <p>Training machine learning models...</p>
                    </div>
                    <div class="step-status">⏳</div>
                </div>
                <div class="prediction-step" id="step3">
                    <div class="step-icon">🔮</div>
                    <div class="step-content">
                        <h3>Making Predictions</h3>
                        <p>Predicting future sensor values...</p>
                    </div>
                    <div class="step-status">⏳</div>
                </div>
                <div class="prediction-step" id="step4">
                    <div class="step-icon">⚖️</div>
                    <div class="step-content">
                        <h3>Analyzing Safety</h3>
                        <p>Evaluating environmental conditions...</p>
                    </div>
                    <div class="step-status">⏳</div>
                </div>
            </div>
        </div>
    </div>

    <div class="predictions-grid">
        <!-- Prediction Status -->
        <div class="card">
            <h2>Prediction Results</h2>
            <div class="prediction-output">
                <?php if ($prediction_results): ?>
                    <?php if ($prediction_results['status'] === 0): ?>
                        <?php 
                        $safe_duration = null;
                        $has_hazards = false;
                        $hazard_details = [];
                        
                        foreach ($prediction_results['output'] as $line) {
                            // Skip all technical and process messages
                            if (strpos($line, "df = ") !== false ||
                                strpos($line, "UserWarning:") !== false ||
                                strpos($line, "Step ") === 0 ||
                                strpos($line, "Safety Analysis Results:") !== false ||
                                strpos($line, "Saving models") !== false ||
                                strpos($line, "No immediate hazards") !== false) {
                                continue;
                            }
                            
                            // Extract safe duration but don't display the raw message
                            if (strpos($line, "Safe duration:") !== false) {
                                $safe_duration = floatval(str_replace("Safe duration: ", "", $line));
                                continue;
                            }
                            
                            // Check for hazards but don't display the header
                            if (strpos($line, "Potential hazards detected:") !== false) {
                                $has_hazards = true;
                                continue;
                            }
                            
                            // Collect hazard details if any
                            if ((strpos($line, "At ") === 0 || strpos($line, "- ") === 0)) {
                                $hazard_details[] = $line;
                            }
                        }
                        ?>
                        
                        <?php if ($safe_duration !== null): ?>
                            <div class="prediction-summary">
                                <!-- Three-block top row -->
                                <div class="top-row">
                                    <!-- Safety Status -->
                                    <div class="info-block mini-status <?php echo $has_hazards ? 'hazard' : 'safe'; ?>">
                                        <span class="status-emoji"><?php echo $has_hazards ? '⚠️' : '✅'; ?></span>
                                        <div class="status-text">
                                            <span class="status-label"><?php echo $has_hazards ? 'Not Safe' : 'Safe'; ?></span>
                                            <span class="duration-label"><?php echo $has_hazards ? 
                                                number_format($safe_duration, 1) . "h safe" : 
                                                "24h safe"; ?></span>
                                        </div>
                                    </div>

                                    <!-- Accuracy -->
                                    <?php
                                    $prediction_data = json_decode(file_get_contents('prediction_data.json'), true);
                                    if (isset($prediction_data['overall_accuracy'])):
                                    ?>
                                    <div class="info-block mini-accuracy">
                                        <div class="accuracy-text">
                                            <span class="block-title">Accuracy</span>
                                            <span class="accuracy-value"><?php echo number_format($prediction_data['overall_accuracy'], 1); ?>%</span>
                                        </div>
                                        <div class="mini-bar">
                                            <div class="bar-fill" style="width: <?php echo $prediction_data['overall_accuracy']; ?>%"></div>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <!-- 24h Timeline Summary -->
                                    <div class="info-block mini-timeline-summary">
                                        <span class="block-title">24h Timeline</span>
                                        <div class="timeline-summary-content">
                                            <?php if (isset($prediction_data['critical_points']) && !empty($prediction_data['critical_points'])): ?>
                                                <span class="warning-count"><?php echo count($prediction_data['critical_points']); ?> warnings</span>
                                            <?php else: ?>
                                                <span class="safe-status">All clear</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Sensor Groups -->
                                <?php if (isset($prediction_data['accuracy_metrics'])): ?>
                                <div class="sensor-groups">
                                    <!-- Gas Sensors Group -->
                                    <div class="sensor-group">
                                        <h3 class="group-title">Gas Sensors</h3>
                                        <div class="sensor-grid">
                                            <?php
                                            $gas_sensors = array('co2', 'co', 'ch4', 'c2h2');
                                            foreach ($prediction_data['accuracy_metrics'] as $sensor => $metrics):
                                                if (in_array(strtolower($sensor), $gas_sensors)):
                                            ?>
                                                <div class="sensor-chip">
                                                    <div class="sensor-info">
                                                        <span class="sensor-name"><?php echo ucwords(str_replace('_', ' ', $sensor)); ?></span>
                                                        <span class="sensor-accuracy"><?php echo number_format($metrics['accuracy'], 1); ?>%</span>
                                                    </div>
                                                    <div class="mini-bar">
                                                        <div class="bar-fill" style="width: <?php echo $metrics['accuracy']; ?>%"></div>
                                                    </div>
                                                </div>
                                            <?php 
                                                endif;
                                            endforeach; 
                                            ?>
                                        </div>
                                    </div>

                                    <!-- Environmental Sensors Group -->
                                    <div class="sensor-group">
                                        <h3 class="group-title">Environmental Sensors</h3>
                                        <div class="sensor-grid">
                                            <?php
                                            foreach ($prediction_data['accuracy_metrics'] as $sensor => $metrics):
                                                if (!in_array(strtolower($sensor), $gas_sensors)):
                                            ?>
                                                <div class="sensor-chip">
                                                    <div class="sensor-info">
                                                        <span class="sensor-name"><?php echo ucwords(str_replace('_', ' ', $sensor)); ?></span>
                                                        <span class="sensor-accuracy"><?php echo number_format($metrics['accuracy'], 1); ?>%</span>
                                                    </div>
                                                    <div class="mini-bar">
                                                        <div class="bar-fill" style="width: <?php echo $metrics['accuracy']; ?>%"></div>
                                                    </div>
                                                </div>
                                            <?php 
                                                endif;
                                            endforeach; 
                                            ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Detailed Timeline -->
                                <div class="mini-timeline">
                                    <div class="timeline-header">
                                        <span class="timeline-title">24-hour Timeline</span>
                                    </div>
                                    <?php 
                                    if (isset($prediction_data['critical_points']) && !empty($prediction_data['critical_points'])): 
                                        foreach ($prediction_data['critical_points'] as $point): 
                                    ?>
                                        <div class="timeline-chip hazard">
                                            <span class="time-badge"><?php echo date('H:i', strtotime($point['timestamp'])); ?></span>
                                            <?php foreach ($point['violations'] as $violation): ?>
                                                <span class="violation-text"><?php echo $violation; ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php 
                                        endforeach;
                                    else: 
                                    ?>
                                        <div class="timeline-chip safe">
                                            <span class="time-badge">24h</span>
                                            <span class="safe-text">All conditions normal for the next 24 hours</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="error-card">
                            <div class="error-icon">❌</div>
                            <h3>Unable to Generate Prediction</h3>
                            <p>Please try again. If the problem persists, contact technical support.</p>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="initial-card">
                        <div class="initial-icon">🔮</div>
                        <p>Click "Run New Prediction" to start the prediction process.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<style>
.predictions-page {
    padding: 2rem 0;
}

.predictions-page h1 {
    text-align: center;
    color: var(--primary-color);
    margin-bottom: 2rem;
}

.prediction-controls {
    text-align: center;
    margin-bottom: 2rem;
}

.prediction-btn {
    font-size: 1.2rem;
    padding: 1rem 2rem;
    background: linear-gradient(135deg, #4a90e2, #357abd);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.prediction-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 8px rgba(0,0,0,0.15);
}

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    justify-content: center;
    align-items: center;
}

.modal-content {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    width: 90%;
    max-width: 600px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.steps-container {
    margin-top: 2rem;
}

.prediction-step {
    display: flex;
    align-items: center;
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: 8px;
    background: #f8f9fa;
    transition: background-color 0.3s ease;
}

.prediction-step.active {
    background: #e3f2fd;
}

.prediction-step.completed {
    background: #f1f8e9;
}

.step-icon {
    font-size: 2rem;
    margin-right: 1rem;
    width: 40px;
    text-align: center;
}

.step-content {
    flex-grow: 1;
}

.step-content h3 {
    margin: 0;
    color: var(--primary-color);
    font-size: 1.1rem;
}

.step-content p {
    margin: 0.5rem 0 0;
    color: #666;
    font-size: 0.9rem;
}

.step-status {
    font-size: 1.5rem;
    margin-left: 1rem;
}

.prediction-output {
    margin: 1.5rem 0;
    font-family: monospace;
    white-space: pre-wrap;
}

.initial-card {
    text-align: center;
    padding: 2rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.initial-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.initial-card p {
    margin: 0;
    color: #666;
}

.prediction-summary {
    max-width: 800px;
    margin: 0 auto;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    font-size: 1rem;
}

/* Top Row Styles */
.top-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    align-items: stretch;
}

.info-block {
    background: white;
    padding: 1.25rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-height: 120px;
}

.block-title {
    font-size: 1.1rem;
    color: #666;
    margin-bottom: 0.5rem;
    display: block;
    font-weight: 500;
}

/* Status Block */
.mini-status {
    background: #e8f5e9;
    border: 1px solid #a5d6a7;
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 1rem;
}

.mini-status.hazard {
    background: #fff3e0;
    border: 1px solid #ffcc80;
}

.status-emoji {
    font-size: 2.5rem;
}

.status-text {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.status-label {
    font-size: 1.5rem;
    font-weight: 600;
    color: #2c3e50;
}

.duration-label {
    font-size: 1.1rem;
    color: #666;
}

/* Accuracy Block */
.mini-accuracy {
    justify-content: center;
    text-align: center;
}

.accuracy-text {
    display: flex;
    flex-direction: column;
    margin-bottom: 1rem;
}

.accuracy-value {
    font-size: 2rem;
    font-weight: 600;
    color: #2c3e50;
}

/* Timeline Summary Block */
.mini-timeline-summary {
    text-align: center;
}

.timeline-summary-content {
    margin-top: 0.5rem;
    font-size: 1.5rem;
    font-weight: 600;
}

.warning-count {
    color: #f57c00;
}

.safe-status {
    color: #2e7d32;
}

/* Sensor Groups */
.sensor-groups {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.sensor-group {
    background: white;
    padding: 1.25rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.group-title {
    font-size: 1.25rem;
    color: #2c3e50;
    margin: 0 0 1rem 0;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #e0e0e0;
}

.sensor-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
}

.sensor-chip {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 6px;
    border: 1px solid #e0e0e0;
    transition: transform 0.2s ease;
}

.sensor-chip:hover {
    transform: translateY(-2px);
}

.sensor-info {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    margin-bottom: 0.5rem;
}

.sensor-name {
    font-size: 1.1rem;
    color: #2c3e50;
    font-weight: 500;
}

.sensor-accuracy {
    font-size: 1.1rem;
    font-weight: 600;
    color: #2c3e50;
}

/* Timeline Styles */
.mini-timeline {
    background: white;
    border-radius: 8px;
    padding: 1.25rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.timeline-header {
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #e0e0e0;
}

.timeline-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #2c3e50;
}

.timeline-chip {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    align-items: center;
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 0.75rem;
}

.timeline-chip.hazard {
    background: #fff3e0;
    border: 1px solid #ffcc80;
}

.timeline-chip.safe {
    background: #e8f5e9;
    border: 1px solid #a5d6a7;
}

.time-badge {
    background: rgba(0,0,0,0.1);
    padding: 0.25rem 0.75rem;
    border-radius: 4px;
    font-size: 1rem;
    font-weight: 600;
    color: #2c3e50;
}

.violation-text {
    font-size: 1rem;
    color: #d32f2f;
}

.safe-text {
    font-size: 1rem;
    color: #2e7d32;
}

/* Progress Bar Styles */
.mini-bar {
    height: 6px;
    background: rgba(0,0,0,0.05);
    border-radius: 3px;
    overflow: hidden;
}

.bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #81c784 0%, #4caf50 100%);
    border-radius: 3px;
    transition: width 0.3s ease;
}

.error-card {
    text-align: center;
    padding: 2rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.error-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.error-card h3 {
    margin: 0 0 1rem 0;
    color: #721c24;
}

.error-card p {
    margin: 0;
    color: #666;
}

@media (max-width: 768px) {
    .prediction-summary {
        padding: 1rem;
    }

    .top-row {
        grid-template-columns: 1fr;
    }

    .info-block {
        min-height: auto;
        padding: 1rem;
    }

    .sensor-grid {
        grid-template-columns: 1fr;
    }

    .status-emoji {
        font-size: 2rem;
    }

    .status-label {
        font-size: 1.25rem;
    }

    .accuracy-value {
        font-size: 1.5rem;
    }

    .timeline-summary-content {
        font-size: 1.25rem;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function showPredictionSteps() {
    const modal = document.getElementById('predictionModal');
    modal.style.display = 'flex';
    
    // Start the step animation sequence
    animateSteps();
}

function animateSteps() {
    const steps = [
        { id: 'step1', delay: 0 },
        { id: 'step2', delay: 2000 },
        { id: 'step3', delay: 4000 },
        { id: 'step4', delay: 6000 }
    ];
    
    steps.forEach(step => {
        setTimeout(() => {
            const stepElement = document.getElementById(step.id);
            // Mark previous step as completed
            const prevStep = steps[steps.findIndex(s => s.id === step.id) - 1];
            if (prevStep) {
                const prevElement = document.getElementById(prevStep.id);
                prevElement.classList.remove('active');
                prevElement.classList.add('completed');
                prevElement.querySelector('.step-status').textContent = '✅';
            }
            
            // Activate current step
            stepElement.classList.add('active');
            stepElement.querySelector('.step-status').textContent = '🔄';
            stepElement.querySelector('.step-status').classList.add('spinning');
        }, step.delay);
    });
    
    // Complete the last step and close modal
    setTimeout(() => {
        const lastStep = document.getElementById('step4');
        lastStep.classList.remove('active');
        lastStep.classList.add('completed');
        lastStep.querySelector('.step-status').textContent = '✅';
        lastStep.querySelector('.step-status').classList.remove('spinning');
        
        // Close modal after showing completion
        setTimeout(() => {
            modal.style.display = 'none';
            // Reset steps for next run
            steps.forEach(step => {
                const element = document.getElementById(step.id);
                element.classList.remove('active', 'completed');
                element.querySelector('.step-status').textContent = '⏳';
                element.querySelector('.step-status').classList.remove('spinning');
            });
        }, 1000);
    }, 8000);
}

// Close modal if clicked outside
window.onclick = function(event) {
    const modal = document.getElementById('predictionModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}

// Initialize the prediction chart
const ctx = document.getElementById('predictionChart').getContext('2d');

// Load prediction data from JSON file
fetch('prediction_data.json')
    .then(response => response.json())
    .then(data => {
        const predictionChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.timestamps.map(timestamp => {
                    const date = new Date(timestamp);
                    return `${date.getHours()}:00`;
                }),
                datasets: [
                    {
                        label: 'Temperature (°C)',
                        borderColor: '#e63946',
                        data: data.predictions.temperature,
                        fill: false,
                        tension: 0.4
                    },
                    {
                        label: 'Humidity (%)',
                        borderColor: '#457b9d',
                        data: data.predictions.humidity,
                        fill: false,
                        tension: 0.4
                    },
                    {
                        label: 'CO2 (PPM)',
                        borderColor: '#2a9d8f',
                        data: data.predictions.mq135_co2,
                        fill: false,
                        tension: 0.4
                    },
                    {
                        label: 'CO (PPM)',
                        borderColor: '#e9c46a',
                        data: data.predictions.mq7_co,
                        fill: false,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        enabled: true,
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            title: function(context) {
                                return data.timestamps[context[0].dataIndex];
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Time'
                        },
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45
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

        // Handle sensor toggles
        document.querySelectorAll('.sensor-toggle input').forEach(toggle => {
            toggle.addEventListener('change', function() {
                const sensorName = this.dataset.sensor;
                const dataset = predictionChart.data.datasets.find(ds => 
                    ds.label.toLowerCase().includes(sensorName.toLowerCase())
                );
                if (dataset) {
                    dataset.hidden = !this.checked;
                    predictionChart.update();
                }
            });
        });
    })
    .catch(error => {
        console.error('Error loading prediction data:', error);
        document.querySelector('.chart-container').innerHTML = 
            '<div class="error-message">Unable to load prediction data</div>';
    });
</script>

<?php require_once 'includes/footer.php'; ?> 