package processor

import (
	"encoding/json"
	"errors"
	"os"
	"strings"
	"time"

	"ugel-inventario-agente/internal/hardware"
	"ugel-inventario-agente/internal/persona"
	"ugel-inventario-agente/internal/programs"
)

// CharacteristicPayload represents an entry in the 'caracteristicas' list
type CharacteristicPayload struct {
	Clave string `json:"clave"`
	Valor string `json:"valor"`
}

// ProgramPayload represents an installed software application detected on the PC
type ProgramPayload struct {
	Nombre           string `json:"nombre"`
	Version          string `json:"version,omitempty"`
	Fabricante       string `json:"fabricante,omitempty"`
	FechaInstalacion string `json:"fecha_instalacion,omitempty"`
}

// EquipoPayload represents the hardware specification matching the plan & DB schema
type EquipoPayload struct {
	Nombre          string                  `json:"nombre"`
	Tipo            string                  `json:"tipo"`
	IP              string                  `json:"ip"`
	CodSerial       string                  `json:"cod_serial"`
	Caracteristicas []CharacteristicPayload `json:"caracteristicas"`
	Programas       []ProgramPayload        `json:"programas,omitempty"`
}

// PersonaPayload represents the user details matching Laravel Persona model
type PersonaPayload struct {
	NombreCompleto string `json:"nombre_completo"`
	IDOficina      int    `json:"id_oficina"`
	Cargo          string `json:"cargo,omitempty"`
	Celular        string `json:"celular,omitempty"`
	Correo         string `json:"correo,omitempty"`
}

// UnifiedPayload represents the full JSON envelope sent to the API
type UnifiedPayload struct {
	Persona   PersonaPayload `json:"persona"`
	Equipo    EquipoPayload  `json:"equipo"`
	CreatedAt string         `json:"created_at,omitempty"`
}

// BuildUnifiedPayload normalizes hardware data and unifies it with persona and installed software information
func BuildUnifiedPayload(p *persona.Persona, hw *hardware.HardwareReport, progs []programs.InstalledProgram) (*UnifiedPayload, error) {
	if p == nil {
		return nil, errors.New("los datos de la persona no pueden ser nulos")
	}
	if hw == nil {
		return nil, errors.New("los datos de hardware no pueden ser nulos")
	}

	// Validate persona before assembly
	if err := persona.ValidatePersona(p); err != nil {
		return nil, err
	}

	// 1. Prepare Persona section
	pPayload := PersonaPayload{
		NombreCompleto: p.NombreCompleto,
		IDOficina:      p.IDOficina,
		Cargo:          p.Cargo,
		Celular:        p.Celular,
		Correo:         p.Correo,
	}

	// 2. Prepare and normalize Equipo section
	nombreEquipo := hardware.CleanString(hw.HostName)
	if nombreEquipo == "" {
		nombreEquipo = "EQUIPO-UGEL"
	}

	tipoEquipo := hw.DeviceType
	if tipoEquipo == "" {
		tipoEquipo = hardware.DeviceTypePC
	}

	// Normalize Serial Number
	serial := hardware.CleanSerialNumber(hw.SerialNumber)
	if serial == "" && hw.Motherboard.SerialNumber != "" {
		serial = hardware.CleanSerialNumber(hw.Motherboard.SerialNumber)
	}

	// 3. Assemble and order Characteristics matching official inventory requirements
	caracteristicas := make([]CharacteristicPayload, 0, 10)

	addChar := func(clave, valor string) {
		valor = hardware.CleanString(valor)
		if valor != "" {
			caracteristicas = append(caracteristicas, CharacteristicPayload{
				Clave: strings.ToLower(clave),
				Valor: valor,
			})
		}
	}

	// Priority 1: Procesador
	if hw.CPU.Formatted != "" {
		addChar("procesador", hw.CPU.Formatted)
	} else if hw.CPU.Name != "" {
		addChar("procesador", hw.CPU.Name)
	}

	// Priority 2: RAM
	if hw.RAM.Formatted != "" {
		addChar("ram", hw.RAM.Formatted)
	} else if hw.RAM.TotalCapacityFormatted != "" {
		addChar("ram", hw.RAM.TotalCapacityFormatted)
	}

	// Priority 3: Almacenamiento
	if len(hw.Disks) > 0 {
		diskStrings := make([]string, 0, len(hw.Disks))
		for _, d := range hw.Disks {
			diskStrings = append(diskStrings, d.Formatted)
		}
		addChar("almacenamiento", strings.Join(diskStrings, " + "))
	}

	// Priority 4: Placa Madre
	if hw.Motherboard.Formatted != "" {
		addChar("placa_madre", hw.Motherboard.Formatted)
	}

	// Priority 5: Sistema Operativo
	if hw.OS.Formatted != "" {
		addChar("sistema_operativo", hw.OS.Formatted)
	} else if hw.OS.Caption != "" {
		addChar("sistema_operativo", hw.OS.Caption)
	}

	// Priority 6: Dirección MAC física
	if hw.Network.MACAddress != "" {
		addChar("mac", hw.Network.MACAddress)
	}

	// Priority 7: Marca y Modelo
	if hw.Chassis.Manufacturer != "" {
		addChar("marca", hw.Chassis.Manufacturer)
	}
	if hw.Chassis.Model != "" {
		addChar("modelo", hw.Chassis.Model)
	}

	// 4. Transformar programas instalados detectados
	progPayloads := make([]ProgramPayload, 0, len(progs))
	for _, pr := range progs {
		name := strings.TrimSpace(pr.Name)
		if name != "" {
			progPayloads = append(progPayloads, ProgramPayload{
				Nombre:           name,
				Version:          strings.TrimSpace(pr.Version),
				Fabricante:       strings.TrimSpace(pr.Publisher),
				FechaInstalacion: strings.TrimSpace(pr.InstallDate),
			})
		}
	}

	equipoPayload := EquipoPayload{
		Nombre:          nombreEquipo,
		Tipo:            tipoEquipo,
		IP:              hw.Network.IPAddress,
		CodSerial:       serial,
		Caracteristicas: caracteristicas,
		Programas:       progPayloads,
	}

	unified := &UnifiedPayload{
		Persona:   pPayload,
		Equipo:    equipoPayload,
		CreatedAt: time.Now().Format(time.RFC3339),
	}

	return unified, nil
}

// ToJSON serializes the UnifiedPayload to formatted or compact JSON
func ToJSON(payload *UnifiedPayload, indent bool) ([]byte, error) {
	if indent {
		return json.MarshalIndent(payload, "", "  ")
	}
	return json.Marshal(payload)
}

// SaveToFile writes the unified payload to a local JSON file (used for offline storage)
func SaveToFile(payload *UnifiedPayload, filePath string) error {
	data, err := ToJSON(payload, true)
	if err != nil {
		return err
	}
	return os.WriteFile(filePath, data, 0644)
}
