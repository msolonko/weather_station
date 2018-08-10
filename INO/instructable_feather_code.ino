#include <ESP8266WiFi.h>//import the ESP library
#include <Wire.h>//import the Wire library that works with I2C, which is the protocol of our sensor
#include <Adafruit_Sensor.h>//next two are sensor libraries
#include <Adafruit_BME280.h>
Adafruit_BME280 bme;//create sensor variable
const char* server = "example.com";//your website domain
const char* username = "SSID";//wifi username
const char* password =  "PASSWORD";//wifi password
WiFiClient client;//creates Wifi Client
void setup()
{
  WiFi.begin(username, password);//connect to Wifi
  while (WiFi.status() != WL_CONNECTED) //if not connected
    {
      delay(100);//wait 1/10 second before checking again
    }
     if (!bme.begin()) {//if sensor fails to start
        ESP.deepSleep(6e8);//sleep for 10 minutes and try again
    }

    delay(100);//for sensor to turn on
  float temp = bme.readTemperature();//celcius temperature
  float pressure = bme.readPressure() / 100.0;
  float humidity = bme.readHumidity();
  int light = 0;
  for(int i = 0; i<100; i++){
    light+=analogRead(A0);
  }
  light/=100;//averages out 100 photoresistor lights values for more accuracy

  if (client.connect(server, 80)) {//if connected to server
     String data = "light="+String(light)+"&temperature="+String(temp)+"&pressure="+String(pressure)+"&humidity="+String(humidity);//send all data to server
     client.println("POST /PROCESS.php HTTP/1.1"); //fill in the page you are sending data to (Ex: /esp.php)
     client.print("Host: YOUR_HOST\n");  //fill in HOST(your domain name)               
     client.println("User-Agent: ESP8266/1.0");
     client.println("Connection: close"); 
     client.println("Content-Type: application/x-www-form-urlencoded");
     client.print("Content-Length: ");
     client.print(data.length());
     client.print("\n\n");
     client.print(data);
     client.stop(); 
   }
  ESP.deepSleep(6e8);//sleep for 10 minutes, consuming less power
}

void loop() {
  
}
