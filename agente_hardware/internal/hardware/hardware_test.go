package hardware

import (
	"strings"
	"testing"
	"time"
)

func TestCleanString(t *testing.T) {
	input := "  Intel(R)   Core(TM)    i7-8550U   \n"
	expected := "Intel(R) Core(TM) i7-8550U"
	got := CleanString(input)
	if got != expected {
		t.Errorf("CleanString failed: expected %q, got %q", expected, got)
	}
}

func TestCleanSerialNumber(t *testing.T) {
	cases := []struct {
		input    string
		expected string
	}{
		{"To be filled by O.E.M.", ""},
		{"to be filled by oem", ""},
		{"Default string", ""},
		{"None", ""},
		{"5CD8211Q7V", "5CD8211Q7V"},
		{"  PF2X9ABC  ", "PF2X9ABC"},
	}

	for _, c := range cases {
		got := CleanSerialNumber(c.input)
		if got != c.expected {
			t.Errorf("CleanSerialNumber(%q) = %q; expected %q", c.input, got, c.expected)
		}
	}
}

func TestFormatBytesToHuman(t *testing.T) {
	// 480 GB SSD (approx 500,107,862,016 bytes)
	ssd480 := uint64(500107862016)
	got := FormatBytesToHuman(ssd480)
	if !strings.Contains(got, "480 GB") && !strings.Contains(got, "500 GB") && !strings.Contains(got, "466 GB") {
		t.Errorf("FormatBytesToHuman(480GB) unexpected: %s", got)
	}

	// 1 TB HDD (approx 1,000,202,273,280 bytes)
	hdd1tb := uint64(1000202273280)
	got1tb := FormatBytesToHuman(hdd1tb)
	if got1tb != "1 TB" && got1tb != "960 GB" && got1tb != "1000 GB" {
		t.Errorf("FormatBytesToHuman(1TB) unexpected: %s", got1tb)
	}
}

func TestInferDeviceType(t *testing.T) {
	// Laptop by ChassisType (10: Notebook)
	if dt := inferDeviceType([]int32{10}, "Generic System", false); dt != DeviceTypeLaptop {
		t.Errorf("Expected %s, got %s", DeviceTypeLaptop, dt)
	}

	// All-in-One by ChassisType (13)
	if dt := inferDeviceType([]int32{13}, "Generic System", false); dt != DeviceTypeAllInOne {
		t.Errorf("Expected %s, got %s", DeviceTypeAllInOne, dt)
	}

	// All-in-One by Model name
	if dt := inferDeviceType([]int32{3}, "HP Pavilion All-in-One 24", false); dt != DeviceTypeAllInOne {
		t.Errorf("Expected %s, got %s", DeviceTypeAllInOne, dt)
	}

	// PC Desktop by ChassisType (3: Desktop)
	if dt := inferDeviceType([]int32{3}, "Desktop PC", false); dt != DeviceTypePC {
		t.Errorf("Expected %s, got %s", DeviceTypePC, dt)
	}

	// Laptop by battery presence
	if dt := inferDeviceType([]int32{2}, "Unknown Device", true); dt != DeviceTypeLaptop {
		t.Errorf("Expected %s, got %s", DeviceTypeLaptop, dt)
	}
}

func TestCollectHardwareIntegration(t *testing.T) {
	start := time.Now()
	report, err := CollectHardware()
	elapsed := time.Since(start)

	if err != nil {
		t.Fatalf("CollectHardware failed: %v", err)
	}

	if elapsed > 3*time.Second {
		t.Logf("Notice: Hardware collection took %v (requirement is < 3s)", elapsed)
	}

	if report.HostName == "" {
		t.Error("HostName should not be empty")
	}

	if report.DeviceType == "" {
		t.Error("DeviceType should not be empty")
	}

	if report.CPU.Name == "" {
		t.Error("CPU name should not be empty")
	}

	if report.RAM.TotalCapacityGB <= 0 {
		t.Error("RAM total capacity should be greater than 0")
	}

	if report.OS.Caption == "" {
		t.Error("OS caption should not be empty")
	}

	if len(report.Characteristics) == 0 {
		t.Error("Characteristics should have at least 1 entry")
	}

	t.Logf("Collected: Host=%s, Type=%s, CPU=%s, RAM=%s, IP=%s, Time=%v",
		report.HostName, report.DeviceType, report.CPU.Name, report.RAM.TotalCapacityFormatted, report.IPAddress, elapsed)
}
