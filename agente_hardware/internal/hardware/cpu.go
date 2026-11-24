package hardware

import (
	"fmt"
	"strings"

	"github.com/yusufpapurcu/wmi"
)

type Win32_Processor struct {
	Name                      string
	Manufacturer              string
	NumberOfCores             uint32
	NumberOfLogicalProcessors uint32
	MaxClockSpeed             uint32
}

// CollectCPU extracts CPU technical information using Win32_Processor
func CollectCPU() (CPUInfo, error) {
	var procs []Win32_Processor
	q := "SELECT Name, Manufacturer, NumberOfCores, NumberOfLogicalProcessors, MaxClockSpeed FROM Win32_Processor"

	cpu := CPUInfo{
		Name: "Procesador Desconocido",
	}

	if err := wmi.Query(q, &procs); err != nil || len(procs) == 0 {
		return cpu, err
	}

	primary := procs[0]
	cleanedName := CleanString(primary.Name)
	// Clean redundant vendor prefixes if repeated
	cleanedName = strings.ReplaceAll(cleanedName, "(R)", "")
	cleanedName = strings.ReplaceAll(cleanedName, "(TM)", "")
	cleanedName = CleanString(cleanedName)

	cpu.Name = cleanedName
	cpu.Manufacturer = CleanString(primary.Manufacturer)
	cpu.NumberOfCores = primary.NumberOfCores
	cpu.NumberOfLogicalProcessors = primary.NumberOfLogicalProcessors
	cpu.MaxClockSpeedMHz = primary.MaxClockSpeed

	// Build friendly commercial string
	// e.g. "Intel Core i7-8550U CPU @ 1.80GHz (4 Núcleos / 8 Hilos)"
	coresInfo := ""
	if cpu.NumberOfCores > 0 && cpu.NumberOfLogicalProcessors > 0 {
		coresInfo = fmt.Sprintf(" (%d Núcleos / %d Hilos)", cpu.NumberOfCores, cpu.NumberOfLogicalProcessors)
	}

	cpu.Formatted = fmt.Sprintf("%s%s", cpu.Name, coresInfo)

	return cpu, nil
}
