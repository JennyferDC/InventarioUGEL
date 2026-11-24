package persona

import (
	"bufio"
	"fmt"
	"io"
	"strconv"
	"strings"
)

// CapturePersonaInteractive prompts the user in the console for persona details with real-time validation
func CapturePersonaInteractive(r io.Reader, w io.Writer, catalog []Oficina) (*Persona, error) {
	reader := bufio.NewReader(r)
	if len(catalog) == 0 {
		catalog = GetDefaultOficinas()
	}

	for {
		fmt.Fprintln(w, "\n==================================================================")
		fmt.Fprintln(w, "        REGISTRO DEL RESPONSABLE DEL EQUIPO - UGEL HUÁNUCO        ")
		fmt.Fprintln(w, "==================================================================")
		fmt.Fprintln(w, "Complete los datos del personal a cargo de esta computadora.")
		fmt.Fprintln(w, "Campos con (*) son obligatorios.")
		fmt.Fprintln(w)

		// 1. Nombre Completo
		nombre := ""
		for {
			fmt.Fprint(w, "(*) Nombre Completo (ej. Juan Perez Ramos): ")
			input, err := reader.ReadString('\n')
			if err != nil {
				return nil, err
			}
			nombre = FormatNombreCompleto(strings.TrimSpace(input))
			if nombre == "" {
				fmt.Fprintln(w, "    [ERROR]: El nombre completo es obligatorio. Intente nuevamente.")
				continue
			}
			fmt.Fprintf(w, "    -> Registrado como: %s\n\n", nombre)
			break
		}

		// 2. Selección de Oficina
		var selectedOficina *Oficina
		for {
			fmt.Fprintln(w, "(*) Seleccione la Oficina del usuario:")
			for _, of := range catalog {
				fmt.Fprintf(w, "    [%2d] %-30s (%s)\n", of.ID, of.Nombre, of.AreaNombre)
			}
			fmt.Fprintf(w, "    Ingrese el NÚMERO de oficina [1 - %d] o escriba para buscar: ", len(catalog))
			input, err := reader.ReadString('\n')
			if err != nil {
				return nil, err
			}
			trimmed := strings.TrimSpace(input)

			// Try by ID
			if id, err := strconv.Atoi(trimmed); err == nil {
				if of := FindOficinaByID(id, catalog); of != nil {
					selectedOficina = of
					fmt.Fprintf(w, "    -> Oficina seleccionada: %s (%s)\n\n", of.Nombre, of.AreaNombre)
					break
				}
			}

			// Try by search name
			matches := SearchOficinas(trimmed, catalog)
			if len(matches) == 1 {
				selectedOficina = &matches[0]
				fmt.Fprintf(w, "    -> Oficina seleccionada: %s (%s)\n\n", selectedOficina.Nombre, selectedOficina.AreaNombre)
				break
			} else if len(matches) > 1 {
				fmt.Fprintln(w, "    Se encontraron varias oficinas:")
				for _, m := range matches {
					fmt.Fprintf(w, "      [%2d] %s (%s)\n", m.ID, m.Nombre, m.AreaNombre)
				}
				fmt.Fprintln(w, "    Por favor ingrese el número exacto.")
				continue
			}

			fmt.Fprintln(w, "    [ERROR]: Oficina no válida. Seleccione una opción de la lista.")
		}

		// 3. Cargo (Opcional)
		fmt.Fprint(w, "( ) Cargo o Función (Opcional - Presione Enter para omitir): ")
		cargoInput, _ := reader.ReadString('\n')
		cargo := strings.TrimSpace(cargoInput)
		if cargo != "" {
			cargo = FormatNombreCompleto(cargo)
			fmt.Fprintf(w, "    -> Cargo: %s\n\n", cargo)
		} else {
			fmt.Fprintln(w, "    -> Cargo: (Sin especificar)")
			fmt.Fprintln(w)
		}

		// 4. Celular (Opcional, 9 dígitos)
		celular := ""
		for {
			fmt.Fprint(w, "( ) Número de Celular (Opcional - 9 dígitos): ")
			celInput, _ := reader.ReadString('\n')
			celTrim := strings.TrimSpace(celInput)
			if celTrim == "" {
				fmt.Fprintln(w, "    -> Celular: (Sin especificar)")
				fmt.Fprintln(w)
				break
			}

			digits := CleanCelularDigits(celTrim)
			if len(digits) != 9 {
				fmt.Fprintln(w, "    [ERROR]: El celular debe contener exactamente 9 dígitos. Intente de nuevo o Enter para omitir.")
				continue
			}
			celular = digits
			fmt.Fprintf(w, "    -> Celular: %s\n\n", FormatCelular(celular))
			break
		}

		// 5. Correo institucional (Opcional)
		correo := ""
		for {
			fmt.Fprint(w, "( ) Correo Electrónico (Opcional - ej. usuario@ugelhuanuco.gob.pe): ")
			mailInput, _ := reader.ReadString('\n')
			mailTrim := strings.TrimSpace(mailInput)
			if mailTrim == "" {
				fmt.Fprintln(w, "    -> Correo: (Sin especificar)")
				fmt.Fprintln(w)
				break
			}

			cleanedMail := CleanCorreo(mailTrim)
			if !emailRegex.MatchString(cleanedMail) {
				fmt.Fprintln(w, "    [ERROR]: El formato de correo no es válido. Intente de nuevo o Enter para omitir.")
				continue
			}
			correo = cleanedMail
			fmt.Fprintf(w, "    -> Correo: %s\n\n", correo)
			break
		}

		// Resumen de Confirmación
		p := &Persona{
			NombreCompleto: nombre,
			IDOficina:      selectedOficina.ID,
			Cargo:          cargo,
			Celular:        celular,
			Correo:         correo,
			Estado:         "ACTIVO",
		}

		if err := ValidatePersona(p); err != nil {
			fmt.Fprintf(w, "[ERROR DE VALIDACIÓN]: %v\n", err)
			continue
		}

		fmt.Fprintln(w, "------------------------------------------------------------------")
		fmt.Fprintln(w, "RESUMEN DE DATOS DEL RESPONSABLE:")
		fmt.Fprintf(w, "  * Nombre Completo : %s\n", p.NombreCompleto)
		fmt.Fprintf(w, "  * Oficina         : %s (ID: %d)\n", selectedOficina.Nombre, p.IDOficina)
		fmt.Fprintf(w, "  * Cargo           : %s\n", fallback(p.Cargo, "(No especificado)"))
		fmt.Fprintf(w, "  * Celular         : %s\n", fallback(FormatCelular(p.Celular), "(No especificado)"))
		fmt.Fprintf(w, "  * Correo          : %s\n", fallback(p.Correo, "(No especificado)"))
		fmt.Fprintf(w, "  * Estado          : %s\n", p.Estado)
		fmt.Fprintln(w, "------------------------------------------------------------------")
		fmt.Fprint(w, "¿Los datos son correctos? [S/n]: ")

		confirmInput, _ := reader.ReadString('\n')
		confirm := strings.ToLower(strings.TrimSpace(confirmInput))
		if confirm == "" || confirm == "s" || confirm == "si" || confirm == "y" || confirm == "yes" {
			return p, nil
		}

		fmt.Fprintln(w)
		fmt.Fprintln(w, "Reiniciando captura de datos...")
		fmt.Fprintln(w)
	}
}

func fallback(val, def string) string {
	if strings.TrimSpace(val) == "" {
		return def
	}
	return val
}
