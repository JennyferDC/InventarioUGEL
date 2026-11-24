package persona

// Persona represents an institutional staff member matching the 'personas' table in Laravel
type Persona struct {
	NombreCompleto string `json:"nombre_completo"`
	IDOficina      int    `json:"id_oficina"`
	Cargo          string `json:"cargo,omitempty"`
	Celular        string `json:"celular,omitempty"`
	Correo         string `json:"correo,omitempty"`
	Estado         string `json:"estado"` // Default: ACTIVO
}

// Oficina represents an institutional office matching the 'oficinas' table in Laravel
type Oficina struct {
	ID          int    `json:"id"`
	Nombre      string `json:"nombre"`
	Descripcion string `json:"descripcion,omitempty"`
	AreaNombre  string `json:"area_nombre,omitempty"`
}

// NewPersona creates a new persona with default 'ACTIVO' state
func NewPersona() *Persona {
	return &Persona{
		Estado: "ACTIVO",
	}
}
