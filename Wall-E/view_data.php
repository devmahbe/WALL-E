<?php
session_start();
require_once 'includes/header.php';
require_once 'includes/db.php';

// Get filter parameters
$timeRange = isset($_GET['timeRange']) ? $_GET['timeRange'] : '24h';
$sensorType = isset($_GET['sensorType']) ? $_GET['sensorType'] : 'all';

// Build SQL query based on filters
$sql = "SELECT * FROM sensor_data WHERE 1=1";

switch($timeRange) {
    case '1h':
        $sql .= " AND timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR)";
        break;
    case '6h':
        $sql .= " AND timestamp >= DATE_SUB(NOW(), INTERVAL 6 HOUR)";
        break;
    case '24h':
        $sql .= " AND timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        break;
    case '7d':
        $sql .= " AND timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        break;
    case '30d':
        $sql .= " AND timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        break;
}

$sql .= " ORDER BY id DESC LIMIT 1000";
$result = $conn->query($sql);

// Calculate basic statistics
$stats = array();
if ($result->num_rows > 0) {
    $data = array();
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    $stats['humidity'] = calculateStats(array_column($data, 'humidity'));
    $stats['temperature'] = calculateStats(array_column($data, 'temperature'));
    $stats['mq2_lpg'] = calculateStats(array_column($data, 'mq2_lpg'));
    $stats['mq2_methane'] = calculateStats(array_column($data, 'mq2_methane'));
    $stats['mq135_nh3'] = calculateStats(array_column($data, 'mq135_nh3'));
    $stats['mq135_co2'] = calculateStats(array_column($data, 'mq135_co2'));
    $stats['mq7_co'] = calculateStats(array_column($data, 'mq7_co'));
    $stats['mq8_h2'] = calculateStats(array_column($data, 'mq8_h2'));
    
    // Reset result pointer
    $result->data_seek(0);
}

function calculateStats($values) {
    return array(
        'min' => min($values),
        'max' => max($values),
        'avg' => array_sum($values) / count($values),
        'std' => calculateStdDev($values)
    );
}

function calculateStdDev($values) {
    $avg = array_sum($values) / count($values);
    $squareDiffs = array_map(function($value) use ($avg) {
        return pow($value - $avg, 2);
    }, $values);
    return sqrt(array_sum($squareDiffs) / count($values));
}
?>

<div class="data-view">
    <h1>Sensor Data Analysis</h1>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>
    
    <div class="analysis-tools">
        <div class="card">
            <h2>Data Filters</h2>
            <form id="filterForm" class="filter-form">
                <div class="form-group">
                    <label for="timeRange">Time Range:</label>
                    <select name="timeRange" id="timeRange" onchange="this.form.submit()">
                        <option value="1h" <?php echo $timeRange == '1h' ? 'selected' : ''; ?>>Last Hour</option>
                        <option value="6h" <?php echo $timeRange == '6h' ? 'selected' : ''; ?>>Last 6 Hours</option>
                        <option value="24h" <?php echo $timeRange == '24h' ? 'selected' : ''; ?>>Last 24 Hours</option>
                        <option value="7d" <?php echo $timeRange == '7d' ? 'selected' : ''; ?>>Last 7 Days</option>
                        <option value="30d" <?php echo $timeRange == '30d' ? 'selected' : ''; ?>>Last 30 Days</option>
                    </select>
                </div>
            </form>
        </div>

        <div class="card statistics-card">
            <h2>Quick Statistics</h2>
            <div class="stats-grid">
                <?php foreach ($stats as $sensor => $stat): ?>
                <div class="stat-item">
                    <h3><?php echo ucwords(str_replace('_', ' ', $sensor)); ?></h3>
                    <div class="stat-details">
                        <div class="stat-row">
                            <span>Min:</span>
                            <span><?php echo number_format($stat['min'], 2); ?></span>
                        </div>
                        <div class="stat-row">
                            <span>Max:</span>
                            <span><?php echo number_format($stat['max'], 2); ?></span>
                        </div>
                        <div class="stat-row">
                            <span>Avg:</span>
                            <span><?php echo number_format($stat['avg'], 2); ?></span>
                        </div>
                        <div class="stat-row">
                            <span>Std Dev:</span>
                            <span><?php echo number_format($stat['std'], 2); ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-actions">
            <div class="action-group">
                <button class="btn btn-refresh" onclick="refreshData()">
                    <span class="refresh-icon">↻</span>
                    <span class="refresh-text">Refresh Data</span>
                </button>
                
                <button class="btn btn-export" onclick="exportData('csv')">
                    <span class="export-icon">↓</span>
                    <span class="export-text">Export CSV</span>
                </button>
                
                <button class="btn btn-export" onclick="exportData('json')">
                    <span class="export-icon">⋮</span>
                    <span class="export-text">Export JSON</span>
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table id="sensorTable">
                <thead>
                    <tr>
                        <th data-sort="id">ID</th>
                        <th data-sort="humidity">Humidity (%)</th>
                        <th data-sort="temperature">Temperature (°C)</th>
                        <th data-sort="mq2_lpg">LPG (PPM)</th>
                        <th data-sort="mq2_methane">Methane (PPM)</th>
                        <th data-sort="mq135_nh3">NH3 (PPM)</th>
                        <th data-sort="mq135_co2">CO2 (PPM)</th>
                        <th data-sort="mq7_co">CO (PPM)</th>
                        <th data-sort="mq8_h2">H2 (PPM)</th>
                        <th data-sort="timestamp">Timestamp</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row["id"]; ?></td>
                                <td><?php echo number_format($row["humidity"], 1); ?></td>
                                <td><?php echo number_format($row["temperature"], 1); ?></td>
                                <td><?php echo number_format($row["mq2_lpg"], 1); ?></td>
                                <td><?php echo number_format($row["mq2_methane"], 1); ?></td>
                                <td><?php echo number_format($row["mq135_nh3"], 1); ?></td>
                                <td><?php echo number_format($row["mq135_co2"], 1); ?></td>
                                <td><?php echo number_format($row["mq7_co"], 1); ?></td>
                                <td><?php echo number_format($row["mq8_h2"], 1); ?></td>
                                <td><?php echo $row["timestamp"]; ?></td>
                                <td>
                                    <a href="delete.php?id=<?php echo $row["id"]; ?>" 
                                       class="btn btn-danger delete-action">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="11">No data available</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

  <style>
.data-view {
    padding: 2rem 0;
}

.data-view h1 {
    text-align: center;
    color: var(--primary-color);
    margin-bottom: 2rem;
}

.alert {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
      font-weight: 600;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.table-actions {
    margin-bottom: 1.5rem;
      display: flex;
      justify-content: flex-end;
}

.table-responsive {
    overflow-x: auto;
}

@media (max-width: 768px) {
    .table-responsive {
        margin: 0 -1.5rem;
    }
}

.btn-refresh {
    background: linear-gradient(135deg, #3498db, #2980b9);
      color: white;
      border: none;
    padding: 0.8rem 1.5rem;
    border-radius: 8px;
      cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(41, 128, 185, 0.2);
}

.btn-refresh:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(41, 128, 185, 0.3);
    background: linear-gradient(135deg, #2980b9, #2573a7);
}

.refresh-icon {
    font-size: 1.2rem;
    transition: transform 0.5s ease;
    display: inline-block;
}

.btn-refresh.refreshing .refresh-icon {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.table-loading {
    position: relative;
}

.table-loading:after {
    content: 'Refreshing data...';
    position: absolute;
    top: 0;
    left: 0;
      width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    color: var(--primary-color);
    font-weight: 500;
}

.analysis-tools {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.filter-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-group label {
    font-weight: 600;
    color: var(--text-color);
}

.form-group select {
    padding: 0.5rem;
    border: 1px solid rgba(0,0,0,0.1);
    border-radius: 4px;
    background: white;
    font-size: 0.9rem;
}

.statistics-card {
    background: linear-gradient(135deg, #f8f9fa, #ffffff);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-top: 1rem;
}

.stat-item {
    background: white;
    padding: 1rem;
      border-radius: 8px;
    box-shadow: var(--shadow-sm);
}

.stat-item h3 {
    color: var(--primary-color);
    font-size: 0.9rem;
    margin-bottom: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-details {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.stat-row {
    display: flex;
    justify-content: space-between;
    font-size: 0.85rem;
    padding: 0.3rem 0;
    border-bottom: 1px solid rgba(0,0,0,0.05);
}

.stat-row:last-child {
    border-bottom: none;
}

.stat-row span:first-child {
    color: var(--text-light);
}

.stat-row span:last-child {
    font-weight: 600;
    color: var(--text-color);
}

.action-group {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.btn-export {
    background: linear-gradient(135deg, #27ae60, #2ecc71);
    color: white;
    border: none;
    padding: 0.8rem 1.5rem;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
      font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(46, 204, 113, 0.2);
}

.btn-export:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(46, 204, 113, 0.3);
    background: linear-gradient(135deg, #219a52, #25a85c);
}

.export-icon {
    font-size: 1.2rem;
}

@media (max-width: 768px) {
    .analysis-tools {
        grid-template-columns: 1fr;
    }
    
    .action-group {
        flex-direction: column;
        width: 100%;
    }
    
    .btn-export {
        width: 100%;
        justify-content: center;
    }
}
</style>

<script>
function refreshData() {
    const button = document.querySelector('.btn-refresh');
    button.classList.add('refreshing');
    
    const tableWrapper = document.querySelector('.table-responsive');
    tableWrapper.classList.add('table-loading');
    
    setTimeout(() => {
        window.location.reload();
    }, 500);
}

function exportData(format) {
    const table = document.getElementById('sensorTable');
    const rows = Array.from(table.querySelectorAll('tbody tr'));
    const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent.trim());
    
    let data = rows.map(row => {
        const cells = Array.from(row.querySelectorAll('td'));
        return cells.map(cell => cell.textContent.trim());
    });
    
    if (format === 'csv') {
        const csvContent = [
            headers.join(','),
            ...data.map(row => row.join(','))
        ].join('\n');
        
        downloadFile(csvContent, 'sensor_data.csv', 'text/csv');
    } else if (format === 'json') {
        const jsonData = data.map(row => {
            const obj = {};
            headers.forEach((header, index) => {
                obj[header] = row[index];
            });
            return obj;
        });
        
        downloadFile(JSON.stringify(jsonData, null, 2), 'sensor_data.json', 'application/json');
    }
}

function downloadFile(content, fileName, contentType) {
    const blob = new Blob([content], { type: contentType });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = fileName;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
}

// Add sorting functionality
document.addEventListener('DOMContentLoaded', function() {
    const table = document.getElementById('sensorTable');
    const headers = table.querySelectorAll('th[data-sort]');
    
    headers.forEach(header => {
        header.addEventListener('click', () => {
            const column = header.dataset.sort;
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            
            rows.sort((a, b) => {
                const aValue = a.querySelector(`td:nth-child(${Array.from(headers).indexOf(header) + 1})`).textContent;
                const bValue = b.querySelector(`td:nth-child(${Array.from(headers).indexOf(header) + 1})`).textContent;
                
                if (!isNaN(aValue) && !isNaN(bValue)) {
                    return parseFloat(aValue) - parseFloat(bValue);
                }
                return aValue.localeCompare(bValue);
            });
            
            if (header.classList.contains('sort-asc')) {
                rows.reverse();
                header.classList.remove('sort-asc');
                header.classList.add('sort-desc');
      } else {
                header.classList.remove('sort-desc');
                header.classList.add('sort-asc');
            }
            
            tbody.innerHTML = '';
            rows.forEach(row => tbody.appendChild(row));
        });
    });
});
</script>

<?php 
      $conn->close();
require_once 'includes/footer.php'; 
?>
