package hardware

import (
	"strings"
	"sync"
	"time"
)

// CollectHardware executes parallel WMI extraction modules and returns a complete HardwareReport
func CollectHardware() (*HardwareReport, error) {
	report := &HardwareReport{
		Characteristics: make([]Characteristic, 0),
	}

	var wg sync.WaitGroup
	var errChassis, errCPU, errRAM, errDisks, errMB, errOS, errNet error

	// Run extraction sub-modules concurrently for ultra-fast response (< 2 seconds)
	wg.Add(6)

	go func() {
		defer wg.Done()
		var bios BIOSInfo
		report.Chassis, bios, errChassis = CollectChassis()
		report.BIOS = bios
	}()

	go func() {
		defer wg.Done()
		report.CPU, errCPU = CollectCPU()
	}()

	go func() {
		defer wg.Done()
		report.RAM, errRAM = CollectRAM()
	}()

	go func() {
		defer wg.Done()
		report.Disks, errDisks = CollectDisks()
	}()

	go func() {
		defer wg.Done()
		report.Motherboard, errMB = CollectMotherboard()
	}()

	go func() {
		defer wg.Done()
		report.OS, errOS = CollectOS()
	}()

	wg.Wait()

	// Network is queried synchronously to ensure route/adapter stability
	report.Network, errNet = CollectNetwork()

	_ = errChassis
	_ = errCPU
	_ = errRAM
	_ = errDisks
	_ = errMB
	_ = errOS
	_ = errNet

	// Consolidate Top-Level Identifiers
	report.HostName = report.Chassis.HostName
	report.DeviceType = report.Chassis.DeviceType
	report.IPAddress = report.Network.IPAddress
	report.SerialNumber = report.Chassis.SerialNumber

	// If serial number is still empty, fallback to Motherboard serial or BIOS serial
	if report.SerialNumber == "" {
		if report.BIOS.SerialNumber != "" {
			report.SerialNumber = report.BIOS.SerialNumber
		} else if report.Motherboard.SerialNumber != "" {
			report.SerialNumber = report.Motherboard.SerialNumber
		}
	}

	// Build characteristics array matching UGEL Inventario
	report.Characteristics = buildCharacteristics(report)

	return report, nil
}

func buildCharacteristics(r *HardwareReport) []Characteristic {
	chars := make([]Characteristic, 0, 10)

	addChar := func(clave, valor string) {
		valor = CleanString(valor)
		if valor != "" {
			chars = append(chars, Characteristic{
				Clave: strings.ToLower(clave),
				Valor: valor,
			})
		}
	}

	// 1. Procesador
	if r.CPU.Formatted != "" {
		addChar("procesador", r.CPU.Formatted)
	}

	// 2. Memoria RAM
	if r.RAM.Formatted != "" {
		addChar("ram", r.RAM.Formatted)
	}

	// 3. Almacenamiento (Discos)
	if len(r.Disks) > 0 {
		diskDescriptions := make([]string, 0, len(r.Disks))
		for _, d := range r.Disks {
			diskDescriptions = append(diskDescriptions, d.Formatted)
		}
		addChar("almacenamiento", strings.Join(diskDescriptions, " + "))
	}

	// 4. Placa Madre
	if r.Motherboard.Formatted != "" {
		addChar("placa_madre", r.Motherboard.Formatted)
	}

	// 5. Sistema Operativo
	if r.OS.Formatted != "" {
		addChar("sistema_operativo", r.OS.Formatted)
	}

	// 6. MAC Address
	if r.Network.MACAddress != "" {
		addChar("mac", r.Network.MACAddress)
	}

	// 7. Marca y Modelo
	if r.Chassis.Manufacturer != "" {
		addChar("marca", r.Chassis.Manufacturer)
	}
	if r.Chassis.Model != "" {
		addChar("modelo", r.Chassis.Model)
	}

	return chars
}

// MeasureExecutionTime executes a function and returns elapsed duration
func MeasureExecutionTime(fn func()) time.Duration {
	start := time.Now()
	fn()
	return time.Since(start)
}
