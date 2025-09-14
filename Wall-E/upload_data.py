import serial
import requests

# Configure your COM port and baud rate
ser = serial.Serial('COM12', 9600)  # Change COM5 to your Arduino port
server_url = 'http://localhost/sensor_api/upload.php'

while True:
    try:
        line = ser.readline().decode('utf-8').strip()
        print("Received from Arduino:", line)

        # Expecting comma-separated values: h,t,lpg,methane,nh3,co2,co,h2
        values = line.split(',')
        if len(values) == 8:
            payload = {
                'humidity': values[0],
                'temperature': values[1],
                'mq2_lpg': values[2],
                'mq2_methane': values[3],
                'mq135_nh3': values[4],
                'mq135_co2': values[5],
                'mq7_co': values[6],
                'mq8_h2': values[7]
            }
            response = requests.post(server_url, data=payload)
            print("Server response:", response.text)
        else:
            print("Invalid data format")
    except Exception as e:
        print("Error:", e)
