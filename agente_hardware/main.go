package main

import (
	"encoding/json"
	"flag"
	"fmt"
	"os"
	"time"

	"ugel-inventario-agente/internal/client"
	"ugel-inventario-agente/internal/gui"
	"ugel-inventario-agente/internal/hardware"
	"ugel-inventario-agente/internal/persona"
	"ugel-inventario-agente/internal/processor"
	"ugel-inventario-agente/internal/programs"
)

func syncOfflineBackupIfExists(serverURL, agentToken string) bool {
	offlineFile := "inventario_pendiente.json"
	if _, err := os.Stat(offlineFile); err == nil {
		fmt.Printf("\n[AVISO]: Se encontró un inventario pendiente offline ('%s') en la memoria USB.\n", offlineFile)
		data, errRead := os.ReadFile(offlineFile)
		if errRead == nil {
			var pendingPayload processor.UnifiedPayload
			if errJSON := json.Unmarshal(data, &pendingPayload); errJSON == nil {
				fmt.Print(">> Sincronizando inventario pendiente con el servidor UGEL... ")
				resp, errSend := client.SendRegistration(serverURL, agentToken, &pendingPayload)
				if errSend == nil {
					fmt.Printf("¡ÉXITO!\n>> Servidor: %s - %s\n", resp.Action, resp.Message)
					archiveName := fmt.Sprintf("inventario_sincronizado_%d.json", time.Now().Unix())
					_ = os.Rename(offlineFile, archiveName)
					fmt.Printf(">> Archivo pendiente archivado como '%s'.\n\n", archiveName)
					return true
				} else {
					fmt.Printf("Servidor no disponible (%v). Se mantendrá offline para próximo intento.\n\n", errSend)
				}
			}
		}
	}
	return false
}

func main() {
	cliMode := flag.Bool("cli", false, "Ejecuta el agente por consola interactiva en lugar de la interfaz gráfica")
	debugMode := flag.Bool("debug", false, "Muestra la consola de comandos con logs detallados de la interfaz")
	autoTest := flag.Bool("auto-test", false, "Ejecuta una prueba funcional interactiva de la GUI simulando el registro completo")
	noDelete := flag.Bool("no-delete", false, "Evita eliminar el ejecutable tras el registro exitoso (modo pruebas)")
	previewOnly := flag.Bool("preview", false, "Solo recopila y muestra el hardware detectado sin pedir datos de usuario")
	demoMode := flag.Bool("demo-user", false, "Genera el payload unificado con un usuario de prueba para demostración")
	saveOutput := flag.String("save", "", "Guarda el payload JSON unificado en la ruta de archivo especificada")
	dryRun := flag.Bool("dry-run", false, "No transmite al servidor, solo genera y muestra el payload")
	syncOfflineOnly := flag.Bool("sync-offline", false, "Solo sincroniza registros pendientes 'inventario_pendiente.json' y finaliza")
	serverURL := flag.String("server", client.DefaultServerURL, "URL base del servidor web Inventario UGEL")
	agentToken := flag.String("token", client.DefaultAgentToken, "Token de autenticación del agente (X-Agent-Token)")
	flag.Parse()

	// Por defecto (doble clic o ejecución sin parámetros): Iniciar la Interfaz Gráfica Nativa
	if !*cliMode && !*previewOnly && !*demoMode && !*syncOfflineOnly && !*dryRun {
		if err := gui.RunGUI(*serverURL, *agentToken, !*noDelete, *debugMode, *autoTest); err != nil {
			fmt.Printf("Error ejecutando interfaz gráfica: %v\n", err)
			os.Exit(1)
		}
		return
	}

	fmt.Println("==================================================================")
	fmt.Println("         AGENTE PORTABLE DE HARDWARE - UGEL HUÁNUCO              ")
	fmt.Println("   (Auditoría Automática de Hardware e Inventario Institucional)   ")
	fmt.Println("==================================================================")

	// Sincronización automática de respaldos offline si existieran
	syncOfflineBackupIfExists(*serverURL, *agentToken)
	if *syncOfflineOnly {
		fmt.Println("[MODO SYNC-OFFLINE]: Finalizado proceso de sincronización.")
		return
	}

	// --- PASO 1: Recopilación Automática de Hardware (Fase 1) ---
	fmt.Println("\n[1/4] Extrayendo características técnicas del equipo...")
	start := time.Now()
	report, err := hardware.CollectHardware()
	elapsed := time.Since(start)

	if err != nil {
		fmt.Printf("ERROR recopilando hardware: %v\n", err)
		os.Exit(1)
	}

	fmt.Printf(">> Extracción completada en: %v\n", elapsed)
	fmt.Println("------------------------------------------------------------------")
	fmt.Printf("Equipo Detectado : %s (%s - %s)\n", report.HostName, report.Chassis.Manufacturer, report.Chassis.Model)
	fmt.Printf("Tipo Inferido    : %s\n", report.DeviceType)
	fmt.Printf("Número de Serie  : %s\n", report.SerialNumber)
	fmt.Printf("Procesador (CPU) : %s\n", report.CPU.Formatted)
	fmt.Printf("Memoria RAM      : %s\n", report.RAM.Formatted)
	if len(report.Disks) > 0 {
		for i, d := range report.Disks {
			fmt.Printf("Almacenamiento %d : %s\n", i+1, d.Formatted)
		}
	}
	fmt.Printf("Placa Madre      : %s\n", report.Motherboard.Formatted)
	fmt.Printf("Sistema Operativo: %s\n", report.OS.Formatted)
	fmt.Printf("Conexión Red     : %s (MAC: %s)\n", report.Network.IPAddress, report.Network.MACAddress)

	// Detección de Programas Instalados
	fmt.Println(">> Escaneando software y programas instalados...")
	installedPrograms, errProg := programs.CollectInstalledPrograms()
	if errProg == nil {
		fmt.Printf("Programas Detect.: %d aplicaciones encontradas\n", len(installedPrograms))
	} else {
		fmt.Printf("Programas Detect.: No se pudo escanear registro (%v)\n", errProg)
		installedPrograms = make([]programs.InstalledProgram, 0)
	}
	fmt.Println("------------------------------------------------------------------")

	if *previewOnly {
		if len(installedPrograms) > 0 {
			fmt.Println("\n[MUESTRA DE PROGRAMAS INSTALADOS]:")
			limit := 10
			if len(installedPrograms) < limit {
				limit = len(installedPrograms)
			}
			for i := 0; i < limit; i++ {
				fmt.Printf("  - %s (v: %s | %s)\n", installedPrograms[i].Name, installedPrograms[i].Version, installedPrograms[i].Publisher)
			}
			if len(installedPrograms) > limit {
				fmt.Printf("  ... y %d aplicaciones más.\n", len(installedPrograms)-limit)
			}
		}
		fmt.Println("\n[MODO PREVIEW]: Finalizado satisfactoriamente.")
		return
	}

	// --- PASO 2: Obtención de Oficinas (Remoto o Catálogo Local) ---
	fmt.Println("\n[2/4] Cargando catálogo institucional de oficinas...")
	oficinasCatalog, errOf := client.FetchRemoteOficinas(*serverURL)
	if errOf != nil {
		fmt.Printf("    (Aviso: Servidor no disponible en %s. Usando catálogo integrado de UGEL).\n", *serverURL)
		oficinasCatalog = persona.GetDefaultOficinas()
	} else {
		fmt.Printf("    -> Sincronizadas %d oficinas desde el servidor web.\n", len(oficinasCatalog))
	}

	// --- PASO 3: Captura del Responsable del Equipo (Fase 2) ---
	var usuario *persona.Persona

	if *demoMode {
		fmt.Println("\n[3/4] [MODO DEMO]: Cargando datos de usuario de prueba...")
		usuario = &persona.Persona{
			NombreCompleto: "Juan Perez Ramos",
			IDOficina:      10, // Informática
			Cargo:          "Especialista en Soporte Técnico",
			Celular:        "987654321",
			Correo:         "jperez@ugelhuanuco.gob.pe",
			Estado:         "ACTIVO",
		}
	} else {
		fmt.Println("\n[3/4] Iniciando formulario interactivo del responsable...")
		usuario, err = persona.CapturePersonaInteractive(os.Stdin, os.Stdout, oficinasCatalog)
		if err != nil {
			fmt.Printf("ERROR en captura de usuario: %v\n", err)
			os.Exit(1)
		}
	}

	// --- PASO 4: Ensamblado y Transmisión a la API (Fase 3) ---
	fmt.Println("\n[4/4] Ensamblando y normalizando payload técnico unificado...")
	payload, err := processor.BuildUnifiedPayload(usuario, report, installedPrograms)
	if err != nil {
		fmt.Printf("ERROR al construir payload unificado: %v\n", err)
		os.Exit(1)
	}

	jsonBytes, err := processor.ToJSON(payload, true)
	if err != nil {
		fmt.Printf("ERROR al serializar JSON: %v\n", err)
		os.Exit(1)
	}

	fmt.Println("\n--- PAYLOAD ESTRUCTURADO ---")
	fmt.Println(string(jsonBytes))

	if *saveOutput != "" {
		if err := processor.SaveToFile(payload, *saveOutput); err != nil {
			fmt.Printf("Error guardando archivo local: %v\n", err)
		} else {
			fmt.Printf("\n[ARCHIVO GUARDADO]: %s\n", *saveOutput)
		}
	}

	if *dryRun {
		fmt.Println("\n[MODO DRY-RUN]: Finalizado sin transmisión a la API.")
		return
	}

	// Transmisión al Servidor Backend Laravel
	fmt.Printf("\nTransmitiendo a la plataforma web UGEL (%s/api/v1/agente/registro)...\n", *serverURL)
	apiResp, errSend := client.SendRegistration(*serverURL, *agentToken, payload)

	if errSend != nil {
		if apiResp != nil && !apiResp.Success {
			fmt.Println("\n==================================================================")
			fmt.Println("             ALERTA DE VALIDACIÓN EN EL SERVIDOR                  ")
			fmt.Println("==================================================================")
			fmt.Printf("Mensaje: %s\n", apiResp.Message)
			if len(apiResp.Errors) > 0 {
				fmt.Printf("Detalles: %s\n", string(apiResp.Errors))
			}
			fmt.Println("Por favor verifique los datos del responsable o comuníquese con Informática.")
		} else {
			fmt.Printf("\n[ADVERTENCIA DE CONEXIÓN]: %v\n", errSend)
			offlineBackup := "inventario_pendiente.json"
			if errSave := processor.SaveToFile(payload, offlineBackup); errSave == nil {
				fmt.Printf(">> Respaldo generado automáticamente en la memoria USB: '%s'\n", offlineBackup)
				fmt.Println(">> El registro se sincronizará cuando el equipo tenga conexión a la red de la UGEL.")
			}
		}
		return
	}

	fmt.Println("\n==================================================================")
	fmt.Println("              ¡REGISTRO COMPLETADO EXITOSAMENTE!                 ")
	fmt.Println("==================================================================")
	fmt.Printf("Acción Servidor: %s\n", apiResp.Action)
	fmt.Printf("Mensaje        : %s\n", apiResp.Message)
	fmt.Println("El equipo ha quedado registrado en estado 'BORRADOR' en la plataforma web.")
	fmt.Println("El administrador de inventario ya puede revisarlo y confirmarlo.")

	if !*noDelete && !*dryRun && !*demoMode {
		fmt.Println("\n>> Limpieza de seguridad: Eliminando ejecutable temporal de Descargas...")
		_ = gui.ScheduleSelfDelete()
	}
}
