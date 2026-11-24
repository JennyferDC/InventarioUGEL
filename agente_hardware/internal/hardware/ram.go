package hardware

import (
	"fmt"

	"github.com/yusufpapurcu/wmi"
)

type Win32_PhysicalMemory struct {
	Capacity         uint64
	Speed            uint32
	Manufacturer     string
	PartNumber       string
	BankLabel        string
	SMBIOSMemoryType uint32
}

type Win32_PhysicalMemoryArray struct {
	MemoryDevices uint16
}

// CollectRAM extracts RAM modules, total capacity in GB, speed, and slots used
func CollectRAM() (RAMInfo, error) {
	ram := RAMInfo{
		Modules: []RAMModule{},
	}

	// 1. Query Win32_PhysicalMemory
	var sticks []Win32_PhysicalMemory
	qMem := "SELECT Capacity, Speed, Manufacturer, PartNumber, BankLabel, SMBIOSMemoryType FROM Win32_PhysicalMemory"

	if err := wmi.Query(qMem, &sticks); err != nil {
		return ram, err
	}

	var totalBytes uint64 = 0
	var maxSpeed uint32 = 0
	var ddrType string = ""

	for _, stick := range sticks {
		totalBytes += stick.Capacity
		if stick.Speed > maxSpeed {
			maxSpeed = stick.Speed
		}

		if ddrType == "" {
			ddrType = determineDDRType(stick.SMBIOSMemoryType, stick.Speed)
		}

		gb, _ := FormatRAMSize(stick.Capacity)
		mod := RAMModule{
			CapacityBytes: stick.Capacity,
			CapacityGB:    gb,
			SpeedMHz:      stick.Speed,
			Manufacturer:  CleanString(stick.Manufacturer),
			PartNumber:    CleanString(stick.PartNumber),
			BankLabel:     CleanString(stick.BankLabel),
		}
		ram.Modules = append(ram.Modules, mod)
	}

	ram.SlotsUsed = len(ram.Modules)
	ram.SpeedMHz = maxSpeed

	// 2. Query total slots from Win32_PhysicalMemoryArray
	var arrays []Win32_PhysicalMemoryArray
	qArr := "SELECT MemoryDevices FROM Win32_PhysicalMemoryArray"
	if err := wmi.Query(qArr, &arrays); err == nil && len(arrays) > 0 {
		ram.TotalSlots = int(arrays[0].MemoryDevices)
	}
	if ram.TotalSlots < ram.SlotsUsed {
		ram.TotalSlots = ram.SlotsUsed
	}

	// 3. Format total capacity
	ram.TotalCapacityGB, ram.TotalCapacityFormatted = FormatRAMSize(totalBytes)

	// 4. Formatted string (e.g. "16 GB DDR4 @ 2666 MHz (2 slots ocupados)")
	slotText := fmt.Sprintf("%d slot ocupado", ram.SlotsUsed)
	if ram.SlotsUsed > 1 {
		slotText = fmt.Sprintf("%d slots ocupados", ram.SlotsUsed)
	}

	speedText := ""
	if ram.SpeedMHz > 0 {
		speedText = fmt.Sprintf(" @ %d MHz", ram.SpeedMHz)
	}

	typeText := ""
	if ddrType != "" {
		typeText = " " + ddrType
	}

	ram.Formatted = fmt.Sprintf("%s%s%s (%s)", ram.TotalCapacityFormatted, typeText, speedText, slotText)

	return ram, nil
}

func determineDDRType(smbiosType uint32, speed uint32) string {
	switch smbiosType {
	case 24:
		return "DDR3"
	case 26:
		return "DDR4"
	case 34:
		return "LPDDR4"
	case 35:
		return "DDR5"
	case 36:
		return "LPDDR5"
	}

	// Heuristic by speed
	if speed >= 4800 {
		return "DDR5"
	} else if speed >= 2133 {
		return "DDR4"
	} else if speed >= 800 {
		return "DDR3"
	}

	return ""
}
