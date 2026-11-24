package hardware

import (
	"fmt"

	"github.com/yusufpapurcu/wmi"
)

type Win32_BaseBoard struct {
	Manufacturer string
	Product      string
	SerialNumber string
	Version      string
}

// CollectMotherboard extracts motherboard manufacturer, product model, and serial number
func CollectMotherboard() (MotherboardInfo, error) {
	var boards []Win32_BaseBoard
	q := "SELECT Manufacturer, Product, SerialNumber, Version FROM Win32_BaseBoard"

	mb := MotherboardInfo{
		Manufacturer: "Genérica",
		Product:      "Desconocida",
	}

	if err := wmi.Query(q, &boards); err != nil || len(boards) == 0 {
		return mb, err
	}

	primary := boards[0]
	mb.Manufacturer = CleanString(primary.Manufacturer)
	mb.Product = CleanString(primary.Product)
	mb.SerialNumber = CleanSerialNumber(primary.SerialNumber)
	mb.Version = CleanString(primary.Version)

	if mb.Manufacturer != "" && mb.Product != "" {
		mb.Formatted = fmt.Sprintf("%s %s", mb.Manufacturer, mb.Product)
	} else if mb.Product != "" {
		mb.Formatted = mb.Product
	} else {
		mb.Formatted = mb.Manufacturer
	}

	return mb, nil
}
