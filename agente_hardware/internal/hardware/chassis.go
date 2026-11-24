package hardware

import (
	"os"
	"strings"

	"github.com/yusufpapurcu/wmi"
)

type Win32_ComputerSystem struct {
	Manufacturer string
	Model        string
	Name         string
}

type Win32_SystemEnclosure struct {
	ChassisTypes []int32
	Manufacturer string
	SerialNumber string
}

type Win32_BIOS struct {
	Manufacturer       string
	SerialNumber       string
	SMBIOSBIOSVersion  string
	ReleaseDate        string
}

type Win32_ComputerSystemProduct struct {
	IdentifyingNumber string
	Name              string
	Vendor            string
}

type Win32_Battery struct {
	DeviceID string
}

// CollectChassis collects machine enclosure, model, manufacturer, host name, serial and infers device type
func CollectChassis() (ChassisInfo, BIOSInfo, error) {
	chassis := ChassisInfo{
		DeviceType: DeviceTypePC,
	}
	bios := BIOSInfo{}

	// 1. Host name from environment fallback
	if host, err := os.Hostname(); err == nil && host != "" {
		chassis.HostName = host
	}

	// 2. Query Win32_ComputerSystem
	var cs []Win32_ComputerSystem
	qCS := "SELECT Manufacturer, Model, Name FROM Win32_ComputerSystem"
	if err := wmi.Query(qCS, &cs); err == nil && len(cs) > 0 {
		chassis.Manufacturer = CleanString(cs[0].Manufacturer)
		chassis.Model = CleanString(cs[0].Model)
		if cs[0].Name != "" {
			chassis.HostName = CleanString(cs[0].Name)
		}
	}

	// 3. Query Win32_BIOS
	var biosList []Win32_BIOS
	qBIOS := "SELECT Manufacturer, SerialNumber, SMBIOSBIOSVersion, ReleaseDate FROM Win32_BIOS"
	if err := wmi.Query(qBIOS, &biosList); err == nil && len(biosList) > 0 {
		bios.Manufacturer = CleanString(biosList[0].Manufacturer)
		bios.SerialNumber = CleanSerialNumber(biosList[0].SerialNumber)
		bios.Version = CleanString(biosList[0].SMBIOSBIOSVersion)
		bios.ReleaseDate = CleanString(biosList[0].ReleaseDate)

		if bios.SerialNumber != "" {
			chassis.SerialNumber = bios.SerialNumber
		}
	}

	// 4. Fallback for SerialNumber with Win32_ComputerSystemProduct
	if chassis.SerialNumber == "" {
		var cspList []Win32_ComputerSystemProduct
		qCSP := "SELECT IdentifyingNumber, Name, Vendor FROM Win32_ComputerSystemProduct"
		if err := wmi.Query(qCSP, &cspList); err == nil && len(cspList) > 0 {
			cspSerial := CleanSerialNumber(cspList[0].IdentifyingNumber)
			if cspSerial != "" {
				chassis.SerialNumber = cspSerial
			}
		}
	}

	// 5. Inquire ChassisTypes from Win32_SystemEnclosure
	var encList []Win32_SystemEnclosure
	var chassisTypes []int32
	qEnc := "SELECT ChassisTypes, Manufacturer, SerialNumber FROM Win32_SystemEnclosure"
	if err := wmi.Query(qEnc, &encList); err == nil && len(encList) > 0 {
		chassisTypes = encList[0].ChassisTypes
		if chassis.SerialNumber == "" {
			chassis.SerialNumber = CleanSerialNumber(encList[0].SerialNumber)
		}
	}

	// 6. Check battery existence for laptop verification
	hasBattery := false
	var batteries []Win32_Battery
	qBat := "SELECT DeviceID FROM Win32_Battery"
	if err := wmi.Query(qBat, &batteries); err == nil && len(batteries) > 0 {
		hasBattery = true
	}

	// 7. Infer Device Type
	chassis.DeviceType = inferDeviceType(chassisTypes, chassis.Model, hasBattery)

	return chassis, bios, nil
}

// inferDeviceType determines whether a machine is PC, LAPTOP, or TODO EN UNO
func inferDeviceType(chassisTypes []int32, model string, hasBattery bool) string {
	modelLower := strings.ToLower(model)

	// Check model keywords first
	if strings.Contains(modelLower, "all-in-one") || strings.Contains(modelLower, "aio") || strings.Contains(modelLower, "touchsmart") {
		return DeviceTypeAllInOne
	}
	if strings.Contains(modelLower, "laptop") || strings.Contains(modelLower, "notebook") ||
		strings.Contains(modelLower, "thinkpad") || strings.Contains(modelLower, "probook") ||
		strings.Contains(modelLower, "elitebook") || strings.Contains(modelLower, "ideapad") ||
		strings.Contains(modelLower, "latitude") || strings.Contains(modelLower, "inspiron") ||
		strings.Contains(modelLower, "zenbook") || strings.Contains(modelLower, "vivobook") {
		return DeviceTypeLaptop
	}

	// Check enclosure ChassisTypes codes
	// Ref: DMTF SMBIOS specification
	for _, ct := range chassisTypes {
		switch ct {
		// All in One
		case 13:
			return DeviceTypeAllInOne
		// Laptops, Notebooks, Portables, Convertibles
		case 8, 9, 10, 11, 12, 14, 30, 31, 32:
			return DeviceTypeLaptop
		// Servers
		case 17, 23, 28:
			return DeviceTypeServidor
		// Desktops
		case 3, 4, 5, 6, 7, 15, 16, 24:
			return DeviceTypePC
		}
	}

	if hasBattery {
		return DeviceTypeLaptop
	}

	return DeviceTypePC
}
