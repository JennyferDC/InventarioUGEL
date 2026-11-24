package hardware

// DeviceType constants
const (
	DeviceTypePC        = "PC"
	DeviceTypeLaptop    = "LAPTOP"
	DeviceTypeAllInOne  = "TODO EN UNO"
	DeviceTypeServidor  = "SERVIDOR"
)

// Characteristic represents a key-value attribute matching the DB table 'caracteristica_equipos'
type Characteristic struct {
	Clave string `json:"clave"`
	Valor string `json:"valor"`
}

// ChassisInfo holds physical enclosure and machine identity information
type ChassisInfo struct {
	HostName     string `json:"nombre_host"`
	Manufacturer string `json:"fabricante"`
	Model        string `json:"modelo"`
	DeviceType   string `json:"tipo_equipo"`
	SerialNumber string `json:"numero_serie"`
}

// CPUInfo holds processor details
type CPUInfo struct {
	Name                      string `json:"nombre"`
	Manufacturer              string `json:"fabricante"`
	NumberOfCores             uint32 `json:"nucleos_fisicos"`
	NumberOfLogicalProcessors uint32 `json:"nucleos_logicos"`
	MaxClockSpeedMHz          uint32 `json:"frecuencia_mhz"`
	Formatted                 string `json:"formato_legible"`
}

// RAMModule represents an individual physical memory stick
type RAMModule struct {
	CapacityBytes uint64 `json:"capacidad_bytes"`
	CapacityGB    float64 `json:"capacidad_gb"`
	SpeedMHz      uint32 `json:"velocidad_mhz"`
	Manufacturer  string `json:"fabricante"`
	PartNumber    string `json:"numero_parte"`
	BankLabel     string `json:"banco_slot"`
}

// RAMInfo holds combined RAM memory statistics
type RAMInfo struct {
	TotalCapacityGB        float64     `json:"capacidad_total_gb"`
	TotalCapacityFormatted string      `json:"capacidad_formateada"`
	SpeedMHz               uint32      `json:"velocidad_mhz"`
	SlotsUsed              int         `json:"slots_ocupados"`
	TotalSlots             int         `json:"slots_totales"`
	Modules                []RAMModule `json:"modulos"`
	Formatted              string      `json:"formato_legible"`
}

// DiskDrive represents a physical storage drive
type DiskDrive struct {
	Model         string  `json:"modelo"`
	SizeBytes     uint64  `json:"capacidad_bytes"`
	SizeGB        float64 `json:"capacidad_gb"`
	SizeFormatted string  `json:"capacidad_formateada"`
	MediaType     string  `json:"tipo_medio"` // SSD, HDD, NVMe, Desconocido
	InterfaceType string  `json:"interfaz"`   // SATA, NVMe, SCSI, USB
	SerialNumber  string  `json:"numero_serie"`
	Formatted     string  `json:"formato_legible"`
}

// MotherboardInfo holds baseboard hardware specs
type MotherboardInfo struct {
	Manufacturer string `json:"fabricante"`
	Product      string `json:"producto"`
	SerialNumber string `json:"numero_serie"`
	Version      string `json:"version"`
	Formatted    string `json:"formato_legible"`
}

// BIOSInfo holds BIOS and firmware information
type BIOSInfo struct {
	Manufacturer string `json:"fabricante"`
	Version      string `json:"version"`
	ReleaseDate  string `json:"fecha_lanzamiento"`
	SerialNumber string `json:"numero_serie"`
}

// OSInfo holds operating system details
type OSInfo struct {
	Caption      string `json:"nombre_sistema"`
	Version      string `json:"version"`
	BuildNumber  string `json:"compilacion"`
	Architecture string `json:"arquitectura"` // 64-bit o 32-bit
	InstallDate  string `json:"fecha_instalacion,omitempty"`
	Formatted    string `json:"formato_legible"`
}

// NetworkInfo holds primary network connection details
type NetworkInfo struct {
	IPAddress    string `json:"direccion_ip"`
	MACAddress   string `json:"direccion_mac"`
	AdapterName  string `json:"nombre_adaptador"`
	Description  string `json:"descripcion"`
	IsWireless   bool   `json:"es_inalambrico"`
}

// HardwareReport represents the complete hardware blueprint of the machine
type HardwareReport struct {
	HostName        string           `json:"nombre"`
	DeviceType      string           `json:"tipo"`
	IPAddress       string           `json:"ip"`
	SerialNumber    string           `json:"cod_serial"`
	Chassis         ChassisInfo      `json:"chasis"`
	CPU             CPUInfo          `json:"procesador"`
	RAM             RAMInfo          `json:"memoria_ram"`
	Disks           []DiskDrive      `json:"discos"`
	Motherboard     MotherboardInfo  `json:"placa_madre"`
	BIOS            BIOSInfo         `json:"bios"`
	OS              OSInfo           `json:"sistema_operativo"`
	Network         NetworkInfo      `json:"red"`
	Characteristics []Characteristic `json:"caracteristicas"`
}
