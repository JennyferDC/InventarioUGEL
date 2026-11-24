package persona

import (
	"encoding/json"
	"os"
	"strings"
)

// DefaultOficinasCatalog contains the official offices of UGEL Huánuco based on institutional seeders
var DefaultOficinasCatalog = []Oficina{
	{ID: 1, Nombre: "Dirección", AreaNombre: "Dirección", Descripcion: "Oficina principal de Dirección."},
	{ID: 2, Nombre: "Procesos Administrativos", AreaNombre: "Unidad de R.R.H.H.", Descripcion: "Gestión de procesos administrativos disciplinarios."},
	{ID: 3, Nombre: "Planillas", AreaNombre: "Unidad de R.R.H.H.", Descripcion: "Procesamiento de planillas de pago y beneficios."},
	{ID: 4, Nombre: "Escalafón", AreaNombre: "Unidad de R.R.H.H.", Descripcion: "Registro y control del historial laboral y legajos."},
	{ID: 5, Nombre: "Bienestar Social", AreaNombre: "Unidad de R.R.H.H.", Descripcion: "Servicios de bienestar, salud y asistencia social."},
	{ID: 6, Nombre: "Abastecimiento", AreaNombre: "Unidad de Gestión Administrativa", Descripcion: "Adquisiciones, contrataciones y distribución de bienes."},
	{ID: 7, Nombre: "Contabilidad", AreaNombre: "Unidad de Gestión Administrativa", Descripcion: "Registro contable de operaciones financieras."},
	{ID: 8, Nombre: "Tesorería", AreaNombre: "Unidad de Gestión Administrativa", Descripcion: "Gestión de caja, pagos y giros."},
	{ID: 9, Nombre: "Patrimonio", AreaNombre: "Unidad de Gestión Administrativa", Descripcion: "Control y registro de bienes patrimoniales."},
	{ID: 10, Nombre: "Informática", AreaNombre: "Unidad de Gestión Administrativa", Descripcion: "Soporte técnico, redes y sistemas informáticos."},
	{ID: 11, Nombre: "Inicial - Especial", AreaNombre: "Unidad de Gestión Pedagógica", Descripcion: "Acompañamiento técnico pedagógico inicial/especial."},
	{ID: 12, Nombre: "Primaria", AreaNombre: "Unidad de Gestión Pedagógica", Descripcion: "Monitoreo y supervisión del nivel primario."},
	{ID: 13, Nombre: "Secundaria", AreaNombre: "Unidad de Gestión Pedagógica", Descripcion: "Monitoreo y supervisión del nivel secundario."},
	{ID: 14, Nombre: "Secretaría Jurídica", AreaNombre: "Unidad de Asesoría Jurídica", Descripcion: "Trámite de expedientes y resoluciones legales."},
	{ID: 15, Nombre: "Planeación y Racionalización", AreaNombre: "Unidad de Planeamiento", Descripcion: "Planificación estratégica y racionalización."},
	{ID: 16, Nombre: "Presupuesto", AreaNombre: "Unidad de Planeamiento", Descripcion: "Programación y formulación presupuestal."},
	{ID: 17, Nombre: "Infraestructura", AreaNombre: "Unidad de Planeamiento", Descripcion: "Supervisión de proyectos de infraestructura."},
	{ID: 18, Nombre: "SIAGIE Estadística", AreaNombre: "Unidad de Planeamiento", Descripcion: "Sistemas de información y estadísticas."},
}

// GetDefaultOficinas returns a copy of the default UGEL oficinas catalog
func GetDefaultOficinas() []Oficina {
	list := make([]Oficina, len(DefaultOficinasCatalog))
	copy(list, DefaultOficinasCatalog)
	return list
}

// FindOficinaByID searches for an office by its numeric ID
func FindOficinaByID(id int, catalog []Oficina) *Oficina {
	for i := range catalog {
		if catalog[i].ID == id {
			return &catalog[i]
		}
	}
	return nil
}

// SearchOficinas searches for offices matching a keyword in name or area
func SearchOficinas(keyword string, catalog []Oficina) []Oficina {
	keyword = strings.ToLower(strings.TrimSpace(keyword))
	if keyword == "" {
		return catalog
	}

	var results []Oficina
	for _, of := range catalog {
		if strings.Contains(strings.ToLower(of.Nombre), keyword) ||
			strings.Contains(strings.ToLower(of.AreaNombre), keyword) {
			results = append(results, of)
		}
	}
	return results
}

// LoadOficinasFromCache loads cached offices JSON from disk
func LoadOficinasFromCache(cachePath string) ([]Oficina, error) {
	bytes, err := os.ReadFile(cachePath)
	if err != nil {
		return nil, err
	}

	var list []Oficina
	if err := json.Unmarshal(bytes, &list); err != nil {
		return nil, err
	}

	if len(list) == 0 {
		return GetDefaultOficinas(), nil
	}

	return list, nil
}

// SaveOficinasToCache writes the offices catalog to disk as JSON
func SaveOficinasToCache(oficinas []Oficina, cachePath string) error {
	bytes, err := json.MarshalIndent(oficinas, "", "  ")
	if err != nil {
		return err
	}
	return os.WriteFile(cachePath, bytes, 0644)
}
