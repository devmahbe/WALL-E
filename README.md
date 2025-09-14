# A Prototype Rover for Hazardous Environment Monitoring System

A web-based monitoring system for Rover environmental sensors. This system collects and displays data from various sensors including temperature, humidity, and gas levels.

## Features

- Real-time sensor data monitoring
- Environmental status analysis
- Historical data viewing
- Responsive design for all devices
- Automatic data refresh
- Data management capabilities

## Sensors Monitored

- Temperature
- Humidity
- MQ2 (LPG and Methane)
- MQ135 (NH3 and CO2)
- MQ7 (Carbon Monoxide)
- MQ8 (Hydrogen)

## Requirements

- PHP 7.4 or higher
- MySQL/MariaDB
- Web server (Apache/Nginx)
- Python 3.x (for sensor data collection)
- PySerial library
- Arduino with appropriate sensors

## Installation

1. Clone the repository to your web server directory:
   ```bash
   git clone https://github.com/yourusername/mars-rover-sensors.git
   ```

2. Create a MySQL database named 'marsrover' and import the schema:
   ```sql
   CREATE TABLE sensor_data (
       id INT AUTO_INCREMENT PRIMARY KEY,
       humidity FLOAT,
       temperature FLOAT,
       mq2_lpg FLOAT,
       mq2_methane FLOAT,
       mq135_nh3 FLOAT,
       mq135_co2 FLOAT,
       mq7_co FLOAT,
       mq8_h2 FLOAT,
       timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
   );
   ```

3. Install Python dependencies:
   ```bash
   pip install pyserial requests
   ```

4. Configure your Arduino port in `upload_data.py`:
   ```python
   ser = serial.Serial('COM5', 9600)  # Change COM5 to your Arduino port
   ```

5. Start the data collection script:
   ```bash
   python upload_data.py
   ```

## Usage

1. Open your web browser and navigate to the installation directory
2. The main dashboard shows the latest sensor readings
3. Use the navigation menu to access different features:
   - View Data: Shows historical sensor data
   - Environment Status: Displays analysis of environmental conditions

## Security Notes

- The system uses prepared statements to prevent SQL injection
- Input validation is implemented for all user inputs
- Session management is used for flash messages
- Database credentials should be properly secured in production

## Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a new Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details. 
