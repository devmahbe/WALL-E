#include <SPI.h>
#include <nRF24L01.h>
#include <RF24.h>

// NRF24L01 setup
RF24 radio(8, 7);  // CE, CSN
const byte address[6] = "00001";

// Array to hold received data
float receivedData[8];  // [humidity, temperature, MQ2_LPG, MQ2_Methane, MQ135_NH3, MQ135_CO2, MQ7_CO, MQ8_H2]

void setup() {
  Serial.begin(9600);
  radio.begin();
  radio.openReadingPipe(0, address);
  radio.setPALevel(RF24_PA_MIN);
  radio.startListening();

  Serial.println("📡 Receiver Ready. Waiting for data...");
}

void loop() {
  if (radio.available()) {
    radio.read(&receivedData, sizeof(receivedData));

    // 1. Human-readable Serial Monitor output
    Serial.println("✅ Data Received Successfully:");
    Serial.print("Humidity: "); Serial.print(receivedData[0]); Serial.println(" %");
    Serial.print("Temperature: "); Serial.print(receivedData[1]); Serial.println(" °C");
    Serial.print("MQ-2 (LPG): "); Serial.print(receivedData[2]/1000); Serial.println(" PPM");
    Serial.print("MQ-2 (Methane): "); Serial.print(receivedData[3]/1000); Serial.println(" PPM");
    Serial.print("MQ-135 (NH3): "); Serial.print(receivedData[4]/10000); Serial.println(" PPM");
    Serial.print("MQ-135 (CO2): "); Serial.print(417 + (receivedData[5]/10000)); Serial.println(" PPM");
    Serial.print("MQ-7 (CO): "); Serial.print(receivedData[6]/1000); Serial.println(" PPM");
    Serial.print("MQ-8 (H2): "); Serial.print(receivedData[7]/100000); Serial.println(" PPM");
    Serial.println("--------------------------------------------------");

    // 2. Send CSV line to PC for Python script
    Serial.print(receivedData[0]); Serial.print(",");  // Humidity
    Serial.print(receivedData[1]); Serial.print(",");  // Temperature
    Serial.print(receivedData[2]/1000); Serial.print(",");  // MQ2_LPG
    Serial.print(receivedData[3]/1000); Serial.print(",");  // MQ2_Methane
    Serial.print(receivedData[4]/10000); Serial.print(",");  // MQ135_NH3
    Serial.print(417 + (receivedData[5]/10000)); Serial.print(",");  // MQ135_CO2
    Serial.print(receivedData[6]/1000); Serial.print(",");  // MQ7_CO
    Serial.println(receivedData[7]/100000);                   // MQ8_H2
  }

  delay(10000);  // 2-second delay between checks
}
