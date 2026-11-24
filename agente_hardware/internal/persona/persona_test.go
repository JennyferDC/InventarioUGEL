package persona

import (
	"strings"
	"testing"
)

func TestFormatNombreCompleto(t *testing.T) {
	cases := []struct {
		input    string
		expected string
	}{
		{"juan perez", "Juan Perez"},
		{"  MARIA   ELENA   LOPEZ  ", "Maria Elena Lopez"},
		{"carlos álvarez ñúñez", "Carlos Álvarez Ñúñez"},
		{"", ""},
		{"   ", ""},
	}

	for _, c := range cases {
		got := FormatNombreCompleto(c.input)
		if got != c.expected {
			t.Errorf("FormatNombreCompleto(%q) = %q; expected %q", c.input, got, c.expected)
		}
	}
}

func TestCleanCelularDigits(t *testing.T) {
	cases := []struct {
		input    string
		expected string
	}{
		{"987654321", "987654321"},
		{"987-654-321", "987654321"},
		{"+51 987 654 321", "51987654321"},
		{"abc", ""},
	}

	for _, c := range cases {
		got := CleanCelularDigits(c.input)
		if got != c.expected {
			t.Errorf("CleanCelularDigits(%q) = %q; expected %q", c.input, got, c.expected)
		}
	}
}

func TestFormatCelular(t *testing.T) {
	got := FormatCelular("987654321")
	expected := "987 654 321"
	if got != expected {
		t.Errorf("FormatCelular('987654321') = %q; expected %q", got, expected)
	}
}

func TestValidatePersona(t *testing.T) {
	// 1. Valid Persona
	valid := &Persona{
		NombreCompleto: "Juan Perez Ramos",
		IDOficina:      3,
		Cargo:          "Especialista en Sistemas",
		Celular:        "987654321",
		Correo:         "jperez@ugelhuanuco.gob.pe",
		Estado:         "ACTIVO",
	}
	if err := ValidatePersona(valid); err != nil {
		t.Errorf("Expected valid persona, got error: %v", err)
	}

	// 2. Missing Nombre Completo
	invalidName := &Persona{
		NombreCompleto: "",
		IDOficina:      3,
	}
	if err := ValidatePersona(invalidName); err == nil || !strings.Contains(err.Error(), "nombre completo es obligatorio") {
		t.Errorf("Expected name required error, got %v", err)
	}

	// 3. Missing Oficina
	invalidOffice := &Persona{
		NombreCompleto: "Maria Lopez",
		IDOficina:      0,
	}
	if err := ValidatePersona(invalidOffice); err == nil || !strings.Contains(err.Error(), "oficina es obligatoria") {
		t.Errorf("Expected office required error, got %v", err)
	}

	// 4. Invalid Celular (less than 9 digits)
	invalidCel := &Persona{
		NombreCompleto: "Maria Lopez",
		IDOficina:      1,
		Celular:        "9876543",
	}
	if err := ValidatePersona(invalidCel); err == nil || !strings.Contains(err.Error(), "exactamente 9 dígitos") {
		t.Errorf("Expected 9-digit cellphone error, got %v", err)
	}

	// 5. Invalid Correo
	invalidEmail := &Persona{
		NombreCompleto: "Maria Lopez",
		IDOficina:      1,
		Correo:         "correo-invalido-sin-arroba",
	}
	if err := ValidatePersona(invalidEmail); err == nil || !strings.Contains(err.Error(), "no tiene un formato válido") {
		t.Errorf("Expected invalid email error, got %v", err)
	}
}

func TestOficinasCatalog(t *testing.T) {
	catalog := GetDefaultOficinas()
	if len(catalog) == 0 {
		t.Fatal("Default oficinas catalog should not be empty")
	}

	// Search by ID
	of := FindOficinaByID(10, catalog)
	if of == nil || of.Nombre != "Informática" {
		t.Errorf("Expected office ID 10 to be Informática, got %v", of)
	}

	// Search by Keyword
	matches := SearchOficinas("sistemas", catalog)
	// Informática mentions "sistemas" in description
	if len(matches) == 0 {
		// Try search by name "Infor"
		matches = SearchOficinas("infor", catalog)
		if len(matches) == 0 {
			t.Errorf("SearchOficinas('infor') should find Informática")
		}
	}
}
