package processor

import (
	"encoding/json"
	"strings"
	"testing"

	"ugel-inventario-agente/internal/hardware"
	"ugel-inventario-agente/internal/persona"
	"ugel-inventario-agente/internal/programs"
)

func TestBuildUnifiedPayload(t *testing.T) {
	p := &persona.Persona{
		NombreCompleto: "Juan Perez Ramos",
		IDOficina:      3,
		Cargo:          "Especialista en Sistemas",
		Celular:        "987654321",
		Correo:         "jperez@ugelhuanuco.gob.pe",
		Estado:         "ACTIVO",
	}

	hw := &hardware.HardwareReport{
		HostName:     "DESKTOP-INFORMATICA",
		DeviceType:   "PC",
		IPAddress:    "192.168.1.45",
		SerialNumber: "PF2X9ABC",
		CPU: hardware.CPUInfo{
			Formatted: "Intel Core i5-10400 @ 2.90GHz",
		},
		RAM: hardware.RAMInfo{
			Formatted: "16 GB DDR4 @ 2666 MHz",
		},
		Disks: []hardware.DiskDrive{
			{Formatted: "SSD Kingston 480GB"},
		},
		Motherboard: hardware.MotherboardInfo{
			Formatted: "Gigabyte H410M H V2",
		},
		OS: hardware.OSInfo{
			Formatted: "Windows 10 Pro 64-bit",
		},
		Network: hardware.NetworkInfo{
			IPAddress:  "192.168.1.45",
			MACAddress: "E0:D5:5E:2B:1A:09",
		},
	}

	progs := []programs.InstalledProgram{
		{Name: "Google Chrome", Version: "120.0", Publisher: "Google LLC"},
		{Name: "Microsoft Office Professional Plus 2021", Version: "16.0", Publisher: "Microsoft Corporation"},
	}

	payload, err := BuildUnifiedPayload(p, hw, progs)
	if err != nil {
		t.Fatalf("BuildUnifiedPayload failed: %v", err)
	}

	if len(payload.Equipo.Programas) != 2 {
		t.Errorf("Expected 2 programas, got %d", len(payload.Equipo.Programas))
	}

	if payload.Persona.NombreCompleto != "Juan Perez Ramos" {
		t.Errorf("Expected persona name 'Juan Perez Ramos', got %s", payload.Persona.NombreCompleto)
	}

	if payload.Equipo.Nombre != "DESKTOP-INFORMATICA" {
		t.Errorf("Expected equipo nombre 'DESKTOP-INFORMATICA', got %s", payload.Equipo.Nombre)
	}

	if payload.Equipo.CodSerial != "PF2X9ABC" {
		t.Errorf("Expected cod_serial 'PF2X9ABC', got %s", payload.Equipo.CodSerial)
	}

	// Verify required characteristics
	keys := make(map[string]string)
	for _, c := range payload.Equipo.Caracteristicas {
		keys[c.Clave] = c.Valor
	}

	expectedKeys := []string{"procesador", "ram", "almacenamiento", "placa_madre", "sistema_operativo", "mac"}
	for _, k := range expectedKeys {
		if _, ok := keys[k]; !ok {
			t.Errorf("Missing expected characteristic key: %s", k)
		}
	}

	// Verify JSON serialization
	jsonBytes, err := ToJSON(payload, true)
	if err != nil {
		t.Fatalf("ToJSON failed: %v", err)
	}

	jsonStr := string(jsonBytes)
	if !strings.Contains(jsonStr, `"persona"`) || !strings.Contains(jsonStr, `"equipo"`) {
		t.Errorf("JSON output missing persona or equipo envelope")
	}

	// Unmarshal to generic map to ensure valid JSON syntax
	var parsed map[string]interface{}
	if err := json.Unmarshal(jsonBytes, &parsed); err != nil {
		t.Fatalf("Generated JSON is not valid JSON: %v", err)
	}
}
