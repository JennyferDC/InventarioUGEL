package persona

import (
	"errors"
	"fmt"
	"regexp"
	"strings"
	"unicode"
)

var (
	spacesRegex = regexp.MustCompile(`\s+`)
	digitsRegex = regexp.MustCompile(`\D`)
	emailRegex  = regexp.MustCompile(`^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$`)
)

// FormatNombreCompleto trims and formats the full name to Title Case, supporting Spanish accents
func FormatNombreCompleto(nombre string) string {
	nombre = strings.TrimSpace(nombre)
	if nombre == "" {
		return ""
	}
	nombre = spacesRegex.ReplaceAllString(nombre, " ")

	words := strings.Fields(nombre)
	titledWords := make([]string, len(words))

	for i, w := range words {
		lower := strings.ToLower(w)
		runes := []rune(lower)
		if len(runes) > 0 {
			runes[0] = unicode.ToUpper(runes[0])
		}
		titledWords[i] = string(runes)
	}

	return strings.Join(titledWords, " ")
}

// CleanCelularDigits extracts only numeric digits from a phone/cellphone string
func CleanCelularDigits(celular string) string {
	if celular == "" {
		return ""
	}
	return digitsRegex.ReplaceAllString(celular, "")
}

// FormatCelular formats a 9-digit cellphone number into 'XXX XXX XXX' format
func FormatCelular(celular string) string {
	digits := CleanCelularDigits(celular)
	if len(digits) == 9 {
		return fmt.Sprintf("%s %s %s", digits[0:3], digits[3:6], digits[6:9])
	}
	return digits
}

// CleanCorreo normalizes email address (trimmed, lowercase)
func CleanCorreo(correo string) string {
	return strings.ToLower(strings.TrimSpace(correo))
}

// IsValidCorreo checks whether an email address has a valid syntax and reasonable length
func IsValidCorreo(correo string) bool {
	correo = CleanCorreo(correo)
	return emailRegex.MatchString(correo) && len(correo) <= 255
}

// ValidatePersona performs validation matching Laravel's PersonaController rules
func ValidatePersona(p *Persona) error {
	if p == nil {
		return errors.New("los datos de la persona son requeridos")
	}

	// 1. Validar nombre completo
	p.NombreCompleto = FormatNombreCompleto(p.NombreCompleto)
	if p.NombreCompleto == "" {
		return errors.New("el nombre completo es obligatorio")
	}
	if len(p.NombreCompleto) > 255 {
		return errors.New("el nombre completo no debe exceder 255 caracteres")
	}

	// 2. Validar oficina
	if p.IDOficina <= 0 {
		return errors.New("la oficina es obligatoria")
	}

	// 3. Validar cargo (opcional, max 255)
	p.Cargo = strings.TrimSpace(p.Cargo)
	if len(p.Cargo) > 255 {
		return errors.New("el cargo no debe exceder 255 caracteres")
	}

	// 4. Validar celular (opcional, pero si existe debe tener exactamente 9 dígitos)
	if p.Celular != "" {
		digits := CleanCelularDigits(p.Celular)
		if len(digits) != 9 {
			return errors.New("el número de celular debe tener exactamente 9 dígitos")
		}
		p.Celular = digits
	}

	// 5. Validar correo (opcional, pero si existe debe ser formato de correo válido)
	if p.Correo != "" {
		p.Correo = CleanCorreo(p.Correo)
		if !emailRegex.MatchString(p.Correo) {
			return errors.New("el correo electrónico no tiene un formato válido")
		}
		if len(p.Correo) > 255 {
			return errors.New("el correo no debe exceder 255 caracteres")
		}
	}

	// 6. Validar estado
	if p.Estado == "" {
		p.Estado = "ACTIVO"
	}
	p.Estado = strings.ToUpper(strings.TrimSpace(p.Estado))
	if p.Estado != "ACTIVO" && p.Estado != "INACTIVO" {
		return errors.New("el estado debe ser ACTIVO o INACTIVO")
	}

	return nil
}
