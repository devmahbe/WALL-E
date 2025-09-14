#include <SPI.h>
#include <nRF24L01.h>
#include <RF24.h>
#include <DHT.h>

// Define DHT11 sensor pin and type
#define DHTPIN 2
#define DHTTYPE DHT11
DHT dht(DHTPIN, DHTTYPE);

// NRF24L01 setup
RF24 radio(8, 7);  // CE, CSN pins for NRF24L01
const byte address[6] = "00001";

// Define MQ sensor pins
#define MQ2_PIN A0    // MQ-2 sensor connected to analog pin A0
#define MQ135_PIN A1  // MQ-135 sensor connected to analog pin A1
#define MQ7_PIN A2    // MQ-7 sensor connected to analog pin A2
#define MQ8_PIN A3    // MQ-8 sensor connected to analog pin A3

// Calibration constants for sensors (adjust based on your sensors)
float MQ2_R0 = 10.0;
float MQ135_R0 = 76.63;
float MQ7_R0 = 100.0;  // Placeholder value for MQ-7
float MQ8_R0 = 50.0;   // Placeholder value for MQ-8

// Gas curve constants for MQ sensors
float LPGCurve[3] = {2.3, 0.21, -0.47};     // LPG
float MethaneCurve[3] = {2.3, 0.27, -0.48}; // Methane
float SmokeCurve[3] = {2.3, 0.53, -0.44};   // Smoke
float NH3Curve[3] = {1.6, 0.16, -0.54};     // NH3 (Ammonia)
float CO2Curve[3] = {1.5, 0.15, -0.45};     // CO2
float COCurve[3] = {2.3, 0.18, -0.46};      // CO (Carbon Monoxide)
float H2Curve[3] = {2.3, 0.18, -0.46};      // H2 (Hydrogen)

float data[8];  // Data array to transmit: [humidity, temperature, MQ2_LPG_PPM, MQ2_Methane_PPM, MQ135_NH3_PPM, MQ135_CO2_PPM, MQ7_CO_PPM, MQ8_H2_PPM]

float calculatePPM(float sensorValue, float R0, float gasCurve[]) {
  return pow(10, ((log10(sensorValue / R0) - gasCurve[1]) / gasCurve[2]) + gasCurve[0]);
}

void setup() {
  Serial.begin(9600);
  dht.begin();

  radio.begin();
  radio.openWritingPipe(address);
  radio.setPALevel(RF24_PA_MIN);
  radio.stopListening();
}

void loop() {
  // Read humidity and temperature from DHT11
  float humidity = dht.readHumidity();
  float temperature = dht.readTemperature();

  // Read MQ-2 sensor value and calculate PPM (LPG, Methane)
  int MQ2_value = analogRead(MQ2_PIN);
  float MQ2_resistance = (1023.0 - MQ2_value) / MQ2_value * 5.0;
  float MQ2_LPG_PPM = calculatePPM(MQ2_resistance, MQ2_R0, LPGCurve);
  float MQ2_Methane_PPM = calculatePPM(MQ2_resistance, MQ2_R0, MethaneCurve);

  // Read MQ-135 sensor value and calculate PPM (NH3, CO2)
  int MQ135_value = analogRead(MQ135_PIN);
  float MQ135_resistance = (1023.0 - MQ135_value) / MQ135_value * 5.0;
  float MQ135_NH3_PPM = calculatePPM(MQ135_resistance, MQ135_R0, NH3Curve);
  float MQ135_CO2_PPM = calculatePPM(MQ135_resistance, MQ135_R0, CO2Curve);

  // Read MQ-7 sensor value and calculate PPM (CO)
  int MQ7_value = analogRead(MQ7_PIN);
  float MQ7_resistance = (1023.0 - MQ7_value) / MQ7_value * 5.0;
  float MQ7_CO_PPM = calculatePPM(MQ7_resistance, MQ7_R0, COCurve);

  // Read MQ-8 sensor value and calculate PPM (H2)
  int MQ8_value = analogRead(MQ8_PIN);
  float MQ8_resistance = (1023.0 - MQ8_value) / MQ8_value * 5.0;
  float MQ8_H2_PPM = calculatePPM(MQ8_resistance, MQ8_R0, H2Curve);

  // Prepare data to send
  data[0] = humidity;
  data[1] = temperature;
  data[2] = MQ2_LPG_PPM;
  data[3] = MQ2_Methane_PPM;
  data[4] = MQ135_NH3_PPM;
  data[5] = MQ135_CO2_PPM;
  data[6] = MQ7_CO_PPM;
  data[7] = MQ8_H2_PPM;

  // Send the data via NRF24L01
  bool success = radio.write(&data, sizeof(data));
  if (success) {
    Serial.println("✅ Data sent successfully.");
  } else {
    Serial.println("❌ Failed to send data.");
  }

  // Debugging output
  Serial.print("Humidity: ");
  Serial.print(humidity);
  Serial.print(" %  Temperature: ");
  Serial.print(temperature);
  Serial.print(" *C  MQ-2 (LPG): ");
  Serial.print(MQ2_LPG_PPM);
  Serial.print(" PPM  MQ-2 (Methane): ");
  Serial.print(MQ2_Methane_PPM);
  Serial.print(" PPM  MQ-135 (NH3): ");
  Serial.print(MQ135_NH3_PPM);
  Serial.print(" PPM  MQ-135 (CO2): ");
  Serial.print(MQ135_CO2_PPM);
  Serial.print(" PPM  MQ-7 (CO): ");
  Serial.print(MQ7_CO_PPM);
  Serial.print(" PPM  MQ-8 (H2): ");
  Serial.println(MQ8_H2_PPM);

  delay(10000);  // Send data every 10 seconds
}
