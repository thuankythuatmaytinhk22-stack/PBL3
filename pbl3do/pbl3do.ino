#include <WiFi.h>
#include <HTTPClient.h>

const char* ssid = "Thu van 1";
const char* password = "Thuvan123@@";
const char* server = "http://192.168.100.159/laymaunuoc";

#define RELAY1 5
#define RELAY2 18
#define TRIG_PIN 3
#define ECHO_PIN 2

int max_level_threshold = 8; // Ngưỡng ngắt mặc định (cm)

void setup() {
  Serial.begin(115200);
  WiFi.begin(ssid, password);

  pinMode(RELAY1, OUTPUT);
  pinMode(RELAY2, OUTPUT);
  pinMode(TRIG_PIN, OUTPUT);
  pinMode(ECHO_PIN, INPUT);

  // Mặc định relay mức cao là TẮT (tùy loại relay của bạn)
  digitalWrite(RELAY1, HIGH); 
  digitalWrite(RELAY2, HIGH);

  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("\nWiFi connected");
}

// Hàm đo khoảng cách từ cảm biến siêu âm
long getDistance() {
  digitalWrite(TRIG_PIN, LOW);
  delayMicroseconds(2);
  digitalWrite(TRIG_PIN, HIGH);
  delayMicroseconds(10);
  digitalWrite(TRIG_PIN, LOW);
  
  long duration = pulseIn(ECHO_PIN, HIGH);
  long distance = duration * 0.034 / 2;
  return (distance == 0) ? 999 : distance; // Trả về 999 nếu lỗi đo
}

void loop() {
  long currentDistance = getDistance();
  Serial.print("Khoảng cách: "); Serial.print(currentDistance); Serial.println(" cm");

  // LOGIC TỰ NGẮT AN TOÀN: Nếu nước quá gần (<= ngưỡng), tắt ngay lập tức
  if (currentDistance <= max_level_threshold) {
    if (digitalRead(RELAY1) == HIGH || digitalRead(RELAY2) == HIGH) { // Nếu đang bật
       digitalWrite(RELAY1, LOW); // Tắt Relay (Sửa LOW/HIGH tùy loại relay)
       digitalWrite(RELAY2, LOW);
       Serial.println("CẢNH BÁO: Đã đạt ngưỡng đầy! Tự động ngắt bơm.");
    }
  }

  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.begin(String(server) + "/get_command.php");
    int httpCode = http.GET();

    if (httpCode == 200) {
      String payload = http.getString();
      payload.trim();
      if (payload != "" && payload != "OFF") {
        Serial.println("Nhận dữ liệu: " + payload);
        processCommands(payload);
      }
    }
    http.end();
  }
  delay(2000); 
}

void processCommands(String input) {
  // Tách các lệnh cách nhau bởi dấu phẩy
  int start = 0;
  int end = input.indexOf(',');
  
  while (end != -1) {
    executeSingleCommand(input.substring(start, end));
    start = end + 1;
    end = input.indexOf(',', start);
  }
  executeSingleCommand(input.substring(start)); // Lệnh cuối cùng
}

void executeSingleCommand(String cmd) {
  cmd.trim();
  // Cập nhật ngưỡng ngắt nếu có lệnh MAX:
  if (cmd.startsWith("MAX:")) {
    max_level_threshold = cmd.substring(4).toInt();
    Serial.print("Cập nhật ngưỡng ngắt mới: "); Serial.println(max_level_threshold);
  }
  // Điều khiển relay (Chỉ bật nếu khoảng cách còn an toàn)
  else if (cmd == "R1_ON") {
    if (getDistance() > max_level_threshold) digitalWrite(RELAY1, HIGH);
  }
  else if (cmd == "R1_OFF") digitalWrite(RELAY1, LOW);
  else if (cmd == "R2_ON") {
    if (getDistance() > max_level_threshold) digitalWrite(RELAY2, HIGH);
  }
  else if (cmd == "R2_OFF") digitalWrite(RELAY2, LOW);
}